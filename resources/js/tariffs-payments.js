import { hideInElement, showInElement } from './components/loader';
import { updateBrowserUrl } from './helpers/url-helpers';

document.addEventListener('DOMContentLoaded', function () {
  // Инициализация переменных
  const paymentsContainer = document.getElementById('payments-container');
  const paginationContainer = document.getElementById('payments-pagination-container');
  const ajaxUrl = paymentsContainer?.getAttribute('data-payments-ajax-url');
  const useAjax = !!ajaxUrl;

  // Обработчик браузерной навигации
  window.addEventListener('popstate', function (event) {
    if (paymentsContainer && ajaxUrl) {
      reloadPaymentsContent(paymentsContainer, ajaxUrl, false);
    }
  });

  // Обработчик кликов по пагинации
  if (paginationContainer && useAjax) {
    paginationContainer.addEventListener('click', function (event) {
      const paginationLink = event.target.closest('.pagination-link');

      if (
        paginationLink &&
        !paginationLink.classList.contains('disabled') &&
        !paginationLink.classList.contains('active')
      ) {
        event.preventDefault();

        const page = paginationLink.getAttribute('data-page');
        if (page) {
          const url = new URL(window.location.href);
          url.searchParams.set('page', page);

          // Обновляем URL браузера
          updateBrowserUrl(url.toString());

          // Перезагружаем контент
          reloadPaymentsContent(paymentsContainer, ajaxUrl);
        }
      }
    });
  }

  /**
   * Центральная функция перезагрузки контента платежей
   */
  function reloadPaymentsContent(container, url, scrollToTop = true) {
    // Показать лоадер
    const loader = showInElement(container);

    // Построить URL с параметрами
    const requestUrl = new URL(window.location.href);

    // AJAX запрос
    fetch(`${url}?${requestUrl.searchParams.toString()}`, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
    })
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        // Обновить список платежей
        const paymentsList = container.querySelector('#payments-list');
        if (paymentsList) {
          paymentsList.innerHTML = data.html;
        }

        // Обновить пагинацию
        updatePagination(data);

        // Прокрутка к началу таблицы если необходимо
        if (scrollToTop) {
          container.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      })
      .catch(error => {
        console.error('Error loading payments:', error);
        // Можно добавить показ toast с ошибкой
      })
      .finally(() => {
        hideInElement(loader);
      });
  }

  /**
   * Обновление пагинации
   */
  function updatePagination(data) {
    if (paginationContainer) {
      if (data.hasPagination) {
        paginationContainer.innerHTML = data.pagination;
        paginationContainer.style.display = 'block';
      } else {
        paginationContainer.innerHTML = '';
        paginationContainer.style.display = 'none';
      }
    }
  }
});
