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
     * Remove token from localStorage
     */
    removeToken: () => {
        localStorage.removeItem(apiTokenHandler.TOKEN_STORAGE_KEY);
        localStorage.removeItem(apiTokenHandler.TOKEN_EXPIRATION_KEY);
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
     * Try to refresh the token using the HttpOnly refresh token cookie
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
            
            console.log("Calling refresh token endpoint with cookies...");
            // Call the token refresh endpoint, which will use the HttpOnly cookie
            const response = await fetch('/api/auth/refresh-token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                },
                credentials: 'same-origin' // Important: include cookies
            });

            console.log("Refresh response status:", response.status);
            
            if (!response.ok) {
                // Try to get more details about the error
                try {
                    const errorData = await response.json();
                    console.error("Error details:", errorData);
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
            
            if (data.access_token) {
                // Store the new token with its expiration
                console.log("Storing new token in localStorage");
                apiTokenHandler.setToken(data.access_token, data.expires_at);
                
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
            if (error.message.includes('401') || error.message.includes('403')) {
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
        // First check for token in meta tag (high priority - just set by server)
        const metaToken = document.querySelector('meta[name="api-token"]')?.getAttribute('content');
        const metaExpires = document.querySelector('meta[name="api-token-expires-at"]')?.getAttribute('content');
        
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
        } else {
            console.log("API token loaded. Monitoring expiration.");
        }
    },
};

// Initialize automatically
document.addEventListener('DOMContentLoaded', apiTokenHandler.init);

export { apiTokenHandler };
