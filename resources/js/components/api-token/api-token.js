/**
 * API Token handling module
 * Manages token storage, retrieval, automatic refresh and expiration checks
 * Designed to work with HttpOnly cookies for refresh tokens
 */
const apiTokenHandler = {
    /**
     * Key used for localStorage token storage
     */
    TOKEN_STORAGE_KEY: "bt",
    
    /**
     * Key for storing token expiration time
     */
    TOKEN_EXPIRATION_KEY: "bt_exp",
    
    /**
     * Threshold in milliseconds before token expiration to trigger a refresh
     * Default: 5 minutes
     */
    REFRESH_THRESHOLD_MS: 5 * 60 * 1000,
    
    /**
     * Flag to prevent multiple simultaneous refresh attempts
     */
    _isRefreshing: false,
    
    /**
     * Queue of functions waiting for token refresh
     */
    _refreshQueue: [],

    /**
     * Get token from localStorage
     * @returns {string|null} The stored token or null if not found
     */
    getToken: () => {
        return localStorage.getItem(apiTokenHandler.TOKEN_STORAGE_KEY);
    },

    /**
     * Save token to localStorage
     * @param {string} token - The token to store
     * @param {number|null} expiresAt - Unix timestamp when token expires (optional)
     */
    setToken: (token, expiresAt = null) => {
        if (token) {
            localStorage.setItem(apiTokenHandler.TOKEN_STORAGE_KEY, token);
            
            // If expiration time is provided, store it
            if (expiresAt) {
                localStorage.setItem(apiTokenHandler.TOKEN_EXPIRATION_KEY, expiresAt.toString());
            }
            
            console.log("API token stored in localStorage");
        }
    },

    /**
     * Remove token from localStorage and clean up foreign cookies
     */
    removeToken: () => {
        // Clear localStorage tokens
        localStorage.removeItem(apiTokenHandler.TOKEN_STORAGE_KEY);
        localStorage.removeItem(apiTokenHandler.TOKEN_EXPIRATION_KEY);
        localStorage.removeItem("bt_refresh"); // Also remove the refresh token fallback

        // Attempt to clear any foreign cookies that might interfere with authentication
        if (typeof document !== 'undefined') {
            const cookiesToDelete = ['eoassist_auth_session', 'refresh_token'];
            const expires = 'expires=Thu, 01 Jan 1970 00:00:01 GMT';

            cookiesToDelete.forEach(cookieName => {
                document.cookie = `${cookieName}=; ${expires}; path=/; domain=${window.location.hostname}`;
                document.cookie = `${cookieName}=; ${expires}; path=/;`;
                console.log(`Attempted to delete cookie: ${cookieName}`);
            });
        }
    },

    /**
     * Check if a token is present in localStorage
     * @returns {boolean} True if token exists
     */
    hasToken: () => {
        return !!apiTokenHandler.getToken();
    },
    
    /**
     * Get token expiration time
     * @returns {number|null} Unix timestamp or null if not set
     */
    getTokenExpiration: () => {
        const expStr = localStorage.getItem(apiTokenHandler.TOKEN_EXPIRATION_KEY);
        return expStr ? parseInt(expStr, 10) : null;
    },
    
    /**
     * Check if the token is expired or about to expire
     * @param {number} thresholdMs - How many milliseconds before expiration to consider it "about to expire"
     * @returns {boolean} True if token is expired or will expire soon
     */
    isTokenExpiredOrExpiring: (thresholdMs = null) => {
        const expiresAt = apiTokenHandler.getTokenExpiration();
        if (!expiresAt) return false; // If no expiration is set, assume it's still valid
        
        const threshold = thresholdMs || apiTokenHandler.REFRESH_THRESHOLD_MS;
        const now = Math.floor(Date.now() / 1000); // Current time in seconds
        
        // Return true if token expires within the threshold
        return expiresAt - now < threshold / 1000;
    },

    /**
     * Try to refresh the token using the HttpOnly refresh token cookie or fallback to localStorage
     * @returns {Promise<string>} A promise that resolves with the new token
     */
    refreshToken: async () => {
        console.log("Starting token refresh process");
        
        // If already refreshing, wait for that to complete
        if (apiTokenHandler._isRefreshing) {
            console.log("Another refresh already in progress, waiting...");
            return new Promise((resolve, reject) => {
                apiTokenHandler._refreshQueue.push({ resolve, reject });
            });
        }
        
        apiTokenHandler._isRefreshing = true;
        console.log("Setting refresh flag to prevent concurrent refreshes");
        
        try {
            // Check for CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            console.log("Using CSRF token:", csrfToken ? "Available" : "Not found");
            
            // Check for manually stored refresh token as fallback
            // Note: This is less secure but provides a fallback for environments where cookies don't work
            const storedRefreshToken = localStorage.getItem("bt_refresh");

            // Debug refresh token status
            if (storedRefreshToken) {
                console.log("Found stored refresh token in localStorage, length:", storedRefreshToken.length);
                console.log("Token prefix:", storedRefreshToken.substring(0, 5) + "...");
            } else {
                console.log("No refresh token found in localStorage");
            }

            const hasCookie = document.cookie.includes('refresh_token=');
            console.log("Has refresh_token cookie:", hasCookie);

            // Only use localStorage token if cookies are disabled or no cookie exists
            const useTokenFallback = storedRefreshToken && (!navigator.cookieEnabled || !hasCookie);

            // Create request body with refresh token if available
            const requestBody = useTokenFallback ?
                JSON.stringify({
                    refresh_token: storedRefreshToken || "",
                    include_refresh_token: "true" // Request refresh token in response
                }) :
                JSON.stringify({
                    include_refresh_token: "true" // Always request refresh token in response for fallback
                });

            console.log("Calling refresh token endpoint...");
            console.log("Using stored refresh token as fallback:", useTokenFallback);
            console.log("Cookies enabled in browser:", navigator.cookieEnabled);
            
            // Call the token refresh endpoint, which will use the HttpOnly cookie
            // or the stored refresh token as fallback
            // Prepare headers
            const headers = {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'Accept': 'application/json',
            };
            
            // Always request token in response as fallback
            headers['X-Include-Refresh-Token'] = 'true';
            
            const response = await fetch('/api/auth/refresh-token', {
                method: 'POST',
                headers: headers,
                body: requestBody, // Include refresh token in body if available
                credentials: 'include', // Include cookies in cross-origin requests
                mode: 'cors', // Explicitly enable CORS requests
                cache: 'no-cache' // Prevent caching of token requests
            });

            console.log("Refresh response status:", response.status);
            
            if (!response.ok) {
                // Try to get more details about the error
                try {
                    const errorData = await response.json();
                    console.error("Error details:", errorData);
                    
                    // Handle specific error cases
                    if (response.status === 400 && errorData.message) {
                        console.warn("Token refresh failed:", errorData.message);
                        
                        // If refresh token not provided, try to recover by clearing and requesting login
                        if (errorData.message.includes('not provided')) {
                            apiTokenHandler.removeToken();
                            console.warn("Token refresh failed due to missing token. Clearing storage.");
                        }
                    } else if (response.status === 401 || response.status === 422) {
                        // Authentication error or validation error, clear tokens
                        apiTokenHandler.removeToken();
                        console.warn("Authentication failed during token refresh. Clearing token.");
                        
                        // If we're on a page that requires authentication, redirect to login
                        const requiresAuth = document.querySelector('meta[name="requires-auth"]');
                        if (requiresAuth && requiresAuth.getAttribute('content') === 'true') {
                            console.warn("Page requires authentication but cannot refresh token. Redirecting to login...");
                            window.location.href = '/login';
                        }
                    }
                } catch (e) {
                    // If we can't parse the error response, just log the status
                    console.error("Couldn't parse error response");
                }
                
                throw new Error(`Failed to refresh token: ${response.status}`);
            }

            console.log("Successfully received refresh response, parsing...");
            const data = await response.json();
            console.log("Response contains access_token:", !!data.access_token);
            console.log("Response contains expires_at:", !!data.expires_at);
            console.log("Response contains refresh_token in body:", !!data.refresh_token);
            
            if (data.access_token) {
                // Store the new token with its expiration
                console.log("Storing new token in localStorage");
                apiTokenHandler.setToken(data.access_token, data.expires_at);
                
                // Store refresh token as fallback if provided in response body
                // This is less secure but provides a fallback for environments where cookies don't work
                if (data.refresh_token) {
                    console.log("Storing refresh token as fallback, length:", data.refresh_token.length);
                    console.log("New token prefix:", data.refresh_token.substring(0, 5) + "...");
                    localStorage.setItem("bt_refresh", data.refresh_token);
                } else {
                    console.warn("No refresh_token in response body - cookie-only mode will be used");
                }
                
                // Process waiting functions
                if (apiTokenHandler._refreshQueue.length > 0) {
                    console.log(`Resolving ${apiTokenHandler._refreshQueue.length} waiting promises`);
                    apiTokenHandler._refreshQueue.forEach(item => item.resolve(data.access_token));
                }
                
                return data.access_token;
            }
            throw new Error('No token in response');
        } catch (error) {
            console.error('Error refreshing token:', error);
            
            // Reject all waiting promises
            if (apiTokenHandler._refreshQueue.length > 0) {
                console.log(`Rejecting ${apiTokenHandler._refreshQueue.length} waiting promises`);
                apiTokenHandler._refreshQueue.forEach(item => item.reject(error));
            }
            
            // If token refresh fails, clear the token
            console.log("Removing invalid token from localStorage");
            apiTokenHandler.removeToken();
            
            // Optional: redirect to login page
            if (error.message.includes('401') || error.message.includes('403') || error.message.includes('422')) {
                console.warn('Authentication error. Redirecting to login...');
                // Uncomment if you want to redirect to login
                // window.location.href = '/login';
            }
            
            throw error;
        } finally {
            console.log("Resetting refresh state");
            apiTokenHandler._isRefreshing = false;
            apiTokenHandler._refreshQueue = [];
        }
    },
    
    /**
     * Start token monitoring to check for expiration and auto-refresh
     */
    startTokenMonitor: () => {
        // Check the token immediately
        apiTokenHandler.checkAndRefreshToken();
        
        // Then set up interval to check token every minute
        setInterval(apiTokenHandler.checkAndRefreshToken, 60 * 1000);
    },
    
    /**
     * Check token expiration and refresh if needed
     */
    checkAndRefreshToken: async () => {
        // Do nothing if there's no token
        if (!apiTokenHandler.hasToken()) return;
        
        // Check if token is about to expire
        if (apiTokenHandler.isTokenExpiredOrExpiring()) {
            console.log('Token is expiring soon. Refreshing...');
            try {
                await apiTokenHandler.refreshToken();
                console.log('Token refreshed successfully');
            } catch (error) {
                console.error('Failed to refresh token:', error);
            }
        }
    },

    /**
     * Initialize the token system
     * Checks for token in meta tag, then localStorage
     */
    init: () => {
        // Add a global recovery method to window for emergency token reset
        window.resetApiTokens = () => {
            console.warn("Manual token reset initiated");
            apiTokenHandler.removeToken();
            alert("API tokens have been reset. Please reload the page and try again.");
            return "Tokens cleared. Reload the page to complete reset.";
        };

        // First check for token in meta tag (high priority - just set by server)
        const metaToken = document.querySelector('meta[name="api-token"]')?.getAttribute('content');
        const metaExpires = document.querySelector('meta[name="api-token-expires-at"]')?.getAttribute('content');

        // Check for URL parameter that forces token reset (useful for stuck states)
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('reset_tokens')) {
            console.warn("Token reset requested via URL parameter");
            apiTokenHandler.removeToken();

            // Remove the parameter from URL to prevent repeated resets
            if (history.replaceState) {
                const url = new URL(window.location.href);
                url.searchParams.delete('reset_tokens');
                window.history.replaceState({}, '', url);
            }
        }

        if (metaToken) {
            // Store token with expiration if available
            apiTokenHandler.setToken(metaToken, metaExpires ? parseInt(metaExpires, 10) : null);
        } else {
            // Check for token in hidden form field (backward compatibility)
            const formToken = document.getElementById('api_token')?.value;
            if (formToken) {
                apiTokenHandler.setToken(formToken);
            }
        }

        // Start monitoring token expiration
        apiTokenHandler.startTokenMonitor();
        
        // Validate that we have a token
        const token = apiTokenHandler.getToken();
        if (!token) {
            console.warn("No API token found. Some features may not work properly.");
            
            // If we don't have a token but the user is logged in (has a session)
            // we can attempt to get a new token via the refresh endpoint
            // This helps in cases where localStorage was cleared but session is still valid
            if (document.cookie.includes('spy_house_session') || document.cookie.includes('laravel_session')) {
                console.log("User appears to be logged in. Attempting to get a new token...");
                setTimeout(() => {
                    apiTokenHandler.refreshToken()
                        .then(newToken => {
                            console.log("Successfully obtained new token:", newToken.substring(0, 10) + "...");
                            
                            // Force reload if we're on a page that requires authentication
                            const requiresAuth = document.querySelector('meta[name="requires-auth"]');
                            if (requiresAuth && requiresAuth.getAttribute('content') === 'true') {
                                console.log("Page requires authentication and token refreshed. Reloading...");
                                window.location.reload();
                            }
                        })
                        .catch(error => {
                            console.warn("Could not get a new token:", error);
                            
                            // If we're on a page that requires authentication and can't refresh token,
                            // redirect to login
                            const requiresAuth = document.querySelector('meta[name="requires-auth"]');
                            if (requiresAuth && requiresAuth.getAttribute('content') === 'true') {
                                console.warn("Page requires authentication but cannot refresh token. Redirecting to login...");
                                window.location.href = '/login';
                            }
                        });
                }, 100); // Short delay to ensure page is loaded
            }
        } else {
            console.log("API token loaded. Monitoring expiration.");
        }
    },
};

// Initialize automatically
document.addEventListener('DOMContentLoaded', apiTokenHandler.init);

export { apiTokenHandler };