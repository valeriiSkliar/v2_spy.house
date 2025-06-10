import { ajaxFetcher } from './components/fetcher/ajax-fetcher.js';
import { hideInElement, showInElement } from './components/loader.js';
import { logger, loggerError } from './helpers/logger.js';
import { createAndShowToast } from './utils/uiHelpers.js';

/**
 * Обновляет URL браузера без перезагрузки страницы
 */
function updateBrowserUrl(params) {
  const url = new URL(window.location.href);
  const searchParams = new URLSearchParams(url.search);

  // Обновляем параметры
  Object.keys(params).forEach(key => {
    if (params[key] !== null && params[key] !== '') {
      searchParams.set(key, params[key]);
    } else {
      searchParams.delete(key);
    }
  });

  // Удаляем page=1 для чистоты URL
  if (searchParams.get('page') === '1') {
    searchParams.delete('page');
  }

  const newUrl = `${url.pathname}${searchParams.toString() ? '?' + searchParams.toString() : ''}`;
  window.history.pushState({}, '', newUrl);
}

/**
 * Обновляет контейнер пагинации
 */
function updatePagination(data) {
  const paginationContainer = document.getElementById('transactions-pagination-container');
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
 * Центральная функция перезагрузки контента транзакций
 */
function reloadTransactionsContent(container, url, scrollToTop = true) {
  logger('Загрузка истории транзакций', { url });

  // Показать лоадер
  const loader = showInElement(container);

  // Построить URL с параметрами
  const requestUrl = new URL(window.location.href);
  const queryParams = Object.fromEntries(requestUrl.searchParams.entries());

  // AJAX запрос через существующий ajaxFetcher
  ajaxFetcher.get(url, queryParams, {
    successCallback: function (response) {
      const data = response.data;
      logger('Данные транзакций загружены успешно', { count: data.count });

      // Обновить контент
      container.innerHTML = data.html;

      // Обновить пагинацию
      updatePagination(data);

      // Прокрутка к верху если нужно
      if (scrollToTop) {
        container.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }

      // Переинициализация обработчиков пагинации
      initializePaginationHandlers(container, url);
    },
    errorCallback: function (jqXHR, textStatus, errorThrown) {
      loggerError('Ошибка загрузки истории транзакций:', {
        status: jqXHR.status,
        textStatus,
        errorThrown,
      });
      createAndShowToast('Ошибка загрузки истории транзакций', 'error');
    },
    completeCallback: function () {
      hideInElement(loader);
    },
  });
}

// Глобальная переменная для хранения обработчика
let currentPaginationHandler = null;

/**
 * Обработчик кликов по пагинации
 */
function createPaginationClickHandler(container, url) {
  return function (event) {
    const paginationLink = event.target.closest('a[href*="page="]');
    if (!paginationLink) return;

    event.preventDefault();

    const href = paginationLink.getAttribute('href');
    const url_params = new URL(href, window.location.origin);
    const page = url_params.searchParams.get('page');

    if (page) {
      logger('Переход на страницу:', { page });
      updateBrowserUrl({ page: page });
      reloadTransactionsContent(container, url);
    }
  };
}

/**
 * Инициализация обработчиков пагинации
 */
function initializePaginationHandlers(container, url) {
  const paginationContainer = document.getElementById('transactions-pagination-container');
  if (!paginationContainer) return;

  // Удаляем предыдущий обработчик если он существует
  if (currentPaginationHandler) {
    paginationContainer.removeEventListener('click', currentPaginationHandler);
  }

  // Создаем новый обработчик и сохраняем ссылку на него
  currentPaginationHandler = createPaginationClickHandler(container, url);

  // Добавляем обработчик
  paginationContainer.addEventListener('click', currentPaginationHandler);
}

// Основная инициализация
document.addEventListener('DOMContentLoaded', function () {
  logger('Инициализация AJAX пагинации для транзакций');

  // Инициализация переменных
  const transactionsContainer = document.getElementById('transactions-container');
  const ajaxUrl = transactionsContainer?.getAttribute('data-transactions-ajax-url');
  const useAjax = !!ajaxUrl;

  if (!useAjax || !transactionsContainer || !ajaxUrl) {
    logger('AJAX пагинация для транзакций не настроена или контейнеры не найдены');
    return; // Выходим если AJAX не настроен
  }

  logger('AJAX пагинация для транзакций активирована', { ajaxUrl });

  // Обработчик события браузерной навигации
  window.addEventListener('popstate', function (event) {
    if (transactionsContainer && ajaxUrl) {
      logger('Навигация по истории браузера для транзакций');
      reloadTransactionsContent(transactionsContainer, ajaxUrl, false);
    }
  });

  // Инициализация обработчиков пагинации
  initializePaginationHandlers(transactionsContainer, ajaxUrl);
});
