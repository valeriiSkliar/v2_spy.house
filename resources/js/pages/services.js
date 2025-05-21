import { initializeSelectComponent } from '@/helpers';
import { initializeServiceComponents } from '../components';

document.addEventListener('DOMContentLoaded', function () {
  // Add popstate event listener to handle browser back/forward navigation
  window.addEventListener('popstate', function(event) {
    // Reload services content when navigating through history
    const servicesContainer = document.getElementById('services-container');
    const ajaxUrl = servicesContainer?.getAttribute('data-services-ajax-url');
    
    if (servicesContainer && ajaxUrl) {
      reloadServicesContent(servicesContainer, ajaxUrl);
    }
  });
  // --- Initialize Components ---

  // Service Components
  initializeServiceComponents();
  
  const servicesContainer = document.getElementById('services-container');
  const ajaxUrl = servicesContainer?.getAttribute('data-services-ajax-url');
  const useAjax = !!ajaxUrl;
  
  // Sort By
  initializeSelectComponent('#sort-by', {
    selectors: {
      select: '.base-select__dropdown',
      options: '.base-select__option',
      trigger: '.base-select__trigger',
      valueElement: '[data-value]',
      orderElement: '[data-order]',
    },
    params: {
      valueParam: 'sortBy',
      orderParam: 'sortOrder',
    },
    resetPage: true,
    preventReload: useAjax, // When Ajax is enabled, prevent form reload
  });

  // Per Page
  initializeSelectComponent('#services-per-page', {
    selectors: {
      select: '.base-select__dropdown',
      options: '.base-select__option',
      trigger: '.base-select__trigger',
      valueElement: '[data-value]',
      // No order element for perPage
    },
    params: {
      valueParam: 'perPage',
      // No order param
    },
    resetPage: true,
    preventReload: useAjax, // When Ajax is enabled, prevent form reload
  });

  // Category Filter
  initializeSelectComponent('#category-filter', {
    selectors: {
      select: '.base-select__dropdown',
      options: '.base-select__option',
      trigger: '.base-select__trigger',
      valueElement: '[data-value]',
    },
    params: {
      valueParam: 'category',
    },
    resetPage: false, // Do not reset page for filters
    preventReload: useAjax, // When Ajax is enabled, prevent form reload
  });

  // Bonuses Filter
  initializeSelectComponent('#bonuses-filter', {
    selectors: {
      select: '.base-select__dropdown',
      options: '.base-select__option',
      trigger: '.base-select__trigger',
      valueElement: '[data-value]',
    },
    params: {
      valueParam: 'bonuses',
    },
    resetPage: false, // Do not reset page for filters
    preventReload: useAjax, // When Ajax is enabled, prevent form reload
  });
  
  // If AJAX is enabled, add handlers for filter changes
  if (useAjax) {
    const filterSelectors = [
      '#sort-by', 
      '#services-per-page', 
      '#category-filter', 
      '#bonuses-filter'
    ];
    
    filterSelectors.forEach(selector => {
      // jQuery is needed because our custom events are triggered with jQuery
      $(selector).on('change', handleFilterChange);
    });
  }
  
  /**
   * Generic function to reload services content
   * @param {HTMLElement} container - The services container
   * @param {string} url - The AJAX URL to fetch data
   * @param {boolean} scrollToTop - Whether to scroll to top after loading
   */
  function reloadServicesContent(container, url, scrollToTop = true) {
    // Show loading state
    container.classList.add('loading');
    
    // Build URL with the current query parameters
    const requestUrl = new URL(window.location.href);
    
    // Make AJAX request
    fetch(`${url}?${requestUrl.searchParams.toString()}`, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then(data => {
      // Update content
      container.innerHTML = data.html;
      
      // Update pagination container
      const paginationContainer = document.getElementById('services-pagination-container');
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
      
      // Scroll to top of services container for better UX
      if (scrollToTop) {
        container.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
      
      console.log('Services loaded:', {
        count: data.count,
        currentPage: data.currentPage,
        totalPages: data.totalPages,
        hasPagination: data.hasPagination
      });
    })
    .catch(error => {
      console.error('Error fetching services:', error);
    })
    .finally(() => {
      // Remove loading state
      container.classList.remove('loading');
    });
  }

  /**
   * Handle filter changes for AJAX loading
   * @param {Event} event - Change event
   * @param {Object} data - Optional event data from select component
   */
  function handleFilterChange(event, data) {
    if (!useAjax) return;
    
    console.log('Filter change detected', { event, data });
    
    // Use the generic reload function
    reloadServicesContent(servicesContainer, ajaxUrl, true);
  }
});
