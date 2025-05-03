function setCommentMode(e) {
    e.preventDefault();
    const form = $(e.target).closest(".comment-ajax-form");
    const replyInfo = form.find(".reply-info");
    if (replyInfo.length) {
        replyInfo.hide();
    }
    const cancelReply = form.find(".cancel-reply-container");
    if (cancelReply.length) {
        cancelReply.css("display", "none");
        cancelReply.hide();
    }
    const regularCommentLabel = form.find("#regular-comment-label");
    if (regularCommentLabel.length) {
        regularCommentLabel.css("visibility", "visible");
        regularCommentLabel.css("max-height", "fit-content");
    }
    form.find("#comment-parent-id").val("");
}

function setReplyMode(parentId, authorName, commentForm) {
    const form = commentForm;
    const regularCommentLabel = form.find("#regular-comment-label");
    if (regularCommentLabel.length) {
        regularCommentLabel.css("visibility", "hidden");
        regularCommentLabel.css("max-height", "0");
    }
    form.find("#comment-parent-id").val(parentId);
    form.find("#comment-parent-id").val(parentId);

    const replyInfo = form.find(".reply-info");
    if (replyInfo.length) {
        replyInfo.css("display", "block");
        replyInfo.show();
    }
    form.find("#reply-to-author").text(authorName);

    const cancelReply = form.find(".cancel-reply-container");
    if (cancelReply.length) {
        cancelReply.css("display", "block");
        cancelReply.show();
        cancelReply.on("click", setCommentMode);
    }
}

const replyClickHandler = (e) => {
    e.preventDefault();
    const commentId = $(e.target).data("comment-id");
    const authorName = $(e.target).data("author-name");
    const regularCommentLabel = $(e.target)
        .closest(".universal-comment-form")
        .find(".regular-comment-label");
    if (regularCommentLabel.length) {
        regularCommentLabel.hide();
    }

    const commentForm = $("#universal-comment-form");
    if (commentForm.length) {
        // setReplyMode(articleSlug, commentId, authorName);
        $("html, body")
            .animate(
                {
                    scrollTop: commentForm.offset().top - 200,
                },
                500
            )
            .after(setReplyMode(commentId, authorName, commentForm));
    }
};

export function initUniversalCommentForm(commentForm) {
    if (!commentForm) return;
    // add of before submit event
    commentForm.on("submit", function (e) {
        // e.preventDefault();
        // const formData = new FormData(this);
        // const url = $(this).attr("action");
        // const method = $(this).attr("method");
        // const data = Object.fromEntries(formData);
        // console.log({ data, url, method });
    });
}

export function initReplyButtons(commentForm) {
    if (!commentForm) return;

    const replyButtons = $(".reply-btn");
    if (!replyButtons) return;
    replyButtons.each(function () {
        const button = $(this);
        button.off("click", replyClickHandler);
        button.on("click", replyClickHandler);
    });
}
