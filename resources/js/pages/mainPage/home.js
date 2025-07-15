jQuery(document).ready(function () {
  //------------Scroll page START
  function onScroll() {
    if ($(document).scrollTop() > 0) {
      $('.header._home').addClass('active');
    } else {
      $('.header._home').removeClass('active');
    }
  }
  onScroll();
  $(document).scroll(function () {
    onScroll();
  });
  //------------ END
  //------------h1 slider START
  // Проверяем существование переменной points перед инициализацией TypeIt
  if (typeof points !== 'undefined' && $('#typeit').length > 0) {
    $('#typeit').typeIt({
      strings: points,
      speed: 120,
      startDelay: 1000,
      cursor: false,
      breakLines: false,
      loop: true,
      deleteDelay: 1000,
    });
  }
  //------------h1 slider END

  //------------Blog slider START
  $('.reviews-slider').slick({
    slidesToShow: 3,
    slidesToScroll: 1,
    dots: true,
    arrows: false,
    autoplay: true,
    speed: 600,
    autoplaySpeed: 1500,
    adaptiveHeight: false,
    infinite: true,
    centerMode: false,
    centerPadding: '0',
    pauseOnFocus: true,
    pauseOnHover: true,
    responsive: [
      {
        breakpoint: 1200,
        settings: {
          slidesToShow: 2,
        },
      },
      {
        breakpoint: 767,
        settings: {
          slidesToShow: 1,
        },
      },
    ],
  });
  $('.reviews-slider').on('beforeChange', function (event, slick, currentSlide, nextSlide) {
    $('.review__txt-in').addClass('hidden');
    $('.review__txt-toggle').removeClass('active');
  });
  $('.review__txt-in').each(function () {
    if ($(this).outerHeight() > 50) {
      $(this).closest('.review').addClass('hidden-txt js-toggle-review');
      $(this).addClass('hidden');
      $(this).after('<span class="review__txt-toggle icon-down"></span>');
    }
  });
  $('body').on('click', '.js-toggle-review', function () {
    $(this).find('.review__txt-in').toggleClass('hidden');
    $(this).find('.review__txt-toggle').toggleClass('active');
  });
  //------------ END

  //------------Review stars START
  $('.review__rating').starRating({
    readOnly: true,
    emptyColor: '#EFF3F8',
    strokeColor: '#29B877',
    strokeWidth: 0,
    starSize: 15,
    hoverColor: '#29B877',
    starGradient: {
      start: '#29B877',
      end: '#29B877',
    },
  });
  //------------ END

  //------------Blog slider START
  $('.blog-home-slider').slick({
    slidesToShow: 3,
    slidesToScroll: 1,
    dots: true,
    arrows: false,
    autoplay: true,
    speed: 600,
    autoplaySpeed: 1500,
    adaptiveHeight: false,
    infinite: true,
    centerMode: false,
    centerPadding: '0',
    pauseOnFocus: true,
    pauseOnHover: true,
    responsive: [
      {
        breakpoint: 1200,
        settings: {
          slidesToShow: 2,
        },
      },
      {
        breakpoint: 767,
        settings: {
          slidesToShow: 1,
        },
      },
    ],
  });
  //------------ END

  //------------liMarquee START
  $('.creatives-marquee').mqScroller({
    htmlDir: 'auto',
    loop: true,
    duration: 9000,
    direction: 'left',
    gap: 0,
    pauseOnHover: false,
    separator: '',
    cloneCount: 3,
  });
  $('.creatives-tags-marquee').mqScroller({
    htmlDir: 'auto',
    loop: true,
    duration: 9000,
    direction: 'right',
    gap: 0,
    pauseOnHover: false,
    separator: '',
    cloneCount: 3,
  });
  //------------ END

  //------------AOS Animation START
  // Проверяем, что AOS доступен глобально и есть элементы для анимации
  if (typeof window.AOS !== 'undefined' && $('[data-aos]').length > 0) {
    window.AOS.init({
      easing: 'ease',
      duration: 1000,
      once: true,
    });
  } else if ($('[data-aos]').length > 0) {
    // Если AOS не загружен, но элементы есть, ждем загрузки
    console.warn('AOS library not loaded yet, retrying...');
    setTimeout(function () {
      if (typeof window.AOS !== 'undefined') {
        window.AOS.init({
          easing: 'ease',
          duration: 1000,
          once: true,
        });
      }
    }, 100);
  }
  //------------ END
});
