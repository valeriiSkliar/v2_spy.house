import { createAndShowToast } from "@/utils";
import { debounce } from "@/helpers/custom-debounce";
import { apiTokenHandler } from "../api-token";

/**
 * Updates the service rating UI elements
 * @param {string|number} rating - The average rating to display
 * @param {string|number|null} userRating - The user's submitted rating (if any)
 */
const updateServiceRating = (rating, userRating = null) => {
    // Format rating to one decimal place for display
    const formattedRating =
        typeof rating === "number"
            ? rating.toFixed(1)
            : parseFloat(rating).toFixed(1);

    // Update all rating values on the page
    const ratingElements = document.querySelectorAll(".icon-rating");
    ratingElements.forEach((element) => {
        element.textContent = formattedRating;
    });

    // Update all rating values with icon-star class
    const starElements = document.querySelectorAll(".icon-star");
    starElements.forEach((element) => {
        element.textContent = formattedRating;
    });

    // Update the main rating value display
    const ratingValueElement = document.querySelector(".rate-service__value");
    if (ratingValueElement) {
        ratingValueElement.innerHTML = `<span class="font-weight-600">${formattedRating}</span>/5`;
    }

    // If user has rated, replace the rating form with a thank you message
    if (userRating) {
        const ratingContainer = document.querySelector(".rate-service");
        if (ratingContainer) {
            ratingContainer.innerHTML = `
                <div class="rate-service__success">
                    <div class="row align-items-center ">
                        <div class="col-12 col-md-5">
                            <h4>Thank you for rating!</h4>
                            <p class="mb-0">Your rating: <strong>${userRating}</strong> stars</p>
                        </div>
                        <div class="col-12 col-md-7 d-flex align-items-center justify-content-center">
                            <div class="rate-service__value"><span class="font-weight-600">${formattedRating}</span>/5</div>
                        </div>
                    </div>
                </div>
            `;
        }
    }
};

/**
 * Submits a service rating to the API
 * @param {number|string} serviceId - The ID of the service being rated
 * @param {number|string} rating - The rating value (1-5)
 */
const submitServiceRating = (serviceId, rating) => {
    // Show loading indicator
    const ratingContainer = document.querySelector(".rate-service");
    if (ratingContainer) {
        const ratingStars = ratingContainer.querySelector(
            ".rate-service__rating"
        );
        if (ratingStars) {
            ratingStars.classList.add("loading");
        }
    }

    // Check for CSRF token for the request
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) {
        console.error("CSRF token not found");
        createAndShowToast("Error: CSRF token missing", "error");
        return;
    }

    // Check if token needs refresh before making the request
    const performRequest = async () => {
        // Check if we have a token at all
        if (!apiTokenHandler.hasToken()) {
            console.warn("No API token found, attempting to refresh...");
            
            try {
                // Try to get a new token via refresh token
                await apiTokenHandler.refreshToken();
                
                // If we got here, refresh was successful
                console.log("Successfully obtained new token via refresh");
            } catch (error) {
                console.error("Failed to get token via refresh:", error);
                createAndShowToast("Authentication error. Please log in again.", "error");
                // Optionally redirect to login
                // window.location.href = '/login';
                return Promise.reject(new Error("No API token available"));
            }
        }

        // If token is expiring soon, refresh it first
        if (apiTokenHandler.isTokenExpiredOrExpiring()) {
            try {
                console.log("Access token expiring soon, refreshing before rating submission");
                await apiTokenHandler.refreshToken();
                console.log("Token refreshed successfully");
            } catch (error) {
                console.error("Failed to refresh token:", error);
                createAndShowToast("Error with authentication. Please try again.", "error");
                return Promise.reject(error);
            }
        }

        // Get the current token (after potential refresh)
        const token = apiTokenHandler.getToken();
        
        // Double check we have a valid token
        if (!token) {
            console.error("Still no valid API token after refresh attempt");
            createAndShowToast("Authentication error. Please log in again.", "error");
            return Promise.reject(new Error("No valid API token after refresh"));
        }

        console.log("Using token for request:", token.substring(0, 10) + "...");

        // Make the API request with the current (or refreshed) token
        return fetch(`/api/services/${serviceId}/rate/${rating}`, {
            method: "GET",
            credentials: "same-origin", // Include cookies for refresh token
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken?.getAttribute('content'),
                "Authorization": `Bearer ${token}`,
            },
        });
    };

    // Execute the request with token handling
    performRequest()
        .then((response) => {
            // Handle redirects (like to login page)
            if (response.redirected && response.url.includes("login")) {
                window.location.href = response.url;
                throw new Error("Authentication required");
            }

            // Handle error responses
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }

            return response.json();
        })
        .then((data) => {
            // Remove loading indicator if it exists

            console.log(data);
            if (ratingContainer) {
                const ratingStars = ratingContainer.querySelector(
                    ".rate-service__rating"
                );
                if (ratingStars) {
                    ratingStars.classList.remove("loading");
                }
            }

            if (data.success || data.average_rating || data.averageRating) {
                // Use the average_rating from the response or fall back to original rating
                const averageRating =
                    data.average_rating || data.averageRating || rating;
                const userRating =
                    data.user_rating || data.rating?.rating || rating;

                // Update UI with average rating and user rating
                updateServiceRating(averageRating, userRating);

                // Show success message
                createAndShowToast("Thank you for rating!", "success");
            } else {
                createAndShowToast(
                    data.message || "Error saving rating. Please try again.",
                    "error"
                );
            }
        })
        .catch((error) => {
            console.error("Error:", error);

            // Remove loading indicator if it exists
            if (ratingContainer) {
                const ratingStars = ratingContainer.querySelector(
                    ".rate-service__rating"
                );
                if (ratingStars) {
                    ratingStars.classList.remove("loading");
                }
            }

            // Handle specific error cases
            if (error.message.includes("401") || error.message.includes("403")) {
                // Authentication or authorization error
                console.log("Authentication error, attempting token refresh");
                // Try to refresh the token and retry the operation
                apiTokenHandler.refreshToken()
                    .then(() => {
                        console.log("Token refreshed, retrying rating submission");
                        // Retry the rating submission after successful token refresh
                        submitServiceRating(serviceId, rating);
                    })
                    .catch((refreshError) => {
                        console.error("Failed to refresh token:", refreshError);
                        createAndShowToast(
                            "Authentication error. Please log in again.",
                            "error"
                        );
                    });
            } 
            // Only show generic error if not a redirect
            else if (!error.message.includes("Authentication required")) {
                createAndShowToast(
                    "Error saving rating. Please try again.",
                    "error"
                );
            }
        });
};

// Create a debounced version of the submit function to prevent multiple rapid submissions
const debouncedSubmitServiceRating = debounce(submitServiceRating, 300);

/**
 * Initializes the service rating component
 * @param {HTMLElement|null} ratingContainersElement - Optional container element
 */
const initSingleServiceRating = (ratingContainersElement = null) => {
    // Check if we're on a single service page
    const singleMarketElement = document.querySelector(".single-market");
    if (!singleMarketElement) {
        return;
    }

    // Get the service ID from the data attribute
    const serviceId = singleMarketElement.dataset.serviceId;
    if (!serviceId) {
        console.error("Service ID not found");
        return;
    }

    // Get all rating containers or use the provided one
    const ratingContainers =
        ratingContainersElement ||
        document.querySelectorAll(".rate-service__rating");

    if (ratingContainers.length === 0) {
        return;
    }

    // Initialize each rating container
    ratingContainers.forEach((container) => {
        const currentRating = parseInt(container.dataset.rating) || 0;
        const isRated = container.dataset.isRated === "true";

        // Clear existing stars first (important for re-initialization)
        container.innerHTML = "";

        // If already rated, don't initialize the interactive stars
        if (isRated) {
            return;
        }

        // Create stars
        for (let i = 1; i <= 5; i++) {
            const star = document.createElement("span");
            star.classList.add("rating-star");
            star.dataset.value = i;

            // Pre-activate stars based on current rating
            if (i <= currentRating) {
                star.classList.add("active");
            }

            star.innerHTML = "★";

            // Add hover effects
            star.addEventListener("mouseover", function () {
                // Highlight this star and all stars before it
                const value = parseInt(this.dataset.value);
                const stars = container.querySelectorAll(".rating-star");
                stars.forEach((s, index) => {
                    if (index + 1 <= value) {
                        s.classList.add("hover");
                    } else {
                        s.classList.remove("hover");
                    }
                });
            });

            star.addEventListener("mouseout", function () {
                // Remove hover class from all stars
                const stars = container.querySelectorAll(".rating-star");
                stars.forEach((s) => {
                    s.classList.remove("hover");
                });
            });

            // Handle click to submit rating
            star.addEventListener("click", function () {
                const value = parseInt(this.dataset.value);

                // Add visual feedback immediately
                const stars = container.querySelectorAll(".rating-star");
                stars.forEach((s, index) => {
                    if (index + 1 <= value) {
                        s.classList.add("active");
                    } else {
                        s.classList.remove("active");
                    }
                });

                // Submit the rating with debounce to prevent multiple submissions
                debouncedSubmitServiceRating(serviceId, value);
            });

            container.appendChild(star);
        }
    });
};

export { initSingleServiceRating, submitServiceRating, updateServiceRating };
