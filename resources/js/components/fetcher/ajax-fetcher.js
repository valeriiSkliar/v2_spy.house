// import $ from "jquery";
import { apiTokenHandler } from "../api-token/api-token";

/**
 * Ajax fetcher for making API requests
 * Automatically handles authentication tokens
 */
const ajaxFetcher = {
    /**
     * Make a GET request
     * @param {string} url - The URL to fetch
     * @param {object} data - Query parameters
     * @param {object} settings - Settings object
     * @returns {Promise} jQuery ajax promise
     */
    get: (
        url,
        data,
        {
            successCallback = null,
            errorCallback = null,
            beforeSendCallback = null,
            completeCallback = null,
        }
    ) =>
        $.ajax({
            url,
            method: "GET",
            data,
            success: successCallback,
            error: errorCallback,
            beforeSend: beforeSendCallback,
            complete: completeCallback,
        }),

    /**
     * Make a POST request
     * @param {string} url - The URL to fetch
     * @param {object} data - Body data
     * @returns {Promise} jQuery ajax promise
     */
    post: (url, data) =>
        $.ajax({
            url,
            method: "POST",
            data,
            contentType: "application/json",
        }),

    /**
     * Make a PUT request
     * @param {string} url - The URL to fetch
     * @param {object} data - Body data
     * @returns {Promise} jQuery ajax promise
     */
    put: (url, data) =>
        $.ajax({
            url,
            method: "PUT",

            data,
            contentType: "application/json",
        }),

    /**
     * Make a DELETE request
     * @param {string} url - The URL to fetch
     * @returns {Promise} jQuery ajax promise
     */
    delete: (url) => $.ajax({ url, method: "DELETE" }),

    /**
     * Send form data
     * @param {string} url - The URL to send form data to
     * @param {FormData} formData - Form data to send
     * @returns {Promise} jQuery ajax promise
     */
    form: (
        url,
        formData,
        method = "POST",
        settings = {
            successCallback: null,
            errorCallback: null,
            beforeSendCallback: null,
            completeCallback: null,
        }
    ) => {
        if (method !== "POST") {
            // Add _method=PUT for Laravel to handle PUT requests
            formData.append("_method", method);
        }
        return $.ajax({
            url,
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            ...settings,
        });
    },

    /**
     * Send form data
     * @param {string} url - The URL to send form data to
     * @param {FormData} formData - Form data to send
     * @returns {Promise} jQuery ajax promise
     */
    submit: (
        url,
        {
            data = null,
            successCallback = null,
            errorCallback = null,
            beforeSendCallback = null,
            completeCallback = null,
        } = {}
    ) => {
        const ajaxOptions = {
            url,
            method: "POST",
            data: data,
            success: successCallback,
            error: errorCallback,
            beforeSend: beforeSendCallback,
            complete: completeCallback,
        };

        if (data instanceof FormData) {
            ajaxOptions.processData = false;
            ajaxOptions.contentType = false;
        }

        return $.ajax(ajaxOptions);
    },
};

/**
 * Initialize ajax interceptor for API requests
 * Sets up authorization headers and handles 401 errors
 */
const initAjaxFetcher = () => {
    // Set up default headers with token
    updateAjaxHeaders();

    // Add a pre-request interceptor to check token expiration
    // Use $.ajaxSetup instead of ajaxPrefilter since the latter might not be available
    $.ajaxSetup({
        beforeSend: async function (jqXHR, settings) {
            // Check if this is an API request (skip for non-API requests)
            const isApiRequest = settings.url.includes("/api/");
            if (!isApiRequest) return;

            // For API requests, check if token is about to expire
            if (
                apiTokenHandler.hasToken() &&
                apiTokenHandler.isTokenExpiredOrExpiring()
            ) {
                try {
                    // Refresh the token before making the request
                    console.log(
                        "Token is expiring soon. Refreshing before request..."
                    );
                    await apiTokenHandler.refreshToken();

                    // Update the request headers with the new token
                    const token = apiTokenHandler.getToken();
                    if (token) {
                        settings.headers = settings.headers || {};
                        settings.headers["Authorization"] = "Bearer " + token;
                    }
                } catch (error) {
                    console.error(
                        "Failed to refresh token before request:",
                        error
                    );
                    // Let the request proceed and potentially fail with 401
                }
            }
        },
    });

    // Set up ajax error handler for 401 errors
    $(document).ajaxError(async function (
        event,
        jqXHR,
        ajaxSettings,
        thrownError
    ) {
        // Handle 401 Unauthorized errors
        if (jqXHR.status === 401) {
            console.log("Received 401 response. Attempting token refresh...");
            try {
                // Try to refresh the token
                await apiTokenHandler.refreshToken();

                // Update headers for all future requests
                updateAjaxHeaders();

                // Retry the original request
                console.log("Token refreshed. Retrying original request...");
                return $.ajax({
                    ...ajaxSettings,
                    headers: {
                        ...ajaxSettings.headers,
                        Authorization: "Bearer " + apiTokenHandler.getToken(),
                    },
                });
            } catch (error) {
                console.error("Token refresh failed after 401 error", error);

                // If the refresh fails with a 401/403, redirect to login
                if (
                    error.message.includes("401") ||
                    error.message.includes("403")
                ) {
                    console.warn(
                        "Authentication error. You may need to log in again."
                    );
                    // Uncomment to redirect to login
                    // window.location.href = '/login';
                }
            }
        }
    });
};

/**
 * Update Ajax headers with current token
 * Called on init and after token refresh
 */
const updateAjaxHeaders = () => {
    const token = apiTokenHandler.getToken();
    if (!token) {
        console.warn("API token not found. Some API requests may fail.");
    }

    $.ajaxSetup({
        credentials: "same-origin",
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content") || "",
            Authorization: token ? "Bearer " + token : "",
        },
    });
};

// Initialize automatically
document.addEventListener("DOMContentLoaded", initAjaxFetcher);

export { initAjaxFetcher, ajaxFetcher };
