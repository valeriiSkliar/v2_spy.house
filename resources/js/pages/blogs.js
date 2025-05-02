import {
    initUniversalCommentForm,
    initReplyButtons,
} from "@/components/blog-comments";
import { initCommentPagination } from "@/components/blog-comment-pagination";
document.addEventListener("DOMContentLoaded", function () {
    const commentForm = $("#universal-comment-form");
    if (commentForm.length) {
        // initUniversalCommentForm(commentForm);
        initReplyButtons(commentForm);
        initCommentPagination();
    }
});
