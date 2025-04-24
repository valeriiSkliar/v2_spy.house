// resources/js/carousel.js
document.addEventListener('DOMContentLoaded', function() {
    // Simple carousel functionality
    const carousels = document.querySelectorAll('.carousel-container');
    
    carousels.forEach(carousel => {
        const id = carousel.id;
        const prevButton = document.getElementById(`${id}-prev`);
        const nextButton = document.getElementById(`${id}-next`);
        const items = carousel.querySelectorAll('.carousel-item');
        const itemWidth = items[0]?.offsetWidth || 0;
        let position = 0;
        let slidesToShow = 4;
        
        // Responsive slides to show
        function updateSlidesToShow() {
            if (window.innerWidth < 576) {
                slidesToShow = 1;
            } else if (window.innerWidth < 768) {
                slidesToShow = 2;
            } else if (window.innerWidth < 992) {
                slidesToShow = 3;
            } else {
                slidesToShow = 4;
            }
            
            // Reset position if needed
            if (position > items.length - slidesToShow) {
                position = items.length - slidesToShow;
                updateCarousel();
            }
        }
        
        updateSlidesToShow();
        window.addEventListener('resize', updateSlidesToShow);
        
        function updateCarousel() {
            carousel.style.transform = `translateX(-${position * (100 / slidesToShow)}%)`;
            
            // Update button states
            if (prevButton) {
                prevButton.disabled = position <= 0;
            }
            
            if (nextButton) {
                nextButton.disabled = position >= items.length - slidesToShow;
            }
        }
        
        if (prevButton) {
            prevButton.addEventListener('click', function() {
                if (position > 0) {
                    position--;
                    updateCarousel();
                }
            });
        }
        
        if (nextButton) {
            nextButton.addEventListener('click', function() {
                if (position < items.length - slidesToShow) {
                    position++;
                    updateCarousel();
                }
            });
        }
        
        // Initialize
        updateCarousel();
    });
});