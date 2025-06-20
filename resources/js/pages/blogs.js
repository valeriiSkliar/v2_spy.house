import {
  initAlsowInterestingArticlesCarousel,
  initCommentPagination,
  initReadOftenArticlesCarousel,
} from '@/components/blogs';
import Alpine from 'alpinejs';
import { blogAjaxManager } from '../managers/blog-ajax-manager';

/**
 * Главная функция для перезагрузки контента блога
 * Зачем: централизованная обработка всех AJAX запросов с retry логикой и валидацией
 * REFACTORED: Now uses store and ajax manager
 */
function reloadBlogContent(container, url, options = {}) {
  return blogAjaxManager.loadContent(container, url, options);
}

/**
 * Валидация параметров запроса
 * Зачем: предотвращение некорректных состояний URL
 * REFACTORED: Now uses store validation with safety checks
 */
function validateRequestParams() {
  if (typeof Alpine !== 'undefined' && Alpine.store) {
    try {
      const store = Alpine.store('blog');
      if (store) {
        store.updateFromURL();
        return store.validateFilters();
      }
    } catch (e) {
      console.warn('Store not available for validation, falling back to basic validation');
    }
  }

  // Fallback validation without store
  const urlParams = new URLSearchParams(window.location.search);
  const page = parseInt(urlParams.get('page')) || 1;
  const category = urlParams.get('category');
  const search = urlParams.get('search');

  if (page < 1 || page > 1000) return false;
  if (search && (search.length > 255 || search.length < 1)) return false;
  if (category && !/^[a-zA-Z0-9\-_]*$/.test(category)) return false;

  return true;
}

// Note: Functions like buildRequestUrl, handleRedirectResponse, updatePageContent, etc.
// have been moved to blogAjaxManager for better centralization and state management

// Глобальная переменная для хранения обработчика пагинации
let currentPaginationHandler = null;

/**
 * Инициализация обработчиков кликов по пагинации
 * Зачем: обеспечение AJAX работы пагинации
 */
function initPaginationClickHandlers() {
  const paginationLinks = document.querySelectorAll(
    '#blog-pagination-container .pagination-list a'
  );

  console.log('Found pagination links:', paginationLinks.length);

  paginationLinks.forEach(link => {
    // Удаляем старые обработчики
    if (currentPaginationHandler) {
      link.removeEventListener('click', currentPaginationHandler);
    }

    // Добавляем новый обработчик
    link.addEventListener('click', handlePaginationClick);
  });

  currentPaginationHandler = handlePaginationClick;
}

/**
 * Обработка кликов по ссылкам пагинации
 * Зачем: AJAX навигация без перезагрузки страницы
 * MIGRATED: Now uses centralized store for state management
 */
function handlePaginationClick(event) {
  event.preventDefault();

  // Получаем store - обязательно для новой архитектуры
  if (typeof Alpine === 'undefined' || !Alpine.store) {
    console.error('Alpine store not available for pagination');
    return;
  }

  const store = Alpine.store('blog');
  if (!store) {
    console.error('Blog store not available');
    return;
  }

  // Проверяем состояние загрузки через store
  if (store.loading) {
    console.log('Request already in progress, ignoring pagination click');
    return;
  }

  // Получаем номер страницы из data-page атрибута или href
  const linkElement = event.target.closest('a') || event.target;
  let page;

  // Приоритет: сначала data-page, потом href
  if (linkElement.dataset.page) {
    page = parseInt(linkElement.dataset.page);
  } else {
    const href = linkElement.href;
    if (!href) {
      console.error('No href or data-page found on pagination link');
      return;
    }
    const url = new URL(href);
    page = parseInt(url.searchParams.get('page')) || 1;
  }

  // Валидация номера страницы
  if (page < 1 || page > 1000) {
    console.error('Invalid page number:', page);
    return;
  }

  console.log('Navigating to page:', page, 'via store');

  // Обновляем состояние через store (реактивно)
  store.setFilters({
    ...store.filters,
    page,
  });

  // Получаем контейнер и URL для загрузки
  const blogContainer = document.getElementById('blog-articles-container');
  const ajaxUrl = blogContainer?.getAttribute('data-blog-ajax-url');

  if (!ajaxUrl) {
    console.error('No AJAX URL found');
    return;
  }

  // Запускаем загрузку через manager (он использует store состояние)
  reloadBlogContent(blogContainer, ajaxUrl);
}

/**
 * Инициализация фильтрации по категориям
 * Зачем: AJAX фильтрация без перезагрузки
 * REFACTORED: Now uses store and ajax manager
 */
function initCategoryFiltering() {
  const blogContainer = document.getElementById('blog-articles-container');
  const ajaxUrl = blogContainer?.getAttribute('data-blog-ajax-url');

  if (!ajaxUrl) {
    console.log('No AJAX URL found, category filtering disabled');
    return;
  }

  // Используем делегирование событий для лучшей производительности
  document.addEventListener('click', function (event) {
    const categoryLink = event.target.closest('[data-ajax-category-link]');
    if (!categoryLink) return;

    event.preventDefault();

    // Получаем store - обязательно для новой архитектуры
    if (typeof Alpine === 'undefined' || !Alpine.store) {
      console.error('Alpine store not available for categories');
      return;
    }

    const store = Alpine.store('blog');
    if (!store) {
      console.error('Blog store not available');
      return;
    }

    // Проверяем состояние загрузки через store
    if (store.loading) {
      console.log('Request already in progress, ignoring category click');
      return;
    }

    const categorySlug = categoryLink.getAttribute('data-category-slug') || '';

    console.log('Category clicked:', categorySlug || 'all', 'via store');

    // Обновляем состояние через store (реактивно) - сбрасываем страницу на 1
    store.setFilters({
      ...store.filters,
      category: categorySlug,
      page: 1, // Сбрасываем на первую страницу при смене категории
    });

    // Обновляем активное состояние в сайдбаре (preserve existing DOM approach)
    updateCategorySidebarState(categorySlug);

    // Перезагружаем контент блога
    reloadBlogContent(blogContainer, ajaxUrl);
  });
}

/**
 * Обновление активного состояния в сайдбаре категорий
 * Зачем: визуальная индикация выбранной категории
 */
function updateCategorySidebarState(categorySlug) {
  const sidebar = document.querySelector('[data-blog-sidebar]');
  if (!sidebar) return;

  // Убираем активный класс со всех элементов
  sidebar.querySelectorAll('.blog-nav li').forEach(li => {
    li.classList.remove('is-active');
  });

  // Добавляем активный класс к выбранной категории
  const targetLink = sidebar.querySelector(`[data-category-slug="${categorySlug || ''}"]`);
  if (targetLink) {
    targetLink.closest('li').classList.add('is-active');
  }
}

/**
 * Инициализация пагинации блога
 * Зачем: настройка AJAX пагинации и навигации браузера
 */
function initBlogPagination() {
  const blogContainer = document.getElementById('blog-articles-container');
  const ajaxUrl = blogContainer?.getAttribute('data-blog-ajax-url');

  console.log('Blog pagination init:', { blogContainer, ajaxUrl });

  if (!ajaxUrl) {
    console.log('No AJAX URL found, pagination disabled');
    return;
  }

  // Обработчик навигации браузера (кнопки назад/вперед)
  window.addEventListener('popstate', function () {
    if (blogContainer && ajaxUrl) {
      // Проверяем store и синхронизируем состояние с URL
      if (typeof Alpine !== 'undefined' && Alpine.store) {
        try {
          const store = Alpine.store('blog');
          if (store) {
            // Предотвращаем одновременные запросы
            if (store.loading) {
              return;
            }

            console.log('Popstate event triggered, syncing store with URL');

            // Синхронизируем store с текущим URL
            store.updateFromURL();

            // Обновляем состояние сайдбара на основе store
            updateCategorySidebarState(store.filters.category);

            // Перезагружаем контент
            reloadBlogContent(blogContainer, ajaxUrl, { scrollToTop: false });
            return;
          }
        } catch (e) {
          console.warn('Store sync failed during popstate, falling back to URL parsing');
        }
      }

      console.log('Popstate event triggered, reloading content (fallback)');

      // Fallback: обновляем состояние сайдбара на основе текущего URL
      const urlParams = new URLSearchParams(window.location.search);
      const categorySlug = urlParams.get('category') || '';
      updateCategorySidebarState(categorySlug);

      // Перезагружаем контент
      reloadBlogContent(blogContainer, ajaxUrl, { scrollToTop: false });
    }
  });

  // Инициализируем обработчики кликов по пагинации
  initPaginationClickHandlers();
}

/**
 * Инициализация состояния сайдбара на основе URL
 * Зачем: корректное отображение активной категории при загрузке страницы
 * MIGRATED: Полностью использует centralized store для state management
 */
function initSidebarState() {
  if (typeof Alpine !== 'undefined' && Alpine.store) {
    try {
      const store = Alpine.store('blog');
      if (store) {
        // Синхронизируем store с URL при загрузке страницы
        store.updateFromURL();

        // Обновляем состояние сайдбара на основе store
        updateCategorySidebarState(store.filters.category);

        console.log('Sidebar state initialized via store:', {
          category: store.filters.category,
          page: store.filters.page,
          search: store.filters.search,
        });

        return;
      }
    } catch (e) {
      console.warn('Store not available for sidebar state, falling back to URL params');
    }
  }

  // Fallback: get category from URL directly (для совместимости)
  console.log('Using fallback URL parsing for sidebar state');
  const urlParams = new URLSearchParams(window.location.search);
  const categorySlug = urlParams.get('category') || '';
  updateCategorySidebarState(categorySlug);
}

/**
 * Оптимизированная инициализация поиска
 * Зачем: интеграция поиска с общей системой состояний
 */
// function initOptimizedBlogSearch() {
//   // Новый поиск в блоке фильтров
//   const filterSearchInput = document.querySelector('.blog-filter-search input[type="search"]');

//   // Старый поиск в форме
//   const searchForm = document.querySelector('.search-form form');
//   const searchInput = document.querySelector('.search-form input[type="search"]');

//   // Инициализируем новый поиск в блоке фильтров
//   if (filterSearchInput) {
//     initFilterSearch(filterSearchInput);
//   }

//   // Поддерживаем старый поиск для обратной совместимости
//   if (!searchForm || !searchInput) {
//     return;
//   }

//   // Обработчик изменения поискового запроса
//   let searchTimeout;
//   searchInput.addEventListener('input', function (e) {
//     const query = searchInput.value.trim();

//     // Очищаем предыдущий таймаут
//     clearTimeout(searchTimeout);

//     // Если запрос короткий, возвращаемся к обычному режиму
//     if (query.length < 3) {
//       const blogContainer = document.getElementById('blog-articles-container');
//       const ajaxUrl = blogContainer?.getAttribute('data-blog-ajax-url');

//       if (ajaxUrl) {
//         // Убираем параметр поиска из URL
//         const urlParams = new URLSearchParams(window.location.search);
//         urlParams.delete('search');
//         const newUrl =
//           window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
//         window.history.pushState({}, '', newUrl);

//         // Перезагружаем контент
//         reloadBlogContent(blogContainer, ajaxUrl);
//       }
//       return;
//     }

//     // Устанавливаем задержку для избежания частых запросов
//     searchTimeout = setTimeout(() => {
//       performIntegratedSearch(query);
//     }, 300);
//   });

//   // Обработчик отправки формы поиска
//   searchForm.addEventListener('submit', function (e) {
//     e.preventDefault();
//     const query = searchInput.value.trim();

//     if (query.length >= 3) {
//       // Переходим на страницу поиска
//       const url = new URL(window.location.href);
//       url.pathname = '/blog/search';
//       url.searchParams.set('q', query);
//       window.location.href = url.toString();
//     }
//   });

//   // Обработчик ESC для сброса поиска
//   document.addEventListener('keydown', function (event) {
//     if (event.key === 'Escape' && searchInput.value.trim()) {
//       searchInput.value = '';
//       const inputEvent = new Event('input');
//       searchInput.dispatchEvent(inputEvent);
//     }
//   });
// }

/**
 * Инициализация поиска в блоке фильтров
 * Зачем: новый дизайн поиска с интеграцией в систему фильтров
 * REFACTORED: Now uses store state with safety checks
 */
function initFilterSearch(searchInput) {
  // Устанавливаем значение из store если доступен
  if (typeof Alpine !== 'undefined' && Alpine.store) {
    try {
      const store = Alpine.store('blog');
      if (store) {
        store.updateFromURL();
        if (store.filters.search) {
          searchInput.value = store.filters.search;
        }
      }
    } catch (e) {
      console.warn('Store not available for search init, falling back to URL params');
    }
  }

  // Fallback: get search from URL directly if store not available
  if (!searchInput.value) {
    const urlParams = new URLSearchParams(window.location.search);
    const searchParam = urlParams.get('search');
    if (searchParam) {
      searchInput.value = searchParam;
    }
  }

  // Debounced обработчик поиска
  let searchTimeout;
  searchInput.addEventListener('input', function (e) {
    const query = e.target.value.trim();

    // Очищаем предыдущий таймаут
    clearTimeout(searchTimeout);

    // Устанавливаем задержку для избежания частых запросов
    searchTimeout = setTimeout(() => {
      handleFilterSearch(query);
    }, 300);
  });

  // Обработчик Enter
  searchInput.addEventListener('keypress', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      clearTimeout(searchTimeout);
      const query = e.target.value.trim();
      handleFilterSearch(query);
    }
  });
}

/**
 * Обработка поиска из блока фильтров
 * Зачем: централизованная обработка поиска с обновлением URL
 * REFACTORED: Now uses store and ajax manager
 */
function handleFilterSearch(query) {
  const blogContainer = document.getElementById('blog-articles-container');
  const ajaxUrl = blogContainer?.getAttribute('data-blog-ajax-url');

  if (!ajaxUrl) return;

  console.log('Filter search triggered:', query);

  // Use ajax manager for search
  blogAjaxManager.setSearch(query);

  // Перезагружаем контент
  reloadBlogContent(blogContainer, ajaxUrl);
}

/**
 * Выполнение интегрированного поиска через основную систему
 * Зачем: единая обработка всех типов запросов
 */
function performIntegratedSearch(query) {
  const blogContainer = document.getElementById('blog-articles-container');
  const ajaxUrl = blogContainer?.getAttribute('data-blog-ajax-url');

  if (!ajaxUrl) return;

  // Обновляем URL с параметром поиска
  const urlParams = new URLSearchParams(window.location.search);
  urlParams.set('search', query);
  urlParams.delete('page'); // Сбрасываем страницу при поиске
  urlParams.delete('category'); // Сбрасываем категорию при поиске

  const newUrl = window.location.pathname + '?' + urlParams.toString();
  window.history.pushState({ search: query }, '', newUrl);

  // Сбрасываем состояние категорий
  updateCategorySidebarState('');

  // Перезагружаем контент
  reloadBlogContent(blogContainer, ajaxUrl);
}

/**
 * Инициализация сортировки блога
 * Зачем: функциональность сортировки статей без перезагрузки страницы
 */
function initBlogSorting() {
  const sortingButtons = document.querySelectorAll('.sorting-btn');

  if (sortingButtons.length === 0) {
    console.log('No sorting buttons found');
    return;
  }

  console.log('Initializing blog sorting with', sortingButtons.length, 'buttons');

  // Инициализируем состояние кнопок на основе URL
  updateSortingButtonsState();

  // Добавляем обработчики для кнопок сортировки
  sortingButtons.forEach(button => {
    button.addEventListener('click', function (e) {
      e.preventDefault();
      handleSortingClick(this);
    });
  });
}

/**
 * Обработка клика по кнопке сортировки
 * Зачем: переключение сортировки и обновление контента
 * REFACTORED: Now uses store and ajax manager
 */
function handleSortingClick(button) {
  // Check if store is available and loading
  if (typeof Alpine !== 'undefined' && Alpine.store) {
    try {
      const store = Alpine.store('blog');
      if (store && store.loading) {
        console.log('Request already in progress, ignoring sort click');
        return;
      }
    } catch (e) {
      // Continue without store check
    }
  }

  const blogContainer = document.getElementById('blog-articles-container');
  const ajaxUrl = blogContainer?.getAttribute('data-blog-ajax-url');

  if (!ajaxUrl) {
    console.error('No AJAX URL found for sorting');
    return;
  }

  // Определяем тип сортировки на основе текста кнопки
  let sortType;
  const buttonText = button.textContent.trim().toLowerCase();

  if (buttonText.includes('популярные')) {
    sortType = 'popular';
  } else if (buttonText.includes('просматрыв')) {
    sortType = 'views';
  } else {
    sortType = 'latest'; // По умолчанию
  }

  // Get current sort state from store or URL fallback
  let currentSort = 'latest';
  let currentDirection = 'desc';

  if (typeof Alpine !== 'undefined' && Alpine.store) {
    try {
      const store = Alpine.store('blog');
      if (store) {
        currentSort = store.filters.sort;
        currentDirection = store.filters.direction;
      }
    } catch (e) {
      // Fallback to URL params
      const urlParams = new URLSearchParams(window.location.search);
      currentSort = urlParams.get('sort') || 'latest';
      currentDirection = urlParams.get('direction') || 'desc';
    }
  } else {
    // Fallback to URL params
    const urlParams = new URLSearchParams(window.location.search);
    currentSort = urlParams.get('sort') || 'latest';
    currentDirection = urlParams.get('direction') || 'desc';
  }

  // Определяем новое направление сортировки
  let newDirection;
  if (currentSort === sortType) {
    // Переключаем направление для той же сортировки
    newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
  } else {
    // Устанавливаем направление по умолчанию для новой сортировки
    newDirection = sortType === 'popular' || sortType === 'views' ? 'desc' : 'desc';
  }

  console.log('Sorting:', { sortType, newDirection, currentSort, currentDirection });

  // Use ajax manager for sorting
  blogAjaxManager.setSort(sortType, newDirection);

  // Обновляем состояние кнопок (preserve existing DOM approach)
  updateSortingButtonsState(sortType, newDirection);

  // Перезагружаем контент
  reloadBlogContent(blogContainer, ajaxUrl);
}

/**
 * Обновление состояния кнопок сортировки
 * Зачем: визуальная индикация текущей сортировки
 */
function updateSortingButtonsState(sortType = null, direction = null) {
  const urlParams = new URLSearchParams(window.location.search);
  const currentSort = sortType || urlParams.get('sort') || 'latest';
  const currentDirection = direction || urlParams.get('direction') || 'desc';

  console.log('Updating sorting buttons state:', { currentSort, currentDirection });

  // Убираем активные классы со всех кнопок
  document.querySelectorAll('.sorting-btn').forEach(button => {
    button.classList.remove('asc', 'desc');
  });

  // Устанавливаем активное состояние для соответствующей кнопки
  document.querySelectorAll('.sorting-btn').forEach(button => {
    const buttonText = button.textContent.trim().toLowerCase();
    let buttonType = 'latest';

    if (buttonText.includes('популярные')) {
      buttonType = 'popular';
    } else if (buttonText.includes('просматрыв')) {
      buttonType = 'views';
    }

    if (buttonType === currentSort) {
      button.classList.add(currentDirection);
      console.log('Set button state:', { buttonType, currentDirection });
    }
  });
}

/**
 * Инициализация обработчика изменения размера окна
 * Зачем: корректная работа компонентов при изменении размера
 */
function initResponsiveHandlers() {
  let resizeTimeout;

  window.addEventListener('resize', function () {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(function () {
      // Переинициализируем карусели при изменении размера
      reinitializeComponents();
    }, 250);
  });
}

/**
 * Инициализация системы предзагрузки
 * Зачем: улучшение пользовательского опыта
 */
function initPreloadSystem() {
  // Предзагрузка следующей страницы при наведении на ссылку пагинации
  document.addEventListener(
    'mouseenter',
    function (e) {
      // Add check for e.target and ensure it is a Node and has closest method
      if (!e.target || typeof e.target.closest !== 'function') return;

      const paginationLink = e.target.closest('#blog-pagination-container .pagination-list a');

      // Check if store is available and loading
      let isStoreLoading = false;
      if (typeof Alpine !== 'undefined' && Alpine.store) {
        try {
          const store = Alpine.store('blog');
          isStoreLoading = store && store.loading;
        } catch (e) {
          // Continue without store check
        }
      }

      if (!paginationLink || isStoreLoading) return;

      const url = new URL(paginationLink.href);
      const page = url.searchParams.get('page');

      if (page) {
        // Простая предзагрузка через создание link тега
        const linkTag = document.createElement('link');
        linkTag.rel = 'prefetch';
        linkTag.href = paginationLink.href;
        document.head.appendChild(linkTag);

        // Удаляем тег через 5 секунд
        setTimeout(() => {
          if (linkTag.parentNode) {
            linkTag.parentNode.removeChild(linkTag);
          }
        }, 5000);
      }
    },
    true
  );
}

/**
 * Основная функция инициализации
 * Зачем: централизованная настройка всех компонентов
 * REFACTORED: Now includes store initialization with proper timing
 */
document.addEventListener('DOMContentLoaded', function () {
  console.log('Initializing blog functionality...');

  // Инициализируем основные компоненты (работают без store)
  initBlogPagination();
  initCategoryFiltering();
  initSidebarState();
  initOptimizedBlogSearch();
  initBlogSorting();
  initResponsiveHandlers();
  initPreloadSystem();

  // Инициализируем компоненты комментариев
  initCommentPagination();
  initAlsowInterestingArticlesCarousel();
  initReadOftenArticlesCarousel();

  console.log('Blog functionality initialized successfully');
});

// Initialize store-dependent functionality when Alpine is ready
document.addEventListener('alpine:ready', function () {
  console.log('Alpine is ready, initializing store-dependent functionality...');
  blogAjaxManager.initFromURL();
});

// Fallback initialization for cases where Alpine might be started elsewhere
document.addEventListener('alpine:init', function () {
  console.log('Alpine initialized, setting up store...');
  setTimeout(() => {
    blogAjaxManager.initFromURL();
  }, 100);
});

// Экспорт основных функций для внешнего использования
export {
  initBlogPagination,
  initCategoryFiltering,
  initPaginationClickHandlers,
  reloadBlogContent,
  updateCategorySidebarState,
  validateRequestParams,
};
