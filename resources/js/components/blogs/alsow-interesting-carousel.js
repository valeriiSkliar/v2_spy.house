const initAlsowInterestingArticlesCarousel = () => {
    const carouselContainer = $("#alsow-interesting-articles-carousel-container");

    if (carouselContainer.length > 0) {
        // Check if slick is already initialized
        if (carouselContainer.hasClass('slick-initialized')) {
            return;
        }

        // Verify required elements exist
        const prevArrow = $("#alsow-interesting-articles-carousel-prev");
        const nextArrow = $("#alsow-interesting-articles-carousel-next");
        
        if (prevArrow.length > 0 && nextArrow.length > 0) {
            try {
                carouselContainer.slick({
                    dots: false,
                    infinite: true,
                    speed: 300,
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    prevArrow: "#alsow-interesting-articles-carousel-prev",
                    nextArrow: "#alsow-interesting-articles-carousel-next",
                    variableWidth: true,
                });
            } catch (error) {
                console.error('Error initializing alsow interesting articles carousel:', error);
            }
        }
    }
};

const initReadOftenArticlesCarousel = () => {
    const carouselContainer = $("#read-often-articles-carousel-container");

    if (carouselContainer.length > 0) {
        // Check if slick is already initialized
        if (carouselContainer.hasClass('slick-initialized')) {
            return;
        }

        // Verify required elements exist
        const prevArrow = $("#read-often-articles-carousel-prev");
        const nextArrow = $("#read-often-articles-carousel-next");
        
        if (prevArrow.length > 0 && nextArrow.length > 0) {
            try {
                carouselContainer.slick({
                    dots: false,
                    infinite: true,
                    speed: 300,
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    prevArrow: "#read-often-articles-carousel-prev",
                    nextArrow: "#read-often-articles-carousel-next",
                });
            } catch (error) {
                console.error('Error initializing read often articles carousel:', error);
            }
        }
    }
};

export { initAlsowInterestingArticlesCarousel, initReadOftenArticlesCarousel };
