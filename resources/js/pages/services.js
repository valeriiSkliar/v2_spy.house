import { initializeSelectComponent } from '@/helpers';
import { initializeServiceComponents } from '../components';

document.addEventListener('DOMContentLoaded', function () {
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
      const container = document.querySelector(selector);
      if (container) {
        container.addEventListener('change', handleFilterChange);
      }
    });
  }
  
  /**
   * Handle filter changes for AJAX loading
   * @param {Event} event - Change event
   */
  function handleFilterChange(event) {
    if (!useAjax) return;
    
    // Show loading state
    servicesContainer.classList.add('loading');
    
    // Build URL with the current query parameters
    const url = new URL(window.location.href);
    
    // Make AJAX request
    fetch(`${ajaxUrl}?${url.searchParams.toString()}`, {
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
      servicesContainer.innerHTML = data.html;
      
      // Update pagination
      const paginationContainer = document.getElementById('services-pagination-container');
      if (paginationContainer && data.pagination) {
        paginationContainer.innerHTML = data.pagination;
      }
    })
    .catch(error => {
      console.error('Error fetching services:', error);
    })
    .finally(() => {
      // Remove loading state
      servicesContainer.classList.remove('loading');
    });
  }
});
