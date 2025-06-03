import { initializeSelectComponent } from '@/helpers';
import { updateBrowserUrl } from '@/helpers/update-browser-url';
import { hideInElement, showInElement } from '../components/loader';
import { NotificationItem } from '../components/notifications/notification-item';

document.addEventListener('DOMContentLoaded', function () {
  // Initialize variables
  const notificationsContainer = document.getElementById('notifications-container');
  const paginationContainer = document.getElementById('notifications-pagination-container');
  const ajaxUrl = notificationsContainer?.getAttribute('data-notifications-ajax-url');
  const useAjax = !!ajaxUrl;

  // Browser back/forward navigation
  window.addEventListener('popstate', function (event) {
    if (notificationsContainer && ajaxUrl) {
      reloadNotificationsContent(notificationsContainer, ajaxUrl);
    }
  });

  // Mark all as read functionality
  const markAllReadBtn = document.getElementById('mark-all-read');
  if (markAllReadBtn) {
    markAllReadBtn.addEventListener('click', function () {
      NotificationItem.markAllAsRead(markAllReadBtn.dataset.url).then(() => {
        // Reload notifications after marking all as read
        if (useAjax) {
          reloadNotificationsContent(notificationsContainer, ajaxUrl);
        }
      });
    });
  }

  // Initialize per-page selector
  initializeSelectComponent('#per-page', {
    selectors: {
      select: '.base-select__dropdown',
      options: '.base-select__option',
      trigger: '.base-select__trigger',
      valueElement: '[data-value]',
    },
    params: {
      valueParam: 'per_page',
    },
    resetPage: true,
    preventReload: useAjax,
    callback: useAjax
      ? function () {
          console.log('callback');
          reloadNotificationsContent(notificationsContainer, ajaxUrl);
        }
      : null,
  });

  // Central function to reload notifications content
  function reloadNotificationsContent(container, url, scrollToTop = true) {
    if (!container || !url) return;

    // Show loader
    const loader = showInElement(container);

    // Build request URL with current parameters
    const requestUrl = new URL(window.location.href);

    // Make AJAX request
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
        // Update notifications content
        container.innerHTML = data.html;

        // Update pagination
        updatePagination(data);

        // Update mark all read button state
        updateMarkAllReadButton(data.unreadCount);

        // Reinitialize notification items
        NotificationItem.init();

        // Scroll to top if requested
        if (scrollToTop) {
          container.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      })
      .catch(error => {
        console.error('Error loading notifications:', error);
        container.innerHTML =
          '<div class="alert alert-danger">Error loading notifications. Please refresh the page.</div>';
      })
      .finally(() => {
        hideInElement(loader);
      });
  }

  // Update pagination container
  function updatePagination(data) {
    if (paginationContainer) {
      if (data.hasPagination && data.pagination) {
        paginationContainer.innerHTML = `<div class="pagination-container">${data.pagination}</div>`;
        initializePaginationLinks();
      } else {
        paginationContainer.innerHTML = '';
      }
    }
  }

  // Update mark all read button state
  function updateMarkAllReadButton(unreadCount) {
    if (markAllReadBtn) {
      markAllReadBtn.disabled = unreadCount === 0;
    }
  }

  // Initialize pagination links for AJAX
  function initializePaginationLinks() {
    if (!useAjax) return;

    const paginationLinks = paginationContainer.querySelectorAll('a[href]');
    paginationLinks.forEach(link => {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        const url = new URL(link.href);

        // Update browser URL
        const searchParams = Object.fromEntries(url.searchParams);
        updateBrowserUrl(searchParams);

        // Reload content
        reloadNotificationsContent(notificationsContainer, ajaxUrl);
      });
    });
  }

  // Initialize pagination links on page load
  initializePaginationLinks();

  // Initialize notification items
  NotificationItem.init();
});
