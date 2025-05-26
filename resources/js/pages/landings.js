import { initializeDynamicLandingStatus, initializeLandingStatus } from '@/components/landings';
import { initializeSelectComponent } from '@/helpers';
import $ from 'jquery';
import {
  addLandingHandler,
  asyncPaginationHandler,
  deleteLandingHandler,
  fetchAndReplaceContent,
  initDownloadLandingHandler,
} from '../components/landings';
import { landingsConstants } from '../components/landings/constants';
import { logger, loggerError } from '../helpers/logger';

export function initDeleteLandingHandler() {
  const tableContainerSelector = landingsConstants.LANDINGS_TABLE_CONTAINER_ID;
  const deleteButtonSelector = '.delete-landing-button';

  $(document)
    .off('click', `${tableContainerSelector} ${deleteButtonSelector}`)
    .on('click', `${tableContainerSelector} ${deleteButtonSelector}`, deleteLandingHandler);
}

$(document).ready(function () {
  if (!$('#landings-page-content').length) {
    console.warn('Landings page content not found.');
    return false;
  }

  // --- Вспомогательные функции ---

  // --- Инициализация универсального обработчика пагинации ---
  function initAsyncPaginationHandler() {
    // Делегированный обработчик для всех контейнеров пагинации
    $(document).on(
      'click',
      `[${landingsConstants.PAGINATION_CONTAINER_ATTR}] ${landingsConstants.PAGINATION_LINK_SELECTOR}`,
      asyncPaginationHandler
    );
  }

  // --- Инициализация обработчиков сортировки (адаптируем для универсальности) ---
  function initSortAndFilterHandlers() {
    // Common AJAX handler for both select components
    function handleSelectChange(params) {
      logger('handleSelectChange - входящие параметры:', params);

      const $sortForm = $('#landings-sort-form');
      if (!$sortForm.length) return;

      // Проверяем, что параметры не пустые
      if (!params || Object.keys(params).length === 0) {
        loggerError('Ошибка: пустые параметры в handleSelectChange');
        return;
      }

      // Get current form values
      const formData = Object.fromEntries(new URLSearchParams($sortForm.serialize()).entries());

      // Merge with new params
      const queryParams = { ...formData, ...params, page: 1 };

      // Update the form inputs to reflect the new values
      Object.entries(params).forEach(([key, value]) => {
        const input = $sortForm.find(`input[name="${key}"]`);
        if (input.length) {
          input.val(value);
        }
      });

      // Find the associated pagination container
      const $paginationBox = $(`[data-filter-form-selector="#${$sortForm.attr('id')}"]`).first();

      let targetSelector, ajaxUrl;

      if ($paginationBox.length) {
        targetSelector = $paginationBox.data('target-selector');
        ajaxUrl = $paginationBox.data('ajax-url');
      } else {
        // Fallback to default values
        targetSelector = `#${landingsConstants.CONTENT_WRAPPER_ID}`;
        ajaxUrl = window.routes.landingsAjaxList;
      }

      if (ajaxUrl && $(targetSelector).length) {
        fetchAndReplaceContent(ajaxUrl, queryParams, targetSelector);
      }
    }

    // Initialize sort-by component with AJAX handler
    initializeSelectComponent('#sort-by', {
      selectors: {
        select: '.base-select__dropdown',
        options: '.base-select__option',
        trigger: '.base-select__trigger',
        valueElement: "input[name='sort']",
        orderElement: "input[name='direction']",
      },
      params: {
        valueParam: 'sort',
        orderParam: 'direction',
      },
      resetPage: true,
      ajaxHandler: handleSelectChange,
    });

    // Initialize items-per-page component with AJAX handler
    initializeSelectComponent('#items-per-page', {
      selectors: {
        select: '.base-select__dropdown',
        options: '.base-select__option',
        trigger: '.base-select__trigger',
        valueElement: "input[name='per_page']",
      },
      params: {
        valueParam: 'per_page',
      },
      resetPage: true,
      ajaxHandler: handleSelectChange,
    });

    // Form change handler as a backup for any other form elements
    const $sortForm = $('#landings-sort-form');
    if (!$sortForm.length) return;

    // Handle form changes for updating content via AJAX
    $sortForm.on('change', "input[type='hidden']", function (event) {
      // Skip if the change was triggered by one of our select components
      // (they're already handled by the ajaxHandler)
      const inputName = $(this).attr('name');
      if (
        (inputName === 'sort' || inputName === 'direction') &&
        event.originalEvent === undefined
      ) {
        return;
      }
      if (inputName === 'per_page' && event.originalEvent === undefined) {
        return;
      }

      // Get form data
      let queryParams = Object.fromEntries(new URLSearchParams($sortForm.serialize()).entries());
      queryParams.page = 1; // Reset to first page

      // Find target container
      const $paginationBox = $(`[data-filter-form-selector="#${$sortForm.attr('id')}"]`).first();

      let targetSelector, ajaxUrl;

      if ($paginationBox.length) {
        targetSelector = $paginationBox.data('target-selector');
        ajaxUrl = $paginationBox.data('ajax-url');
      } else {
        // Fallback to default values
        targetSelector = `#${landingsConstants.CONTENT_WRAPPER_ID}`;
        ajaxUrl = window.routes.landingsAjaxList;
      }

      if (ajaxUrl && $(targetSelector).length) {
        fetchAndReplaceContent(ajaxUrl, queryParams, targetSelector);
      } else {
        $sortForm.submit(); // Fallback
      }
    });
  }

  // --- Обработка истории браузера ---
  $(window).on('popstate', function (event) {
    const state = event.originalEvent.state;
    if (state) {
      // Ищем первый универсальный контейнер пагинации и используем его конфигурацию
      const $paginationBox = $(`[${landingsConstants.PAGINATION_CONTAINER_ATTR}]`).first();
      if ($paginationBox.length) {
        const targetSelector = $paginationBox.data('target-selector');
        const ajaxUrl = $paginationBox.data('ajax-url');
        if (targetSelector && ajaxUrl) {
          fetchAndReplaceContent(ajaxUrl, state, targetSelector, false);
        }
      }
    } else {
      // Начальная загрузка страницы или ручное изменение URL, не через pushState
      // Можно попытаться восстановить состояние из URL, если необходимо
      const currentUrlParams = Object.fromEntries(new URLSearchParams(window.location.search));
      if (Object.keys(currentUrlParams).length > 0) {
        const $paginationBox = $(`[${landingsConstants.PAGINATION_CONTAINER_ATTR}]`).first();
        if ($paginationBox.length) {
          const targetSelector = $paginationBox.data('target-selector');
          const ajaxUrl = $paginationBox.data('ajax-url');
          if (targetSelector && ajaxUrl) {
            fetchAndReplaceContent(ajaxUrl, currentUrlParams, targetSelector, false);
          }
        }
      }
    }
  });

  // --- Инициализация для глобальных селектов (вне обновляемого контента) ---
  // This is no longer needed as we're using initializeSelectComponent
  function initializeGlobalSelects() {
    // Initialization is now handled in initSortAndFilterHandlers
  }

  // --- Обработчик добавления нового лендинга ---
  function initAddLandingHandler() {
    const $form = $(`#${landingsConstants.ADD_LANDING_FORM_ID}`);
    if (!$form.length) {
      console.warn(`Form with ID #${landingsConstants.ADD_LANDING_FORM_ID} not found.`);
      return;
    }

    $form.on('submit', addLandingHandler);
  }

  // --- Передача маршрутов из Blade (обязательно!) ---
  // Убедитесь, что объект window.routes определен в Blade перед этим скриптом, например:
  // <script>
  //  window.routes = {
  //      landingsAjaxList: '{{ route("landings.ajax.list") }}',
  //      landingsAjaxStore: '{{ route("landings.ajax.store") }}',
  //      landingsAjaxDestroyBase: '{{ route("landings.ajax.destroy", ["landing" => "_LANDING_ID_"]) }}'
  //  };
  //  window.translations = { // Для JS локализации, если нужно
  //      commonError: "{{ __('common.error_occurred_common_message') }}"
  //  }
  // </script>

  if (!window.routes || !window.routes.landingsAjaxList) {
    console.error('JS route `landingsAjaxList` is not defined in window.routes.');
  }
  // if (!window.translations || !window.translations.commonError) {
  //     console.warn('JS translations are not defined in window.translations.');
  // }
  // window.__ = key => window.translations[key] || key; // Простая функция для локализации в JS

  // --- Запуск инициализаций ---
  initSortAndFilterHandlers();
  initAddLandingHandler();
  initDeleteLandingHandler();
  initDownloadLandingHandler(); // Инициализация обработчика скачивания лендинга
  initializeGlobalSelects();
  initAsyncPaginationHandler();
  initializeLandingStatus();
  initializeDynamicLandingStatus();

  // Первоначальная установка состояния из URL для popstate
  // ... existing code ...
});
