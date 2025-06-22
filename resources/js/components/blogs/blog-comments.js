import { hideInElement, showInElement } from '@/components/loader';
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
    .closest('#universal-comment-form')
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
 * Загрузка комментариев для пагинации (совместимость)
 */
function loadComments(slug, page) {
  const commentForm = $('#universal-comment-form');
  const commentsList = $('.comment-list');
  const paginationContainer = $('.pagination-nav');
  const commentContainer = $('#article-comments-container');

  let loader = null;
  if (commentContainer.length >= 1) {
    loader = showInElement('article-comments-container', 'Loading comments...');
  } else {
    // Show loading indicator that matches site styling
    commentsList.html(
      '<div class="text-center py-4"><div class="message _bg _with-border">Loading comments...</div></div>'
    );
  }

  // Используем унифицированный API endpoint
  fetch(`/api/blog/${slug}/comments?page=${page}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Update comments
        $(commentsList).html(data.html);

        // Update pagination
        $(paginationContainer).html(data.pagination);

        // Reinitialize pagination after DOM update
        initCommentPagination();

        // КРИТИЧНО: Переинициализируем форму комментария после обновления DOM
        const updatedCommentForm = $('#universal-comment-form');
        if (updatedCommentForm.length) {
          initUniversalCommentForm(updatedCommentForm);
          initReplyButtons(updatedCommentForm);
        }

        // Scroll to comments section
        document.getElementById('comments').scrollIntoView({ behavior: 'smooth' });
      } else {
        if (loader) {
          hideInElement(loader);
        }
        createAndShowToast('Error loading comments. Please try again.', 'error');
        commentsList.html(
          '<div class="message _bg _with-border _red">Error loading comments. Please try again.</div>'
        );
      }
    })
    .catch(error => {
      if (loader) {
        hideInElement(loader);
      }
      createAndShowToast('Error loading comments. Please try again.', 'error');
      console.error('Error:', error);
      commentsList.html(
        '<div class="message _bg _with-border _red">Error loading comments. Please try again.</div>'
      );
    })
    .finally(() => {
      if (loader) {
        hideInElement(loader);
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
  if (commentForm.length) {
    initUniversalCommentForm(commentForm);
    initReplyButtons(commentForm);
  }

  // Переинициализируем пагинацию
  initCommentPagination();
}

/**
 * Инициализация пагинации комментариев
 */
function initCommentPagination() {
  const blogLayout = $('.blog-layout');
  if (blogLayout.length < 1) return;
  const article = $('.article._big._single');
  if (article.length < 1) return;

  const commentsList = $('.comment-list');
  const paginationLinks = $('.pagination-link');
  const commentsContainer = $('#comments');

  if (!commentsList || !commentsContainer) return;

  paginationLinks.each(function () {
    if ($(this).hasClass('disabled')) return;

    // Убираем старые обработчики перед добавлением новых
    $(this).off('click');

    $(this).on('click', function (e) {
      e.preventDefault();

      const slug = window.location.pathname.split('/').pop();
      let page = this.dataset.page;

      // For number pagination links
      if (!isNaN(parseInt(page))) {
        loadComments(slug, parseInt(page));
        return;
      }

      // Handle prev/next links that have URLs
      if (page && page.includes('?')) {
        const urlParams = new URLSearchParams(page.split('?')[1]);
        const pageParam = urlParams.get('page');
        if (pageParam) {
          loadComments(slug, parseInt(pageParam));
          return;
        }
      }

      // Default to page 1 if we couldn't extract a page number
      loadComments(slug, 1);
      console.log('loadComments');
    });
  });
}

/**
 * Обработка кликов по пагинации
 */
function handlePaginationClick(e) {
  e.preventDefault();
  const target = e.currentTarget;
  const container = document.querySelector('#comments');

  if (!container) return;

  const slug = window.location.pathname.split('/').pop();
  const page = parseInt(target.dataset.page) || 1;

  reloadCommentsContent(container, slug, { page, scrollToTop: true });
}

export function initUniversalCommentForm(commentForm) {
  if (!commentForm) return;

  // Убираем старые обработчики перед добавлением новых
  commentForm.off('submit');

  commentForm.on('submit', function (e) {
    e.preventDefault();

    const form = $(this);
    const container = form[0].closest('#comments'); // Получаем DOM элемент для лоадера

    // Validate form
    if (!validateCommentForm(form)) {
      return;
    }

    // Показываем лоадер для всей формы (как при пагинации)
    const loader = showInElement(container);

    const formData = new FormData(this);
    const actionUrl = form.attr('action');

    // Используем унифицированный API endpoint
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
        // Скрываем лоадер
        if (loader) {
          hideInElement(loader);
        }
      });
  });
}

export function initReplyButtons(commentForm) {
  if (!commentForm) return;

  const replyButtons = $('.reply-btn');
  if (!replyButtons) return;
  replyButtons.each(function () {
    const button = $(this);
    // Убираем старые обработчики перед добавлением новых
    button.off('click');
    button.on('click', replyClickHandler);
  });
}

// Инициализация при загрузке DOM
document.addEventListener('DOMContentLoaded', function () {
  initCommentPagination();

  // Инициализируем форму комментария если она существует
  const commentForm = $('#universal-comment-form');
  if (commentForm.length) {
    initUniversalCommentForm(commentForm);
    initReplyButtons(commentForm);
  }
});

// Экспорт всех функций
export {
  handlePaginationClick,
  initCommentPagination,
  loadComments,
  reinitializeCommentsComponents,
  reloadCommentsContent,
  updateCommentsContent,
  updateCommentsCounter,
};
