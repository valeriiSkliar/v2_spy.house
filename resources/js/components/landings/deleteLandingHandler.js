import { createAndShowToast } from '../../utils/uiHelpers';
import { ajaxFetcher } from '../fetcher/ajax-fetcher';
import { hideInButton, showInButton } from '../loader';
import { landingsConstants } from './constants';
import { fetchAndReplaceContent } from './fetchAndReplaceContnt';

export const deleteLandingHandler = function (event) {
  event.preventDefault();
  event.stopImmediatePropagation();

  const $button = $(this);
  const landingId = $button.data('id');
  const landingName = $button.data('name') || 'этот лендинг';

  if (!landingId) {
    console.error('Landing ID not found on delete button.');
    createAndShowToast('Ошибка: ID лендинга не найден.', 'error');
    return;
  }

  if (!confirm(`Вы уверены, что хотите удалить ${landingName}?`)) {
    return;
  }

  showInButton($button, '_dark');

  if (!window.routes || !window.routes.landingsAjaxDestroyBase) {
    console.error('Route for landingsAjaxDestroyBase is not defined.');
    createAndShowToast('Ошибка конфигурации: URL для удаления не определен.', 'error');
    hideInButton($button);
    return;
  }
  const deleteUrl = window.routes.landingsAjaxDestroyBase.replace(':id', landingId);
  ajaxFetcher
    .delete(deleteUrl)
    .done(function (response) {
      if (response.success) {
        // First, remove the deleted row with animation
        $button.closest('tr').fadeOut(300, function () {
          $(this).remove();

          // First check if we need to navigate to a different page based on visible items
          const visibleItemsAfterRemoval = $(
            `${landingsConstants.LANDINGS_TABLE_CONTAINER_ID} tbody tr:visible`
          ).length;

          console.log('Visible items after removal:', visibleItemsAfterRemoval);

          // Find the pagination container to get AJAX URL and target selector
          const $paginationBox = $(`[${landingsConstants.PAGINATION_CONTAINER_ATTR}]`).first();
          const ajaxUrl = $paginationBox.data('ajax-url') || window.routes.landingsAjaxList;
          const targetSelector =
            $paginationBox.data('target-selector') || landingsConstants.CONTENT_WRAPPER_SELECTOR;
          const filterFormSelector = $paginationBox.data('filter-form-selector');

          // Get current filter/sort parameters
          let queryParams = {};
          const currentUrl = new URL(window.location.href);
          queryParams = Object.fromEntries(currentUrl.searchParams.entries());

          // Add filter form parameters if available
          if (filterFormSelector) {
            const $filterForm = $(filterFormSelector);
            if ($filterForm.length) {
              const formValues = $filterForm.serializeArray();
              formValues.forEach(function (field) {
                if (field.name !== 'page') {
                  queryParams[field.name] = field.value;
                }
              });
            }
          }

          // Get current pagination status
          let currentPage = parseInt(queryParams.page, 10) || 1;
          const perPage = parseInt(queryParams.per_page, 10) || 12;

          // Get pagination data from server response or use defaults
          const paginationData = response.pagination || {};
          const totalItems = paginationData.total_items || 0;
          const lastPage = paginationData.last_page || Math.max(1, Math.ceil(totalItems / perPage));

          let targetPage = currentPage;

          // If there are no visible items after removal and we're not on page 1,
          // navigate to the previous page
          if (visibleItemsAfterRemoval === 0 && currentPage > 1) {
            targetPage = currentPage - 1;
            console.log('No visible items left, navigating to previous page:', targetPage);
          }

          // Make sure target page doesn't exceed last page and is at least 1
          targetPage = Math.min(targetPage, lastPage);
          targetPage = Math.max(targetPage, 1);

          console.log('Target page after calculations:', targetPage);

          // Set the target page in query params
          queryParams.page = targetPage;

          // Always refresh content to update pagination
          if (ajaxUrl && targetSelector) {
            console.log('Refreshing content with page:', targetPage);
            fetchAndReplaceContent(
              ajaxUrl,
              queryParams,
              targetSelector,
              true // Update browser URL
            );
          }
        });
        createAndShowToast(response.message || 'Лендинг успешно удален.', 'success');
        hideInButton($button);
      } else {
        createAndShowToast(response.message || 'Не удалось удалить лендинг.', 'error');
      }
    })
    .fail(function (jqXHR, textStatus, errorThrown) {
      console.error('Error deleting landing: ', textStatus, errorThrown, jqXHR.responseText);
      let errorMessage = 'Произошла ошибка при удалении лендинга.';
      if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
        errorMessage = jqXHR.responseJSON.message;
      }
      createAndShowToast(errorMessage, 'error');
      hideInButton($button);
    });
};
