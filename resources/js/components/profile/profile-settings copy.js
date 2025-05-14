import { createAndShowToast } from "../../utils/uiHelpers";
import { apiTokenHandler } from "../api-token/api-token";
import loader from "../loader";
import { ajaxFetcher } from "../fetcher/ajax-fetcher";

/**
 * Profile settings form handler
 * Manages asynchronous form submission with full-screen loading state
 */
export const profileSettingsHandler = {
    /**
     * Initialize the profile settings handler
     * @param {Object} config Configuration options
     */
    init(config = {}) {
        // Default configuration
        this.config = {
            formId: config.formId,
            apiEndpoint: config.apiEndpoint,
            redirectOnSuccess: false,
            ...config,
        };

        // Get form element
        this.form = document.getElementById(this.config.formId);

        if (!this.form) {
            console.error(
                `Profile settings form with ID "${this.config.formId}" not found`
            );
            return;
        }

        // Initialize event listeners
        this._initEventListeners();

        console.log("Profile settings handler initialized", this.config);
    },

    /**
     * Set up event listeners
     */
    _initEventListeners() {
        // Form submission event
        this.form.addEventListener("submit", this._handleFormSubmit.bind(this));
    },

    /**
     * Handle form submission
     * @param {Event} event Submit event
     */
    async _handleFormSubmit(event) {
        // Prevent default form submission
        event.preventDefault();

        try {
            // Show loader
            loader.show();

            // Get form data
            const formData = new FormData(this.form);

            // Convert FormData to JSON object
            const formDataObj = {};
            formData.forEach((value, key) => {
                // Skip _method and _token as they'll be handled differently
                if (key !== "_method" && key !== "_token") {
                    // Handle arrays (like messengers[])
                    if (key.includes("[") && key.includes("]")) {
                        const mainKey = key.substring(0, key.indexOf("["));
                        const subKey = key.substring(
                            key.indexOf("[") + 1,
                            key.indexOf("]")
                        );

                        if (!formDataObj[mainKey]) {
                            formDataObj[mainKey] = [];
                        }

                        // If the array already has an object at this index, update it
                        // Otherwise, create a new object
                        const index = parseInt(subKey);
                        if (!isNaN(index)) {
                            if (!formDataObj[mainKey][index]) {
                                formDataObj[mainKey][index] = {};
                            }
                        } else {
                            // For associative arrays (e.g., messengers[type])
                            if (!formDataObj[mainKey][0]) {
                                formDataObj[mainKey][0] = {};
                            }
                            formDataObj[mainKey][0][subKey] = value;
                        }
                    } else {
                        formDataObj[key] = value;
                    }
                }
            });

            // Add CSRF token
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content");

            // Check and refresh token if needed
            if (
                apiTokenHandler &&
                apiTokenHandler.hasToken() &&
                apiTokenHandler.isTokenExpiredOrExpiring()
            ) {
                try {
                    await apiTokenHandler.refreshToken();
                } catch (error) {
                    console.error(
                        "Failed to refresh token before profile settings update:",
                        error
                    );
                }
            }

            // Get API token if available
            const apiToken = apiTokenHandler
                ? apiTokenHandler.getToken()
                : null;

            // Make API request
            const response = await fetch(this.config.apiEndpoint, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken || "",
                    ...(apiToken
                        ? { Authorization: `Bearer ${apiToken}` }
                        : {}),
                },
                body: JSON.stringify({
                    ...formDataObj,
                    _method: "PUT",
                }),
                credentials: "include", // Use include instead of same-origin to match token handling
                mode: "cors",
                cache: "no-cache",
            });

            // Check if response is OK first
            if (!response.ok) {
                // Try to parse JSON response, but handle HTML responses too
                let errorMessage = "Failed to update profile settings";
                const contentType = response.headers.get("content-type");

                if (contentType && contentType.includes("application/json")) {
                    const errorData = await response.json();
                    errorMessage = errorData.message || errorMessage;
                } else {
                    // Not JSON - might be HTML response from a redirect or error page
                    const text = await response.text();
                    console.error(
                        "Received non-JSON response:",
                        text.substring(0, 100) + "..."
                    );
                    errorMessage =
                        "Authentication error or session expired. Please reload the page.";
                }

                throw new Error(errorMessage);
            }

            // If we get here, response is OK, so parse JSON
            const data = await response.json();

            // Handle success
            this._handleUpdateSuccess(data);
        } catch (error) {
            // Handle error
            this._handleUpdateError(error);
        } finally {
            // Hide loader
            loader.hide();
        }
    },

    /**
     * Handle successful update
     * @param {Object} data Server response data
     */
    _handleUpdateSuccess(data) {
        // Show success toast
        createAndShowToast(data.message, "success");

        // Update the form with new values if needed
        // This could be done if the server modifies any values

        // Redirect if configured
        if (this.config.redirectOnSuccess && data.redirectUrl) {
            window.location.href = data.redirectUrl;
        }
    },

    /**
     * Handle update error
     * @param {Error} error Error object
     */
    _handleUpdateError(error) {
        console.error("Profile settings update failed:", error);

        // Show error toast
        createAndShowToast(
            error.message ||
                "Failed to update profile settings. Please try again.",
            "error"
        );
    },
};
