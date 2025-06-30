document.addEventListener(
  'DOMContentLoaded',
  function () {
    jQuery(document).ready(function () {
      //$('#subscription-activated-modal').modal('show');

      //--------1111111------------------------------------

      //--------rating------------------------------------
      // if ($('.article-rate__rating').length > 0) {
      //     $(".article-rate__rating").starRating({
      //         emptyColor: '#CEF0DE',
      //         strokeColor: '#CEF0DE',
      //         hoverColor: '#3DC98A',
      //         useFullStars: true,
      //         ratedColor: '#3DC98A',
      //         strokeWidth: 0,
      //         starSize: 40,
      //         disableAfterRate: true,
      //         //starShape: 'rounded',
      //         callback: function (currentRating, $el) {

      //         }
      //     });
      // }

      // TODO: REFACTOR this code
      //--------slick------------------------------------
      // if ($("#slick-demo-1").length > 0) {
      //     $("#slick-demo-1").slick({
      //         dots: false,
      //         infinite: true,
      //         speed: 300,
      //         slidesToShow: 1,
      //         slidesToScroll: 1,
      //         prevArrow: "#slick-demo-1-prev",
      //         nextArrow: "#slick-demo-1-next",
      //         rows: 4,
      //         responsive: [
      //             {
      //                 breakpoint: 1200,
      //                 settings: {
      //                     variableWidth: true,
      //                     rows: 1,
      //                 },
      //             },
      //         ],
      //     });
      // }
      // if ($("#slick-demo-2").length > 0) {
      //     $("#slick-demo-2").slick({
      //         dots: false,
      //         infinite: true,
      //         speed: 300,
      //         slidesToShow: 1,
      //         slidesToScroll: 1,
      //         prevArrow: "#slick-demo-2-prev",
      //         nextArrow: "#slick-demo-2-next",
      //         variableWidth: true,
      //     });
      // }
      //--------Aside blog------------------------------------

      // if ($('.blog-layout__aside').length > 0){
      //     $('.blog-layout__aside').stickySidebar({
      //         topSpacing: 20,
      //         bottomSpacing: 10,
      //         resizeSensor: true,
      //         minWidth: 1200
      //     });
      // }

      //--------Search------------------------------------
      $('.js-toggle-search').click(function () {
        $('.search-form, .search-bg').addClass('active');
      });
      $('.search-bg').click(function () {
        $('.search-form, .search-bg').removeClass('active');
      });

      //--------1111111------------------------------------
      // $('.creative-video').hover(
      //   function () {
      //     let video = $('.creative-video__content', this).data('video');
      //     $('.creative-video__content', this).html(
      //       '<video loop="loop" autoplay muted="muted" webkit-playsinline playsinline controls><source type="video/mp4" src="' +
      //         video +
      //         '"></video>'
      //     );
      //   },
      //   function () {
      //     $('.creative-video__content', this).html(' ');
      //   }
      // );

      //--------Toggle tariff------------------------------------
      // $('body').on('click', '.js-toggle-rate', function () {
      //   $(this).toggleClass('show-all');
      //   $('.rate-item-body._fixed').toggleClass('show-all');
      //   $('.rate-item-body__hidden').slideToggle(300);
      //   if ($(this).hasClass('show-all')) {
      //     $('.btn__text', this).html($(this).data('hide'));
      //   } else {
      //     $('.btn__text', this).html($(this).data('show'));
      //   }
      // });

      //--------Switch password------------------------------------
      $('body').on('click', '[data-pass-switch]', function (e) {
        e.preventDefault();
        $(this).toggleClass('active');
        var current = $(this).data('pass-switch');
        if ($(this).hasClass('active')) {
          $('[data-pass="' + current + '"]').attr('type', 'text');
        } else {
          $('[data-pass="' + current + '"]').attr('type', 'password');
        }
      });

      //--------Tubs------------------------------------
      $('body').on('click', 'a[data-tub], button[data-tub]', function (e) {
        e.preventDefault();
        let current = $(this).data('tub');
        let group = $(this).data('group');
        $('[data-group="' + group + '"]').removeClass('active');
        $('[data-tub="' + current + '"][data-group="' + group + '"]').addClass('active');
      });

      //--------copy btn------------------------------------
      let timer_copy_clipboard;
      $('body').on('click', '.js-copy', function () {
        let current = $(this);
        clearTimeout(timer_copy_clipboard);
        current.addClass('copied-success');
        timer_copy_clipboard = setTimeout(function () {
          current.removeClass('copied-success');
        }, 500);
      });

      //--------Show details------------------------------------
      $('.js-show-details').click(function () {
        $('.creatives-list__details').addClass('show-details');
      });
      $('.js-hide-details').click(function () {
        $('.creatives-list__details').removeClass('show-details');
      });

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
      $('body').on('click', '.js-toggle-txt', function () {
        $(this).toggleClass('active');
        $(this).closest('.hidden-txt').find('.hidden-txt__content').toggleClass('active');
        if ($(this).hasClass('active')) {
          $(this).html($(this).data('hide'));
        } else {
          $(this).html($(this).data('show'));
        }
      });
      $('.js-toggle-code').click(function () {
        $(this).closest('.single-market__code').addClass('show-code');
      });

      //--------Filter------------------------------------
      $('.filter__trigger-mobile').click(function () {
        $('.btn-icon', this).toggleClass('active');
        $(this).closest('.filter').find('.filter__content').slideToggle(300);
      });
      $('.js-toggle-detailed-filtering').click(function () {
        $(this).toggleClass('active');
        $(this).closest('.filter').find('.filter__detailed').slideToggle(300);
      });

      //--------Aside mobile------------------------------------
      $('.js-menu').click(function () {
        $('.aside, .navigation-bg, .menu-burger').toggleClass('active');
      });
      $('.navigation-bg').click(function () {
        $('.aside, .navigation-bg, .menu-burger').removeClass('active');
      });

      //--------User menu------------------------------------
      $('.user-preview__trigger').click(function () {
        let current = $(this).closest('.user-preview');
        current.toggleClass('open');
        current.find('.user-preview__dropdown').slideToggle(200);
      });
      $(document).on('click', function (e) {
        let el = '.user-preview';
        if ($(e.target).closest(el).length) return;
        $('.user-preview').removeClass('open');
        $('.user-preview__dropdown').slideUp(200);
      });

      //--------Select from DEV------------------------------------
      // $(".base-select, .date-picker-container, .multi-select").click(
      //     function () {
      //         let current = $(this);
      //         if (
      //             !current
      //                 .find(
      //                     ".base-select__trigger, .date-select-field, .multi-select__tags"
      //                 )
      //                 .hasClass("is-open")
      //         ) {
      //             $(
      //                 ".base-select__trigger, .base-select__arrow, .date-select-field, .multi-select__tags, .multi-select__arrow"
      //             ).removeClass("is-open");
      //             $(
      //                 ".base-select__dropdown, .date-options-dropdown, .multi-select__dropdown"
      //             ).slideUp(200);
      //             current
      //                 .find(
      //                     ".base-select__trigger, .base-select__arrow, .date-select-field, .multi-select__tags, .multi-select__arrow"
      //                 )
      //                 .addClass("is-open");
      //             current
      //                 .find(
      //                     ".base-select__dropdown, .date-options-dropdown, .multi-select__dropdown"
      //                 )
      //                 .slideDown(200);
      //         } else {
      //             $(
      //                 ".base-select__trigger, .base-select__arrow, .date-select-field, .multi-select__tags, .multi-select__arrow"
      //             ).removeClass("is-open");
      //             $(
      //                 ".base-select__dropdown, .date-options-dropdown, .multi-select__dropdown"
      //             ).slideUp(200);
      //         }
      //     }
      // );
      $(document).on('click', function (e) {
        let el = '.base-select, .date-picker-container, .multi-select';
        if ($(e.target).closest(el).length) return;

        // Закрываем только элементы без кастомной обработки
        $('.base-select__trigger, .base-select__arrow').each(function () {
          const $trigger = $(this);
          const $select = $trigger.closest('.base-select');

          // Пропускаем элементы с кастомной обработкой
          if (!$select.hasClass('js-custom-handling')) {
            $trigger.removeClass('is-open');
          }
        });

        $('.date-select-field, .multi-select__tags, .multi-select__arrow').removeClass('is-open');
        $('.date-options-dropdown, .multi-select__dropdown').slideUp(200);

        // Закрываем только base-select dropdown'ы без кастомной обработки
        $('.base-select__dropdown').each(function () {
          const $dropdown = $(this);
          const $select = $dropdown.closest('.base-select');

          if (!$select.hasClass('js-custom-handling')) {
            $dropdown.slideUp(200);
          }
        });
      });
    });
  },
  false
);
