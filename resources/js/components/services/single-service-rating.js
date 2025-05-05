import { createAndShowToast } from "@/utils";

const updateServiceRating = (rating, userRating = null) => {
    const ratingValue = document.querySelector(
        ".rate-service__value .font-weight-600"
    );
    ratingValue.textContent = rating;

    const metaRating = document.querySelector(".icon-rating");
    if (metaRating) {
        metaRating.textContent = rating;
    }

    // Update all rating elements on the page
    const ratingElements = document.querySelectorAll(
        ".rate-service__item.icon-rating"
    );
    ratingElements.forEach((element) => {
        element.textContent = rating;
    });

    // Show message about user's rating if provided
    if (userRating) {
        const ratingContainer = document.querySelector(".rate-service");
        if (ratingContainer) {
            // Hide the rating form
            ratingContainer.innerHTML = `
                <div class="rate-service__success">
                    <p class="mb-0 font-18 font-weight-600">Thank you for rating!</p>
                    <p class="mb-0">You rated this service: ${userRating} stars</p>
                    <p class="mb-0">Average rating: ${rating}</p>
                </div>
            `;
        }
    }
};

export function submitServiceRating(serviceId, rating) {
    fetch(`/services/${serviceId}/rate`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                .content,
        },
        body: JSON.stringify({ rating: rating }),
    })
        .then((response) => {
            if (response.redirected && response.url.includes("login")) {
                window.location.href = response.url;
                return;
            }
            return response.json();
        })
        .then((data) => {
            if (data.success) {
                // Update UI with average rating and user rating
                updateServiceRating(data.average_rating, data.user_rating);

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
            createAndShowToast(
                "Error saving rating. Please try again.",
                "error"
            );
        });
}

const initSingleServiceRating = (ratingContainersElement = null) => {
    const isSingleMarket = $(".single-market").length > 0;
    if (!isSingleMarket) {
        return;
    }

    const serviceId = $(".single-market").data("service-id");
    console.log(serviceId);
    const ratingContainers =
        ratingContainersElement ||
        document.querySelectorAll(".rate-service__rating");

    if (ratingContainers.length > 0) {
        ratingContainers.forEach((container) => {
            const currentRating = parseInt(container.dataset.rating) || 0;
            const isRated = container.dataset.isRated === "true";

            if (isRated) {
                return;
            }
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
                    submitServiceRating(serviceId, value);
                });

                container.appendChild(star);
            }
        });
    }
    //--------Market single------------------------------------
    // if ($(".rate-service__rating").length > 0) {
    //     $(".rate-service__rating").starRating({
    //         emptyColor: "#DCEAE4",
    //         strokeColor: "#DCEAE4",
    //         hoverColor: "#3DC98A",
    //         useFullStars: true,
    //         ratedColor: "#3DC98A",
    //         strokeWidth: 0,
    //         starSize: 25,
    //         disableAfterRate: true,
    //         starShape: "rounded",
    //         callback: function (currentRating, $el) {},
    //     });
    // }
};

export { initSingleServiceRating };
