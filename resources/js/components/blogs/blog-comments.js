import { hideInElement, showInElement } from '@/components/loader';
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

/**
 * Асинхронное обновление списка комментариев
 */
function reloadCommentsContent(container, slug, options = {}) {
  const { page = 1, sort = 'latest', showLoader = true, scrollToTop = true } = options;

  if (showLoader) {
    showInElement(container);
  }

  // Строим URL для получения комментариев
  const url = `/api/blog/${slug}/comments`;
  const params = new URLSearchParams({ page, sort });

  fetch(`${url}?${params.toString()}`, {
    method: 'GET',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN':
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
  })
    .then(response => {
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      return response.json();
    })
    .then(data => {
      if (data.success) {
        // Обновляем содержимое комментариев
        updateCommentsContent(data, container);

        // Обновляем счетчик комментариев
        updateCommentsCounter(data.commentsCount);

        // Переинициализируем компоненты
        reinitializeCommentsComponents();

        if (scrollToTop) {
          container.scrollIntoView({ behavior: 'smooth' });
        }
      } else {
        createAndShowToast(data.message || 'Ошибка загрузки комментариев', 'error');
      }
    })
    .catch(error => {
      console.error('Error loading comments:', error);
      createAndShowToast('Ошибка загрузки комментариев', 'error');
    })
    .finally(() => {
      if (showLoader) {
        hideInElement(container);
      }
    });
}

/**
 * Обновление содержимого комментариев в DOM
 */
function updateCommentsContent(data, container) {
  // Находим контейнер списка комментариев
  const commentsList = container.querySelector('.comment-list');
  if (commentsList && data.html) {
    commentsList.innerHTML = data.html;
  }

  // Обновляем пагинацию
  const paginationContainer = document.querySelector('#comments-pagination-container');
  if (paginationContainer) {
    if (data.hasPagination && data.pagination) {
      paginationContainer.innerHTML = data.pagination;
      paginationContainer.style.display = 'block';
    } else {
      paginationContainer.innerHTML = '';
      paginationContainer.style.display = 'none';
    }
  }
}

/**
 * Обновление счетчика комментариев
 */
function updateCommentsCounter(count) {
  const counterElement = document.querySelector('.comment-count');
  if (counterElement) {
    counterElement.textContent = count || 0;
  }
}

/**
 * Переинициализация компонентов после обновления DOM
 */
function reinitializeCommentsComponents() {
  const commentForm = $('#universal-comment-form');

  // Переинициализируем кнопки ответа
  initReplyButtons(commentForm);

  // Переинициализируем обработчики пагинации
  initCommentsPagination();
}

/**
 * Инициализация обработчиков пагинации комментариев
 */
function initCommentsPagination() {
  const paginationLinks = document.querySelectorAll(
    '#comments-pagination-container .pagination-link'
  );

  paginationLinks.forEach(link => {
    // Удаляем старые обработчики
    link.removeEventListener('click', handlePaginationClick);

    // Добавляем новые если ссылка не заблокирована
    if (!link.classList.contains('disabled')) {
      link.addEventListener('click', handlePaginationClick);
    }
  });
}

/**
 * Обработчик клика по пагинации
 */
function handlePaginationClick(e) {
  e.preventDefault();

  const link = e.currentTarget;
  const page = link.dataset.page;
  const slug = window.location.pathname.split('/').pop();
  const commentsContainer = document.getElementById('comments');

  if (page && commentsContainer) {
    let pageNumber = 1;

    // Извлекаем номер страницы
    if (!isNaN(parseInt(page))) {
      pageNumber = parseInt(page);
    } else if (page.includes('?')) {
      const urlParams = new URLSearchParams(page.split('?')[1]);
      const pageParam = urlParams.get('page');
      if (pageParam) {
        pageNumber = parseInt(pageParam);
      }
    }

    reloadCommentsContent(commentsContainer, slug, { page: pageNumber });
  }
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
    const actionUrl = form.attr('action');

    // Заменяем URL на API endpoint
    const slug = window.location.pathname.split('/').pop();
    const apiUrl = `/api/blog/${slug}/comment`;

    fetch(apiUrl, {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN':
          document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          createAndShowToast(data.message, 'success');

          // Очистка формы
          form.find('textarea[name="content"]').val('').removeClass('error');
          form.find('#comment-parent-id').val('');

          // Сброс формы в режим комментария (не ответа)
          setCommentMode({ preventDefault: () => {}, target: form[0] });

          // Асинхронное обновление списка комментариев
          const commentsContainer = document.getElementById('comments');
          if (commentsContainer && data.html) {
            updateCommentsContent(data, commentsContainer);
            updateCommentsCounter(data.commentsCount);
            reinitializeCommentsComponents();

            // Скролл к началу комментариев для показа нового комментария
            commentsContainer.scrollIntoView({ behavior: 'smooth' });
          }
        } else {
          createAndShowToast(data.message || 'Ошибка отправки комментария', 'error');

          if (data.errors) {
            const errors = data.errors;
            if (errors.content && errors.content.length > 0) {
              form.find('textarea[name="content"]').addClass('error');
            }
          }
        }
      })
      .catch(error => {
        console.error('Error submitting comment:', error);
        createAndShowToast('Ошибка отправки комментария', 'error');
      })
      .finally(() => {
        // Re-enable submit button
        submitButton.prop('disabled', false);
        submitButton.html(originalButtonText);
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

// Экспортируем новые функции для использования в других компонентах
export { reloadCommentsContent, updateCommentsContent, updateCommentsCounter };
