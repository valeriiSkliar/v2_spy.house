import {
    initReplyButtons,
    initCommentPagination,
    initAlsowInterestingArticlesCarousel,
    initReadOftenArticlesCarousel,
} from "@/components/blogs";

document.addEventListener("DOMContentLoaded", function () {
    const commentForm = $("#universal-comment-form");
    if (commentForm.length) {
        initReplyButtons(commentForm);
    }
    initCommentPagination();
    initAlsowInterestingArticlesCarousel();
    initReadOftenArticlesCarousel();
});
