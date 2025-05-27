import { ajaxFetcher } from '@/components/fetcher/ajax-fetcher';
import { logger } from '@/helpers/logger';
import { createAndShowToast } from '@/utils/uiHelpers';

function setCommentMode(e) {
  e.preventDefault();
  const form = $(e.target).closest('.comment-ajax-form');
  const replyInfo = form.find('.reply-info');
  if (replyInfo.length) {
    replyInfo.hide();
  }
  const cancelReply = form.find('.cancel-reply-container');
  if (cancelReply.length) {
    cancelReply.css('display', 'none');
    cancelReply.hide();
  }
  const regularCommentLabel = form.find('#regular-comment-label');
  if (regularCommentLabel.length) {
    regularCommentLabel.css('visibility', 'visible');
    regularCommentLabel.css('max-height', 'fit-content');
  }
  form.find('#comment-parent-id').val('');
}

function setReplyMode(parentId, authorName, commentForm) {
  const form = commentForm;
  const regularCommentLabel = form.find('#regular-comment-label');
  if (regularCommentLabel.length) {
    regularCommentLabel.css('visibility', 'hidden');
    regularCommentLabel.css('max-height', '0');
  }
  form.find('#comment-parent-id').val(parentId);
  form.find('#comment-parent-id').val(parentId);

  const replyInfo = form.find('.reply-info');
  if (replyInfo.length) {
    replyInfo.css('display', 'block');
    replyInfo.show();
  }
  form.find('#reply-to-author').text(authorName);

  const cancelReply = form.find('.cancel-reply-container');
  if (cancelReply.length) {
    cancelReply.css('display', 'block');
    cancelReply.show();
    cancelReply.on('click', setCommentMode);
  }
}

const replyClickHandler = e => {
  e.preventDefault();
  const commentId = $(e.target).data('comment-id');
  const authorName = $(e.target).data('author-name');
  const regularCommentLabel = $(e.target)
    .closest('.universal-comment-form')
    .find('.regular-comment-label');
  if (regularCommentLabel.length) {
    regularCommentLabel.hide();
  }

  const commentForm = $('#universal-comment-form');
  if (commentForm.length) {
    // setReplyMode(articleSlug, commentId, authorName);
    $('html, body')
      .animate(
        {
          scrollTop: commentForm.offset().top - 200,
        },
        500
      )
      .after(setReplyMode(commentId, authorName, commentForm));
  }
};

function validateCommentForm(form) {
  const textarea = form.find('textarea[name="content"]');
  const content = textarea.val().trim();

  // Clear previous errors
  textarea.removeClass('error');

  if (!content) {
    textarea.addClass('error');
    createAndShowToast('Пожалуйста, введите текст комментария', 'error');
    return false;
  }

  if (content.length < 2) {
    textarea.addClass('error');
    createAndShowToast('Комментарий должен содержать минимум 2 символа', 'error');
    return false;
  }

  if (content.length > 1000) {
    textarea.addClass('error');
    createAndShowToast('Комментарий не должен превышать 1000 символов', 'error');
    return false;
  }

  return true;
}

export function initUniversalCommentForm(commentForm) {
  logger('initUniversalCommentForm', commentForm, { debug: true });
  if (!commentForm) return;

  commentForm.on('submit', function (e) {
    e.preventDefault();

    const form = $(this);
    const submitButton = form.find('button[type="submit"]');

    // Validate form
    if (!validateCommentForm(form)) {
      return;
    }

    // Disable submit button to prevent double submission
    submitButton.prop('disabled', true);
    const originalButtonText = submitButton.html();
    submitButton.html('<i class="fas fa-spinner fa-spin"></i> Отправка...');

    const formData = new FormData(this);
    const url = form.attr('action');

    ajaxFetcher.submit(url, {
      data: formData,
      successCallback: function (response) {
        if (response.success) {
          createAndShowToast(response.message, 'success');

          // Clear form
          form.find('textarea[name="content"]').val('').removeClass('error');
          form.find('#comment-parent-id').val('');

          // Reset form to comment mode (not reply)
          setCommentMode({ preventDefault: () => {}, target: form[0] });
        } else {
          createAndShowToast(response.message || 'Ошибка отправки комментария', 'error');
        }
      },
      errorCallback: function (jqXHR) {
        let errorMessage = 'Ошибка отправки комментария';

        if (jqXHR.responseJSON) {
          if (jqXHR.responseJSON.message) {
            errorMessage = jqXHR.responseJSON.message;
          } else if (jqXHR.responseJSON.errors) {
            // Handle validation errors
            const errors = jqXHR.responseJSON.errors;
            if (errors.content && errors.content.length > 0) {
              errorMessage = errors.content[0];
              form.find('textarea[name="content"]').addClass('error');
            } else {
              errorMessage = Object.values(errors).flat().join(', ');
            }
          }
        } else if (jqXHR.status === 429) {
          errorMessage = 'Слишком много запросов. Пожалуйста, подождите минуту.';
        } else if (jqXHR.status === 401) {
          errorMessage = 'Необходимо авторизоваться для отправки комментариев';
        }

        createAndShowToast(errorMessage, 'error');
      },
      completeCallback: function () {
        // Re-enable submit button
        submitButton.prop('disabled', false);
        submitButton.html(originalButtonText);
      },
    });
  });
}

export function initReplyButtons(commentForm) {
  logger('initReplyButtons', commentForm, { debug: true });
  if (!commentForm) return;

  const replyButtons = $('.reply-btn');
  if (!replyButtons) return;
  replyButtons.each(function () {
    const button = $(this);
    button.off('click', replyClickHandler);
    button.on('click', replyClickHandler);
  });
}
