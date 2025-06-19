import { hideInElement, showInElement } from '@/components/loader';
import { createAndShowToast } from '@/utils/uiHelpers';
import { blogAPI } from '../fetcher/ajax-fetcher';

function setCommentMode(e) {
  e.preventDefault();

  // Обновляем store состояние (очищаем reply mode)
  if (typeof Alpine !== 'undefined' && Alpine.store) {
    const store = Alpine.store('blog');
    if (store) {
      store.clearReplyMode();
      console.log('Reply mode cleared via store');
    }
  }

  // Обновляем DOM (сохраняем для совместимости)
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
  // Обновляем store состояние (устанавливаем reply mode)
  if (typeof Alpine !== 'undefined' && Alpine.store) {
    const store = Alpine.store('blog');
    if (store) {
      store.setReplyMode(parentId, authorName);
      console.log('Reply mode set via store:', { parentId, authorName });
    }
  }

  // Обновляем DOM (сохраняем для совместимости)
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
 * MIGRATED: Использует centralized store вместо прямых DOM манипуляций
 */
function reloadCommentsContent(container, slug, options = {}) {
  const { page = 1, sort = 'latest', showLoader = true, scrollToTop = true } = options;

  // Получаем store для централизованного управления состоянием
  if (typeof Alpine === 'undefined' || !Alpine.store) {
    console.error('Alpine store not available for comments reload');
    return;
  }

  const store = Alpine.store('blog');
  if (!store) {
    console.error('Blog store not available');
    return;
  }

  // Обновляем состояние загрузки через store
  store.setCommentsLoading(true);

  // Используем централизованный blogAPI вместо прямого fetch
  blogAPI
    .loadComments(slug, { page, sort })
    .then(data => {
      if (data.success) {
        // Обновляем store вместо прямых DOM манипуляций
        store.setComments(data.comments || []);

        // Обновляем пагинацию в store
        store.setCommentsPagination({
          currentPage: data.currentPage || page,
          totalPages: data.totalPages || 1,
          hasPages: data.hasPagination || false,
        });

        // Обновляем содержимое комментариев (пока сохраняем для совместимости)
        updateCommentsContent(data, container);

        // Обновляем счетчик комментариев
        updateCommentsCounter(data.commentsCount);

        // Переинициализируем компоненты
        reinitializeCommentsComponents();

        if (scrollToTop) {
          container.scrollIntoView({ behavior: 'smooth' });
        }

        console.log('Comments reloaded via store:', {
          commentsCount: data.comments?.length || 0,
          currentPage: data.currentPage || page,
          totalPages: data.totalPages || 1,
        });
      } else {
        createAndShowToast(data.message || 'Ошибка загрузки комментариев', 'error');
      }
    })
    .catch(error => {
      console.error('Error loading comments:', error);
      createAndShowToast('Ошибка загрузки комментариев', 'error');
    })
    .finally(() => {
      // Обновляем состояние загрузки через store
      store.setCommentsLoading(false);
    });
}

/**
 * Загрузка комментариев для пагинации (совместимость)
 * MIGRATED: Использует centralized store для управления состоянием
 */
function loadComments(slug, page) {
  const commentForm = $('#universal-comment-form');
  const commentsList = $('.comment-list');
  const paginationContainer = $('.pagination-nav');
  const commentContainer = $('#article-comments-container');

  // Получаем store для централизованного управления состоянием
  if (typeof Alpine !== 'undefined' && Alpine.store) {
    const store = Alpine.store('blog');
    if (store) {
      // Обновляем состояние загрузки через store
      store.setCommentsLoading(true);

      // Обновляем пагинацию в store
      store.setCommentsPagination({
        ...store.comments.pagination,
        currentPage: page,
      });
    }
  }

  // Fallback: показываем loading indicator в DOM (для совместимости)
  let loader = null;
  if (commentContainer.length >= 1) {
    loader = showInElement('article-comments-container', 'Loading comments...');
  } else {
    // Show loading indicator that matches site styling
    commentsList.html(
      '<div class="text-center py-4"><div class="message _bg _with-border">Loading comments...</div></div>'
    );
  }

  // Используем централизованный blogAPI вместо прямого fetch
  blogAPI
    .loadComments(slug, { page })
    .then(data => {
      if (data.success) {
        // Обновляем store с полученными данными
        if (typeof Alpine !== 'undefined' && Alpine.store) {
          const store = Alpine.store('blog');
          if (store) {
            store.setComments(data.comments || []);
            store.setCommentsPagination({
              currentPage: data.currentPage || page,
              totalPages: data.totalPages || 1,
              hasPages: data.hasPagination || false,
            });

            console.log('Comments pagination loaded via store:', {
              page: data.currentPage || page,
              totalPages: data.totalPages || 1,
              commentsCount: data.comments?.length || 0,
            });
          }
        }

        // Update comments (сохраняем для совместимости)
        $(commentsList).html(data.commentsHtml);

        // Update pagination (сохраняем для совместимости)
        $(paginationContainer).html(data.paginationHtml);

        // Reinitialize pagination after DOM update
        initCommentPagination();

        // Reinitialize reply buttons
        initReplyButtons(commentForm);

        // Scroll to comments section
        document.getElementById('comments').scrollIntoView({ behavior: 'smooth' });
      } else {
        if (loader) {
          hideInElement(loader);
        }
        createAndShowToast(data.message || 'Ошибка загрузки комментариев', 'error');
      }
    })
    .catch(error => {
      console.error('Error loading comments:', error);
      createAndShowToast('Ошибка загрузки комментариев', 'error');
    })
    .finally(() => {
      // Обновляем состояние загрузки через store
      if (typeof Alpine !== 'undefined' && Alpine.store) {
        const store = Alpine.store('blog');
        if (store) {
          store.setCommentsLoading(false);
        }
      }

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
    const submitButton = form.find('button[type="submit"]');

    // Validate form
    if (!validateCommentForm(form)) {
      return;
    }

    // Получаем store для управления состоянием формы
    const store = typeof Alpine !== 'undefined' && Alpine.store ? Alpine.store('blog') : null;

    if (store) {
      // Обновляем состояние отправки формы через store
      store.setCommentFormSubmitting(true);
    }

    // Disable submit button to prevent double submission (сохраняем для совместимости)
    submitButton.prop('disabled', true);
    const originalButtonText = submitButton.html();
    submitButton.html('<i class="fas fa-spinner fa-spin"></i> Отправка...');

    const formData = new FormData(this);
    const actionUrl = form.attr('action');

    // Используем централизованный blogAPI для отправки комментария
    const slug = window.location.pathname.split('/').pop();

    blogAPI
      .submitComment(slug, formData)
      .then(data => {
        if (data.success) {
          createAndShowToast(data.message, 'success');

          // Обновляем store при успешной отправке
          if (store) {
            // Добавляем новый комментарий в store
            if (data.comment) {
              const currentComments = store.comments.list || [];
              store.setComments([...currentComments, data.comment]);
            }

            // Очищаем состояние формы в store
            store.setCommentForm({
              submitting: false,
              content: '',
              errors: {},
            });

            // Очищаем reply mode если был активен
            store.clearReplyMode();

            console.log('Comment submitted via store:', {
              commentAdded: !!data.comment,
              totalComments: store.comments.list.length,
            });
          }

          // Очистка формы (сохраняем для совместимости)
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

          // Обновляем store с ошибками
          if (store && data.errors) {
            store.setCommentForm({
              submitting: false,
              errors: data.errors,
            });
          }

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

        // Обновляем store с ошибкой
        if (store) {
          store.setCommentForm({
            submitting: false,
            errors: { general: error.message || 'Ошибка отправки комментария' },
          });
        }

        // Используем централизованное сообщение об ошибке из blogAPI
        const errorMessage = error.message || 'Ошибка отправки комментария';
        createAndShowToast(errorMessage, 'error');
      })
      .finally(() => {
        // Обновляем состояние формы в store
        if (store) {
          store.setCommentFormSubmitting(false);
        }

        // Re-enable submit button (сохраняем для совместимости)
        submitButton.prop('disabled', false);
        submitButton.html(originalButtonText);
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
