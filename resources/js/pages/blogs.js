import {
    initUniversalCommentForm,
    initReplyButtons,
} from "@/components/blog-comments";
import { initBlogRating } from "@/components/blog-rating";
import { initCommentPagination } from "@/components/blog-comment-pagination";
document.addEventListener("DOMContentLoaded", function () {
    const commentForm = $("#universal-comment-form");
    if (commentForm.length) {
        // initUniversalCommentForm(commentForm);
        initReplyButtons(commentForm);
        initCommentPagination();
        initBlogRating();
    }
});
