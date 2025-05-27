import {
  initAlsowInterestingArticlesCarousel,
  initBlogSearch,
  initCommentPagination,
  initReadOftenArticlesCarousel,
  initReplyButtons,
  initUniversalCommentForm,
} from '@/components/blogs';

document.addEventListener('DOMContentLoaded', function () {
  const commentForm = $('#universal-comment-form');
  if (commentForm.length) {
    initReplyButtons(commentForm);
    initUniversalCommentForm(commentForm);
  }
  initCommentPagination();
  initAlsowInterestingArticlesCarousel();
  initReadOftenArticlesCarousel();
  initBlogSearch();
});
