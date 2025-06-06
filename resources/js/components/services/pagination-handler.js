import { updateBrowserUrl } from '../../helpers/update-browser-url';
import { hideInElement, showInElement } from '../loader';

/**
 * Initialize the AJAX-based pagination for services page
 */
const initServicesPagination = () => {
  const servicesContainer = document.getElementById('services-container');
  const paginationContainer = document.getElementById('services-pagination-container');

  if (!servicesContainer || !paginationContainer) {
    return; // If containers not found, exit
  }

  const ajaxUrl = servicesContainer.getAttribute('data-services-ajax-url');
  if (!ajaxUrl) {
    console.error('AJAX URL not defined for services pagination');
    return;
  }

  // Setup MutationObserver to handle dynamically added pagination links
  const observer = new MutationObserver(mutations => {
    mutations.forEach(mutation => {
      if (mutation.type === 'childList') {
        // When DOM changes, attach click handlers to pagination links
        attachPaginationHandlers();
      }
    });
  });

  // Start observing pagination container for changes
  observer.observe(paginationContainer, { childList: true, subtree: true });

  // Initial attachment of handlers
  attachPaginationHandlers();

  /**
   * Attach click handlers to pagination links
   */
  function attachPaginationHandlers() {
    // Using event delegation to handle all pagination links
    paginationContainer.addEventListener('click', handlePaginationClick);
  }

  /**
   * Handle pagination link clicks
   * @param {Event} event - Click event
   */
  function handlePaginationClick(event) {
    const target = event.target.closest('.pagination-link');

    if (!target || target.classList.contains('disabled') || target.classList.contains('active')) {
      return; // Not a pagination link or disabled/active link
    }

    event.preventDefault();

    const page = target.getAttribute('data-page');
    if (!page) {
      return; // No page attribute
    }

    // Load the requested page via AJAX
    loadServicesPage(page);
  }

  /**
   * Load services page via AJAX
   * @param {string|number} page - Page number to load
   */
  function loadServicesPage(page) {
    // Build URL with the current query parameters
    const url = new URL(window.location.href);
    url.searchParams.set('page', page);

    // Update browser URL
    updateBrowserUrl({ page });

    // Show loading state
    const loader = showInElement(servicesContainer);

    // Make AJAX request
    fetch(`${ajaxUrl}?${url.searchParams.toString()}`, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
    })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        // Update content
        servicesContainer.innerHTML = data.html;

        // Update pagination container
        if (paginationContainer) {
          // If pagination data exists, show it
          if (data.hasPagination && data.pagination) {
            paginationContainer.innerHTML = data.pagination;
            paginationContainer.style.display = 'block';
          } else {
            // Otherwise hide the pagination container
            paginationContainer.innerHTML = '';
            paginationContainer.style.display = 'none';
          }
        }

        // Scroll to top of services container
        if (window) {
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }
      })
      .catch(error => {
        console.error('Error fetching services:', error);
        // Could show error message to user here
      })
      .finally(() => {
        // Remove loading state
        hideInElement(loader);
      });
  }
};

export { initServicesPagination };
