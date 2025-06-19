import { createAndShowToast } from '@/utils';
import { blogAPI } from '../fetcher/ajax-fetcher';

const updateRating = (rating, userRating = null) => {
  const ratingValue = document.querySelector('.article-rate__value .font-weight-600');
  ratingValue.textContent = rating;

  const metaRating = document.querySelector('.icon-rating');
  if (metaRating) {
    metaRating.textContent = rating;
  }

  // Update all rating elements on the page
  const ratingElements = document.querySelectorAll('.article-info__item.icon-rating');
  ratingElements.forEach(element => {
    element.textContent = rating;
  });

  // Show message about user's rating if provided
  if (userRating) {
    const ratingContainer = document.querySelector('.article-rate');
    if (ratingContainer) {
      // Hide the rating form
      ratingContainer.innerHTML = `
                <div class="article-rate__success">
                    <p class="mb-0 font-18 font-weight-600">Thank you for rating!</p>
                    <p class="mb-0">You rated this article: ${userRating} stars</p>
                    <p class="mb-0">Average rating: ${rating}</p>
                </div>
            `;
    }
  }
};

export function submitRating(slug, rating) {
  blogAPI
    .submitRating(slug, rating)
    .then(data => {
      if (data.success) {
        // Update UI with average rating and user rating
        updateRating(data.average_rating, data.user_rating);
        // Show success message
        createAndShowToast(data.message || 'Thank you for rating!', 'success');
      } else {
        createAndShowToast(data.message || 'Error saving rating. Please try again.', 'error');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      const errorMessage = error.message || 'Error saving rating. Please try again.';
      createAndShowToast(errorMessage, 'error');
    });
}

export function initRating(ratingContainersElement = null) {
  const ratingContainers =
    ratingContainersElement || document.querySelectorAll('.article-rate__rating');

  if (ratingContainers.length > 0) {
    ratingContainers.forEach(container => {
      const articleSlug = container.dataset.slug;
      const currentRating = parseInt(container.dataset.rating) || 0;
      const isRated = container.dataset.isRated === 'true';

      // Don't initialize rating stars if already rated
      if (isRated) {
        return;
      }

      // Create stars
      for (let i = 1; i <= 5; i++) {
        const star = document.createElement('span');
        star.classList.add('rating-star');
        star.dataset.value = i;
        if (i <= currentRating) {
          star.classList.add('active');
        }
        star.innerHTML = '★';

        star.addEventListener('click', function () {
          const value = this.dataset.value;
          submitRating(articleSlug, value);
        });

        container.appendChild(star);
      }
    });
  }
}

document.addEventListener('DOMContentLoaded', function () {
  initRating();
});
