import { initializeSelectComponent } from '@/helpers';
import { initializeServiceComponents } from '../components';
import { debounce } from '../helpers/custom-debounce';

document.addEventListener('DOMContentLoaded', function () {
  // Get services container reference for reuse
  const servicesContainer = document.getElementById('services-container');
  const ajaxUrl = servicesContainer?.getAttribute('data-services-ajax-url');
  const useAjax = !!ajaxUrl;
  
  // Add popstate event listener to handle browser back/forward navigation
  window.addEventListener('popstate', function(event) {
    // Reload services content when navigating through history
    if (servicesContainer && ajaxUrl) {
      reloadServicesContent(servicesContainer, ajaxUrl);
    }
  });
  
  // Setup search functionality
  setupSearchForm();
  
  // Add click handler for reset button
  const resetBtn = document.getElementById('services-reset-btn');
  if (resetBtn) {
    resetBtn.addEventListener('click', function(event) {
      event.preventDefault();
      
      // Get the reset URL
      const resetUrl = this.getAttribute('data-reset-url');
      if (!resetUrl) return;
      
      // Update browser URL without query parameters
      history.pushState({}, '', resetUrl);
      
      // Get services container
      const servicesContainer = document.getElementById('services-container');
      const ajaxUrl = servicesContainer?.getAttribute('data-services-ajax-url');
      
      if (servicesContainer && ajaxUrl) {
        // Show loading state
        servicesContainer.classList.add('loading');
        
        // Reset all select components to default state
        resetFilters();
        
        // Fetch default services list
        fetch(ajaxUrl, {
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
          
          // Scroll to top of services container
          servicesContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
          
          console.log('Filters reset, services reloaded');
        })
        .catch(error => {
          console.error('Error resetting services:', error);
        })
        .finally(() => {
          // Remove loading state
          servicesContainer.classList.remove('loading');
        });
      }
    });
  }
  // --- Initialize Components ---

  // Service Components
  initializeServiceComponents();
  
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
   * Reset all filter components to their default state
   */
  function resetFilters() {
    // Get filter selectors
    const filterSelectors = [
      '#sort-by', 
      '#services-per-page', 
      '#category-filter', 
      '#bonuses-filter'
    ];
    
    // Reset each select component
    filterSelectors.forEach(selector => {
      const container = $(selector);
      if (!container.length) return;
      
      // Find default option
      const defaultOption = container.find('.base-select__option[data-value="all"]');
      if (defaultOption.length) {
        // Simulate click on default option
        defaultOption.click();
      } else {
        // If no 'all' option, try first option
        const firstOption = container.find('.base-select__option').first();
        if (firstOption.length) {
          firstOption.click();
        }
      }
    });
    
    // Also reset search form if present
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
      const searchInput = document.getElementById('services-search-input');
      if (searchInput) {
        // Clear input value
        searchInput.value = '';
        
        // Remove warning class if any
        searchInput.classList.remove('min-chars-warning');
        
        // Trigger the search event to update results
        const searchEvent = new Event('search');
        searchInput.dispatchEvent(searchEvent);
      }
    }
  }

  /**
   * Initialize search form functionality
   */
  function setupSearchForm() {
    if (!useAjax) return;
    
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('services-search-input');
    
    if (!searchForm || !searchInput) return;
    
    // Get minimum characters for search
    const minChars = parseInt(searchInput.getAttribute('data-min-chars') || '3', 10);
    
    // Prevent the default form submission
    searchForm.addEventListener('submit', function(event) {
      event.preventDefault();
      
      const searchValue = searchInput.value.trim();
      
      // Check for minimum characters
      if (searchValue.length >= minChars || searchValue.length === 0) {
        performSearch(searchValue);
      }
    });
    
    // Create debounced search function (300ms delay)
    const debouncedSearch = debounce(function(value) {
      // Check for minimum characters
      if (value.length >= minChars || value.length === 0) {
        performSearch(value);
      }
    }, 300);
    
    // Add input event listener
    searchInput.addEventListener('input', function() {
      const searchValue = this.value.trim();
      
      // Show visual indicator that minimum characters are needed
      if (searchValue.length > 0 && searchValue.length < minChars) {
        searchInput.classList.add('min-chars-warning');
      } else {
        searchInput.classList.remove('min-chars-warning');
        debouncedSearch(searchValue);
      }
    });
    
    // Also handle search when clear/X button is clicked (for browsers that support it)
    searchInput.addEventListener('search', function() {
      const searchValue = this.value.trim();
      
      if (searchValue.length >= minChars || searchValue.length === 0) {
        performSearch(searchValue);
      }
    });
  }
  
  /**
   * Perform search with the given query
   * @param {string} query - Search query
   */
  function performSearch(query) {
    // Update URL with the search parameter
    const url = new URL(window.location.href);
    
    if (query && query.length > 0) {
      url.searchParams.set('search', query);
    } else {
      url.searchParams.delete('search');
    }
    
    // Update the browser URL
    history.pushState({}, '', url.toString());
    
    // Reload services with the new search parameter
    reloadServicesContent(servicesContainer, ajaxUrl, true);
  }

  function handleFilterChange(event, data) {
    if (!useAjax) return;
    
    console.log('Filter change detected', { event, data });
    
    // Use the generic reload function
    reloadServicesContent(servicesContainer, ajaxUrl, true);
  }
});
