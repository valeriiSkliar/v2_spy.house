import { initReplyButtons } from "@/components/blogs";
document.addEventListener("DOMContentLoaded", function () {
    initCommentPagination();
});

function initCommentPagination() {
    const commentsList = $(".comment-list");
    const paginationLinks = $(".pagination-link");
    const commentsContainer = $("#comments");

    if (!commentsList || !commentsContainer) return;

    paginationLinks.each(function () {
        if ($(this).hasClass("disabled")) return;

        $(this).on("click", function (e) {
            e.preventDefault();

            const slug = window.location.pathname.split("/").pop();
            let page = this.dataset.page;

            // For number pagination links
            if (!isNaN(parseInt(page))) {
                loadComments(slug, parseInt(page));
                return;
            }

            // Handle prev/next links that have URLs
            if (page && page.includes("?")) {
                const urlParams = new URLSearchParams(page.split("?")[1]);
                const pageParam = urlParams.get("page");
                if (pageParam) {
                    loadComments(slug, parseInt(pageParam));
                    return;
                }
            }

            // Default to page 1 if we couldn't extract a page number
            loadComments(slug, 1);
        });
    });
}

function loadComments(slug, page) {
    const commentForm = $(".universal-comment-form");
    const commentsList = $(".comment-list");
    const paginationContainer = $(".pagination-nav");

    // Show loading indicator that matches site styling
    commentsList.html(
        '<div class="text-center py-4"><div class="message _bg _with-border">Loading comments...</div></div>'
    );

    fetch(`/blog/${slug}/comments?page=${page}`)
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                // Update comments
                $(commentsList).html(data.commentsHtml);

                // Update pagination
                $(paginationContainer).html(data.paginationHtml);

                // Reinitialize pagination after DOM update
                initCommentPagination();

                // Reinitialize reply buttons
                initReplyButtons(commentForm);

                // Scroll to comments section
                document
                    .getElementById("comments")
                    .scrollIntoView({ behavior: "smooth" });
            } else {
                commentsList.html(
                    '<div class="message _bg _with-border _red">Error loading comments. Please try again.</div>'
                );
            }
        })
        .catch((error) => {
            console.error("Error:", error);
            commentsList.html(
                '<div class="message _bg _with-border _red">Error loading comments. Please try again.</div>'
            );
        });
}

export { initCommentPagination, loadComments };
