import { createAndShowToast } from "./utils";

const updateRating = (rating) => {
    const ratingValue = document.querySelector(
        ".article-rate__value .font-weight-600"
    );
    ratingValue.textContent = rating;
    const metaRating = $(".icon-rating");
    if (metaRating.length > 0) {
        metaRating.text(rating);
    }
};

export function submitRating(slug, rating) {
    // Fetch API for AJAX request
    try {
        // throw new Error("Test error");
        fetch(`/blog/${slug}/rate`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]'
                ).content,
            },
            body: JSON.stringify({ rating: rating }),
        })
            .then((response) => {
                if (response.redirected && response.url.includes("login")) {
                    console.log("Redirected to login");
                    return;
                }
                return response.json();
            })
            .then((data) => {
                if (data.success) {
                    // Update UI
                    document.querySelector(
                        ".article-rate__value .font-weight-600"
                    ).textContent = data.rating;

                    updateRating(data.rating);

                    // Update stars
                    const stars = document.querySelectorAll(".rating-star");
                    stars.forEach((star) => {
                        if (star.dataset.value <= rating) {
                            star.classList.add("active");
                        } else {
                            star.classList.remove("active");
                        }
                    });
                }
            })
            .catch((error) => {
                console.error("Error:", error);
                createAndShowToast(
                    "Error saving rating. Please try again.",
                    "error"
                );
                // alert("Error saving rating. Please try again.");
            });
    } catch (error) {
        console.error("Error:", error);
        createAndShowToast("Error saving rating. Please try again.", "error");
    }
}

export function initRating(ratingContainersElement = null) {
    const ratingContainers = ratingContainersElement.length
        ? ratingContainersElement
        : document.querySelectorAll(".article-rate__rating");

    if (ratingContainers.length > 0) {
        ratingContainers.forEach((container) => {
            const articleSlug = container.dataset.slug;
            const currentRating = parseInt(container.dataset.rating) || 0;

            // Create stars
            for (let i = 1; i <= 5; i++) {
                const star = document.createElement("span");
                star.classList.add("rating-star");
                star.dataset.value = i;
                if (i <= currentRating) {
                    star.classList.add("active");
                }
                star.innerHTML = "★";

                star.addEventListener("click", function () {
                    const value = this.dataset.value;
                    submitRating(articleSlug, value);
                });

                container.appendChild(star);
            }
        });
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const ratingContainers = document.querySelectorAll(".article-rate__rating");
    initRating(ratingContainers);
});
