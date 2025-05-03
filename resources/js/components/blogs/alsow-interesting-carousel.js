const initAlsowInterestingArticlesCarousel = () => {
    const carouselContainer = $(
        "#alsow-interesting-articles-carousel-container"
    );

    if (carouselContainer) {
        if ($("#alsow-interesting-articles-carousel-container").length > 0) {
            $("#alsow-interesting-articles-carousel-container").slick({
                dots: false,
                infinite: true,
                speed: 300,
                slidesToShow: 1,
                slidesToScroll: 1,
                prevArrow: "#alsow-interesting-articles-carousel-prev",
                nextArrow: "#alsow-interesting-articles-carousel-next",
                variableWidth: true,
            });
        }
    }
};

const initReadOftenArticlesCarousel = () => {
    const carouselContainer = $("#read-often-articles-carousel-container");

    if (carouselContainer) {
        if ($("#read-often-articles-carousel-container").length > 0) {
            $("#read-often-articles-carousel-container").slick({
                dots: false,
                infinite: true,
                speed: 300,
                slidesToShow: 1,
                slidesToScroll: 1,
                prevArrow: "#read-often-articles-carousel-prev",
                nextArrow: "#read-often-articles-carousel-next",
            });
        }
    }
};

export { initAlsowInterestingArticlesCarousel, initReadOftenArticlesCarousel };
