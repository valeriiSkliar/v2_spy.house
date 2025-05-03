export const initBlogRating = () => {
    console.log("initBlogRating");
};

document.addEventListener("DOMContentLoaded", function () {
    //         $(".article-rate__rating").starRating({
    //             initialRating: {{ $article['user_rating'] ?? 0 }},
    //             strokeColor: '#894A00',
    //             strokeWidth: 10,
    //             starSize: 25,3
    //             disableAfterRate: false,
    //             useFullStars: true,
    //             hoverColor: '#ffb700',
    //             activeColor: '#ffb700',
    //             ratedColor: '#ffb700',
    //             useGradient: false,
    //             callback: function(currentRating, $el) {
    //                 // Отправка рейтинга на сервер через AJAX
    //                 $.ajax({
    //                     url: "{{ route('blog.rate', $article['slug']) }}",
    //                     type: "POST",
    //                     data: {
    //                         rating: currentRating,
    //                         _token: "{{ csrf_token() }}"
    //                     },
    //                     success: function(response) {
    //                         if (response.success) {
    //                             $(".article-rate__value .font-weight-600").text(response.rating);
    //                         }
    //                     },
    //                     error: function() {
    //                         alert("Error saving rating. Please try again.");
    //                     }
    //                 });
    //             }
    //         });
    //     $(document).ready(function() {
    //         $('.category-link').click(function() {
    //             var color = $(this).data('color');
    //             $(this).css('color', color);
    //         });
    //     });
    //     $(document).ready(function() {
    //         $('.cat-links').click(function() {
    //             var color = $(this).data('color');
    //             $(this).css('color', color);
    //         });
    //     });
});
