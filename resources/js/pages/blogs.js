import {
  initAlsowInterestingArticlesCarousel,
  initCommentPagination,
  initReadOftenArticlesCarousel,
  initReplyButtons,
  initUniversalCommentForm,
} from '@/components/blogs';
import { hideInElement, showInElement } from '../components/loader';
import { updateBrowserUrl } from '../helpers/update-browser-url';

// Глобальные переменные для состояния
let isLoading = false;
let currentRequest = null;
let retryCount = 0;
const MAX_RETRIES = 3;
const RETRY_DELAY = 1000;

/**
 * Главная функция для перезагрузки контента блога
 * Зачем: централизованная обработка всех AJAX запросов с retry логикой и валидацией
 */
function reloadBlogContent(container, url, options = {}) {
  const {
    scrollToTop = true,
    showLoader = true,
    validateParams = true,
    retryOnError = true,
  } = options;

  // Предотвращаем множественные одновременные запросы
  if (isLoading && currentRequest) {
    currentRequest.abort();
  }

  if (validateParams && !validateRequestParams()) {
    console.warn('Invalid request parameters detected, redirecting to clean state');
    cleanRedirect();
    return;
  }

  console.log('Reloading blog content...', { url, options });

  isLoading = true;
  const loader = showLoader ? showInElement(container) : null;

  // Строим URL с текущими параметрами
  const requestUrl = buildRequestUrl(url);

  // Создаем AbortController для возможности отмены запроса
  const controller = new AbortController();
  currentRequest = controller;

  // Делаем AJAX запрос
  fetch(requestUrl, {
    method: 'GET',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN':
        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    signal: controller.signal,
  })
    .then(response => {
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      return response.json();
    })
    .then(data => {
      console.log('AJAX response received:', data);
      retryCount = 0; // Сбрасываем счетчик попыток при успехе

      // Обрабатываем редирект
      if (data.redirect) {
        handleRedirectResponse(data, container, url, options);
        return;
      }

      // Обрабатываем ошибку валидации
      if (data.error) {
        console.error('Validation error:', data.error);
        cleanRedirect();
        return;
      }

      // Обновляем контент
      updatePageContent(data, container, scrollToTop);
    })
    .catch(error => {
      if (error.name === 'AbortError') {
        console.log('Request was aborted');
        return;
      }

      console.error('Error fetching blog articles:', error);

      if (retryOnError && retryCount < MAX_RETRIES) {
        retryCount++;
        console.log(`Retrying request (${retryCount}/${MAX_RETRIES})...`);
        setTimeout(() => {
          reloadBlogContent(container, url, options);
        }, RETRY_DELAY * retryCount);
      } else {
        showErrorMessage(container, error);
      }
    })
    .finally(() => {
      isLoading = false;
      currentRequest = null;
      if (loader) {
        hideInElement(loader);
      }
    });
}

/**
 * Валидация параметров запроса
 * Зачем: предотвращение некорректных состояний URL
 */
function validateRequestParams() {
  const urlParams = new URLSearchParams(window.location.search);
  const page = parseInt(urlParams.get('page')) || 1;
  const category = urlParams.get('category');
  const search = urlParams.get('search');
  const sort = urlParams.get('sort');
  const direction = urlParams.get('direction');

  // Проверяем валидность номера страницы
  if (page < 1 || page > 1000) {
    return false;
  }

  // Проверяем длину поискового запроса
  if (search && (search.length > 255 || search.length < 1)) {
    return false;
  }

  // Проверяем валидность категории (базовая проверка на спецсимволы)
  if (category && !/^[a-zA-Z0-9\-_]+$/.test(category)) {
    return false;
  }

  // Проверяем валидность параметров сортировки
  if (sort && !['latest', 'popular', 'views'].includes(sort)) {
    return false;
  }

  if (direction && !['asc', 'desc'].includes(direction)) {
    return false;
  }

  return true;
}

/**
 * Построение URL запроса с валидацией
 * Зачем: корректное формирование запросов к API
 */
function buildRequestUrl(baseUrl) {
  const currentUrl = new URL(window.location.href);
  const requestUrl = new URL(baseUrl, window.location.origin);

  // Копируем только валидные параметры
  const validParams = ['page', 'category', 'search', 'sort', 'direction'];
  validParams.forEach(param => {
    const value = currentUrl.searchParams.get(param);
    if (value) {
      requestUrl.searchParams.set(param, value);
    }
  });

  return requestUrl.toString();
}

/**
 * Обработка ответа с редиректом
 * Зачем: корректная обработка серверных редиректов
 */
function handleRedirectResponse(data, container, url, options) {
  console.log('Handling redirect to:', data.url);

  const redirectUrl = new URL(data.url);
  const redirectParams = new URLSearchParams(redirectUrl.search);

  // Обновляем состояние браузера
  const stateData = {
    category: redirectParams.get('category') || '',
    page: redirectParams.get('page') || '1',
  };

  window.history.pushState(stateData, '', data.url);

  // Обновляем состояние сайдбара
  updateCategorySidebarState(stateData.category);

  // Перезагружаем контент с новым URL
  reloadBlogContent(container, url, { ...options, showLoader: false });
}

/**
 * Обновление контента страницы
 * Зачем: безопасное обновление DOM с проверками
 */
function updatePageContent(data, container, scrollToTop) {
  try {
    // Обновляем основной контент
    if (data.html) {
      container.innerHTML = data.html;
    }

    // Обновляем классы контейнера в зависимости от результатов
    if (data.count === 0 && data.totalCount === 0) {
      container.classList.add('blog-list__no-results');
    } else {
      container.classList.remove('blog-list__no-results');
    }

    // Обновляем пагинацию
    updatePaginationContent(data);

    // Переинициализируем компоненты
    reinitializeComponents();

    // Прокручиваем к началу если нужно
    if (scrollToTop) {
      container.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Обновляем URL для SEO
    updateUrlForSEO(data);
  } catch (error) {
    console.error('Error updating page content:', error);
    showErrorMessage(container, error);
  }
}

/**
 * Обновление контента пагинации
 * Зачем: корректная работа с пагинацией без перезагрузки
 */
function updatePaginationContent(data) {
  const paginationContainer = document.getElementById('blog-pagination-container');
  if (!paginationContainer) return;

  if (data.hasPagination && data.pagination) {
    paginationContainer.innerHTML = data.pagination;
    paginationContainer.style.display = 'block';
    initPaginationClickHandlers();
  } else {
    paginationContainer.innerHTML = '';
    paginationContainer.style.display = 'none';
  }
}

/**
 * Переинициализация компонентов после обновления DOM
 * Зачем: восстановление функциональности после изменения контента
 */
function reinitializeComponents() {
  try {
    // Уничтожаем существующие slick карусели перед переинициализацией
    destroyExistingCarousels();

    // Переинициализируем карусели через универсальную функцию
    initUniversalCarousels();
  } catch (error) {
    console.error('Error reinitializing components:', error);
  }
}

/**
 * Уничтожение существующих каруселей
 * Зачем: предотвращение конфликтов при переинициализации
 */
function destroyExistingCarousels() {
  const carousels = [
    '#alsow-interesting-articles-carousel-container',
    '#read-often-articles-carousel-container',
  ];

  carousels.forEach(selector => {
    const $carousel = $(selector);
    if ($carousel.length && $carousel.hasClass('slick-initialized')) {
      try {
        $carousel.slick('destroy');
      } catch (error) {
        console.warn(`Error destroying carousel ${selector}:`, error);
      }
    }
  });
}

/**
 * Чистый редирект без параметров
 * Зачем: возврат к базовому состоянию при ошибках
 */
function cleanRedirect() {
  const cleanUrl = new URL(window.location.pathname, window.location.origin);
  window.history.pushState({}, '', cleanUrl.toString());
  window.location.reload();
}

/**
 * Показ сообщения об ошибке
 * Зачем: информирование пользователя о проблемах
 */
function showErrorMessage(container, error) {
  const errorHtml = `
    <div class="blog-error-message empty-landing">
      <h3>Произошла ошибка при загрузке</h3>
      <p>Пожалуйста, обновите страницу или попробуйте позже.</p>
      <button onclick="window.location.reload()" class="btn _flex _green _medium min-120">Обновить страницу</button>
    </div>
  `;
  container.innerHTML = errorHtml;
}

/**
 * Обновление URL для SEO
 * Зачем: поддержка корректных URL для поисковых систем
 */
function updateUrlForSEO(data) {
  if (data.currentCategory) {
    document.title = `${data.currentCategory.name} - Блог`;
  } else if (data.totalCount !== undefined) {
    document.title = `Блог - ${data.totalCount} статей`;
  }
}

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
 */
function handlePaginationClick(event) {
  event.preventDefault();

  if (isLoading) {
    console.log('Request already in progress, ignoring click');
    return;
  }

  // Ищем ближайшую ссылку в пределах пагинации
  let targetLink = event.target;

  // Если кликнули не на ссылку, ищем родительскую ссылку в пределах пагинации
  if (targetLink.tagName !== 'A') {
    targetLink = targetLink.closest('#blog-pagination-container .pagination-list a');
  }

  // Проверяем что нашли валидную ссылку
  if (!targetLink || targetLink.tagName !== 'A') {
    console.log('No valid pagination link found, ignoring click');
    return;
  }

  // Проверяем валидность href
  const href = targetLink.href;
  console.log('href', targetLink.tagName, href);
  if (!href || href === '' || href === '#') {
    console.log('Invalid or empty href, ignoring click');
    return;
  }

  const url = new URL(href);
  const page = parseInt(url.searchParams.get('page')) || 1;

  // Валидация номера страницы
  if (page < 1 || page > 1000) {
    console.error('Invalid page number:', page);
    return;
  }

  const blogContainer = document.getElementById('blog-articles-container');
  const ajaxUrl = blogContainer?.getAttribute('data-blog-ajax-url');

  if (!ajaxUrl) {
    console.error('No AJAX URL found');
    return;
  }

  console.log('Navigating to page:', page);

  // Обновляем URL браузера
  updateBrowserUrl({ page: page });

  // Перезагружаем контент
  reloadBlogContent(blogContainer, ajaxUrl);
}

/**
 * Инициализация фильтрации по категориям
 * Зачем: AJAX фильтрация без перезагрузки
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

    if (isLoading) {
      console.log('Request already in progress, ignoring category click');
      return;
    }

    const categorySlug = categoryLink.getAttribute('data-category-slug') || '';

    console.log('Category clicked:', categorySlug || 'all');

    // Обновляем активное состояние в сайдбаре
    updateCategorySidebarState(categorySlug);

    // Формируем новые параметры URL
    const urlParams = new URLSearchParams(window.location.search);

    if (categorySlug) {
      urlParams.set('category', categorySlug);
    } else {
      urlParams.delete('category');
    }

    urlParams.delete('page'); // Сбрасываем на первую страницу при смене категории

    // Обновляем URL браузера
    const newUrl =
      window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
    window.history.pushState(
      {
        category: categorySlug,
        page: '1',
      },
      '',
      newUrl
    );

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
  window.addEventListener('popstate', function (event) {
    if (blogContainer && ajaxUrl) {
      // Предотвращаем обработку если уже идет загрузка
      if (isLoading) {
        return;
      }

      console.log('Popstate event triggered, reloading content');

      // Обновляем состояние сайдбара на основе текущего URL
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
 */
function initSidebarState() {
  const urlParams = new URLSearchParams(window.location.search);
  const categorySlug = urlParams.get('category') || '';
  updateCategorySidebarState(categorySlug);
}

/**
 * Оптимизированная инициализация поиска
 * Зачем: интеграция поиска с общей системой состояний
 */
function initOptimizedBlogSearch() {
  // Новый поиск в блоке фильтров
  const filterSearchInput = document.querySelector('.blog-filter-search input[type="search"]');

  // Старый поиск в форме
  const searchForm = document.querySelector('.search-form form');
  const searchInput = document.querySelector('.search-form input[type="search"]');

  // Инициализируем новый поиск в блоке фильтров
  if (filterSearchInput) {
    initFilterSearch(filterSearchInput);
  }

  // Поддерживаем старый поиск для обратной совместимости
  if (!searchForm || !searchInput) {
    return;
  }

  // Обработчик изменения поискового запроса
  let searchTimeout;
  searchInput.addEventListener('input', function (e) {
    const query = searchInput.value.trim();

    // Очищаем предыдущий таймаут
    clearTimeout(searchTimeout);

    // Если запрос короткий, возвращаемся к обычному режиму
    if (query.length < 3) {
      const blogContainer = document.getElementById('blog-articles-container');
      const ajaxUrl = blogContainer?.getAttribute('data-blog-ajax-url');

      if (ajaxUrl) {
        // Убираем параметр поиска из URL
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.delete('search');
        const newUrl =
          window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
        window.history.pushState({}, '', newUrl);

        // Перезагружаем контент
        reloadBlogContent(blogContainer, ajaxUrl);
      }
      return;
    }

    // Устанавливаем задержку для избежания частых запросов
    searchTimeout = setTimeout(() => {
      performIntegratedSearch(query);
    }, 300);
  });

  // Обработчик отправки формы поиска
  searchForm.addEventListener('submit', function (e) {
    e.preventDefault();
    const query = searchInput.value.trim();

    if (query.length >= 3) {
      // Переходим на страницу поиска
      const url = new URL(window.location.href);
      url.pathname = '/blog/search';
      url.searchParams.set('q', query);
      window.location.href = url.toString();
    }
  });

  // Обработчик ESC для сброса поиска
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && searchInput.value.trim()) {
      searchInput.value = '';
      const event = new Event('input');
      searchInput.dispatchEvent(event);
    }
  });
}

/**
 * Инициализация поиска в блоке фильтров
 * Зачем: новый дизайн поиска с интеграцией в систему фильтров
 */
function initFilterSearch(searchInput) {
  // Устанавливаем значение из URL если есть
  const urlParams = new URLSearchParams(window.location.search);
  const searchParam = urlParams.get('search');
  if (searchParam) {
    searchInput.value = searchParam;
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
 */
function handleFilterSearch(query) {
  const blogContainer = document.getElementById('blog-articles-container');
  const ajaxUrl = blogContainer?.getAttribute('data-blog-ajax-url');

  if (!ajaxUrl) return;

  console.log('Filter search triggered:', query);

  // Обновляем URL параметры
  const urlParams = new URLSearchParams(window.location.search);

  if (query.length > 0) {
    urlParams.set('search', query);
  } else {
    urlParams.delete('search');
  }

  // Сбрасываем страницу при поиске
  urlParams.delete('page');

  // Обновляем URL браузера
  const newUrl =
    window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
  window.history.pushState({ search: query }, '', newUrl);

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
 */
function handleSortingClick(button) {
  if (isLoading) {
    console.log('Request already in progress, ignoring sort click');
    return;
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

  // Получаем текущие параметры URL
  const urlParams = new URLSearchParams(window.location.search);
  const currentSort = urlParams.get('sort') || 'latest';
  const currentDirection = urlParams.get('direction') || 'desc';

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

  // Обновляем URL параметры
  urlParams.set('sort', sortType);
  urlParams.set('direction', newDirection);
  urlParams.delete('page'); // Сбрасываем на первую страницу

  // Обновляем состояние кнопок
  updateSortingButtonsState(sortType, newDirection);

  // Обновляем URL браузера
  const newUrl =
    window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
  window.history.pushState({ sort: sortType, direction: newDirection }, '', newUrl);

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
      if (!paginationLink || isLoading) return;

      // Проверяем валидность href перед созданием URL
      const href = paginationLink.href;
      if (!href || href === '' || href === '#') return;

      const url = new URL(href);
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
 * Инициализация компонентов для страницы списка статей
 * Зачем: функциональность специфичная для /blog
 */
function initBlogListPage() {
  const blogContainer = document.getElementById('blog-articles-container');
  if (!blogContainer) return false;

  console.log('Initializing blog list page functionality...');

  // Инициализируем компоненты только для страницы списка
  initBlogPagination();
  initCategoryFiltering();
  initSidebarState();
  initOptimizedBlogSearch();
  initBlogSorting();
  initPreloadSystem();

  return true;
}

/**
 * Инициализация компонентов для страницы отдельной статьи
 * Зачем: функциональность специфичная для /blog/{slug}
 */
function initBlogShowPage() {
  const articleContainer = document.querySelector('.article._big._single');
  if (!articleContainer) return false;

  console.log('Initializing blog show page functionality...');

  // Здесь можно добавить специфичную логику для страницы статьи
  // Пока что просто возвращаем true
  return true;
}

/**
 * Универсальная инициализация каруселей
 * Зачем: карусели могут быть на любой странице блога
 */
function initUniversalCarousels() {
  console.log('Initializing universal carousels...');

  // Инициализируем карусели независимо от типа страницы
  const alsowCarouselResult = initAlsowInterestingArticlesCarousel();
  const readOftenCarouselResult = initReadOftenArticlesCarousel();

  console.log('Carousel initialization results:', {
    alsowCarousel: alsowCarouselResult,
    readOftenCarousel: readOftenCarouselResult,
  });
}

/**
 * Универсальная инициализация комментариев
 * Зачем: комментарии есть на странице отдельной статьи
 */
function initUniversalComments() {
  // Инициализируем компоненты комментариев
  initCommentPagination();

  // Инициализируем форму комментария если она существует
  const commentForm = $('#universal-comment-form');
  if (commentForm.length) {
    initUniversalCommentForm(commentForm);
    initReplyButtons(commentForm);
  }
}

/**
 * Основная функция инициализации
 * Зачем: централизованная настройка всех компонентов с определением типа страницы
 */
document.addEventListener('DOMContentLoaded', function () {
  console.log('Initializing blog functionality...');

  // Определяем тип страницы и инициализируем соответствующие компоненты
  const isListPage = initBlogListPage();
  const isShowPage = initBlogShowPage();

  // Универсальные компоненты для всех страниц блога (только один раз)
  initResponsiveHandlers();

  // Инициализируем карусели только один раз с задержкой
  setTimeout(() => {
    initUniversalCarousels();
  }, 200);

  initUniversalComments();

  console.log('Blog functionality initialized successfully', {
    isListPage,
    isShowPage,
    currentPath: window.location.pathname,
  });
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
