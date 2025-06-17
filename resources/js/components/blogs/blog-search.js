import { debounce } from '@/helpers';
// DEBRICATED AFTER NEW IMPLEMENTATION NEED TO BE DELETED
/**
 * Класс для управления поиском в блоге
 * Зачем: централизованное управление состоянием поиска и интеграция с основной системой
 */
class BlogSearchManager {
  constructor() {
    this.originalBlogListHtml = '';
    this.originalPaginationHtml = '';
    this.isSearchActive = false;
    this.isLoading = false;
    this.currentSearchQuery = '';
    this.searchAbortController = null;

    // Кеш для поисковых запросов
    this.searchCache = new Map();
    this.maxCacheSize = 50;

    // Селекторы элементов
    this.selectors = {
      searchForm: '.search-form form',
      searchInput: '.search-form input[type="search"]',
      searchButton: '.search-button',
      blogList: '.blog-list',
      paginationList: '.pagination-list',
      searchResults: '.search-results',
    };

    // Настройки поиска
    this.config = {
      minQueryLength: 3,
      debounceDelay: 300,
      cacheTimeout: 300000, // 5 минут
      maxRetries: 3,
    };
  }

  /**
   * Инициализация поиска
   * Зачем: настройка всех обработчиков и начального состояния
   */
  init() {
    this.cacheOriginalContent();
    this.bindEvents();
    this.handleInitialSearchQuery();
    console.log('Blog search manager initialized');
  }

  /**
   * Кеширование оригинального контента
   * Зачем: сохранение состояния для возврата после поиска
   */
  cacheOriginalContent() {
    const blogList = $(this.selectors.blogList);
    const paginationList = $(this.selectors.paginationList);

    if (blogList.length) {
      this.originalBlogListHtml = blogList.html();
    }

    if (paginationList.length) {
      this.originalPaginationHtml = paginationList.html();
    }
  }

  /**
   * Привязка событий
   * Зачем: настройка всех необходимых обработчиков
   */
  bindEvents() {
    const searchForm = $(this.selectors.searchForm);
    const searchInput = $(this.selectors.searchInput);

    if (!searchForm.length || !searchInput.length) {
      console.warn('Search form or input not found');
      return;
    }

    // Debounced поиск при вводе
    const debouncedSearch = debounce(query => {
      this.handleSearchInput(query);
    }, this.config.debounceDelay);

    searchInput.on('input', e => {
      const query = searchInput.val().trim();
      this.currentSearchQuery = query;
      debouncedSearch(query);
    });

    // Обработка отправки формы
    searchForm.on('submit', e => {
      e.preventDefault();
      this.handleSearchSubmit();
    });

    // Сброс поиска по ESC
    $(document).on('keydown', e => {
      if (e.key === 'Escape' && this.isSearchActive) {
        this.clearSearch();
      }
    });

    // Обработка фокуса на поле поиска
    searchInput.on('focus', () => {
      this.handleSearchFocus();
    });
  }

  /**
   * Обработка ввода в поле поиска
   * Зачем: выполнение поиска или сброс к оригинальному контенту
   */
  handleSearchInput(query) {
    if (query.length === 0) {
      this.resetToOriginalContent();
      return;
    }

    if (query.length < this.config.minQueryLength) {
      // Показываем подсказку о минимальной длине запроса
      this.showSearchHint(`Введите минимум ${this.config.minQueryLength} символа для поиска`);
      return;
    }

    this.performSearch(query);
  }

  /**
   * Выполнение поиска
   * Зачем: основная логика поискового запроса
   */
  async performSearch(query) {
    if (this.isLoading) {
      this.cancelCurrentSearch();
    }

    // Проверяем кеш
    const cachedResult = this.getFromCache(query);
    if (cachedResult) {
      this.displaySearchResults(cachedResult, query);
      return;
    }

    this.setLoadingState(true);
    this.isSearchActive = true;

    try {
      const result = await this.executeSearchRequest(query);

      if (result.success) {
        this.putToCache(query, result.data);
        this.displaySearchResults(result.data, query);
      } else {
        this.showErrorMessage('Ошибка поиска. Попробуйте еще раз.');
      }
    } catch (error) {
      if (error.name !== 'AbortError') {
        console.error('Search error:', error);
        this.showErrorMessage('Произошла ошибка при поиске');
      }
    } finally {
      this.setLoadingState(false);
    }
  }

  /**
   * Выполнение HTTP запроса для поиска
   * Зачем: отделение сетевой логики от логики обработки
   */
  async executeSearchRequest(query) {
    // Отменяем предыдущий запрос если есть
    this.cancelCurrentSearch();

    this.searchAbortController = new AbortController();

    const url = new URL(window.location.href);
    url.pathname = '/api/blog/search';
    url.searchParams.set('q', encodeURIComponent(query));

    const response = await fetch(url.toString(), {
      method: 'GET',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
        'X-CSRF-TOKEN':
          document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      signal: this.searchAbortController.signal,
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    return await response.json();
  }

  /**
   * Отмена текущего поискового запроса
   * Зачем: предотвращение конфликтов при быстром вводе
   */
  cancelCurrentSearch() {
    if (this.searchAbortController) {
      this.searchAbortController.abort();
      this.searchAbortController = null;
    }
  }

  /**
   * Отображение результатов поиска
   * Зачем: обновление DOM с результатами поиска
   */
  displaySearchResults(data, query) {
    const blogList = $(this.selectors.blogList);
    const paginationList = $(this.selectors.paginationList);
    const searchResults = $(this.selectors.searchResults);

    // Скрываем пагинацию при поиске
    paginationList.hide();

    if (data.total > 0) {
      blogList.html(data.html);
      blogList.show();
      this.showSearchInfo(searchResults, `Найдено результатов: ${data.total}`, 'success');
    } else {
      blogList.html(data.html || this.getNoResultsHtml(query));
      blogList.show();
      this.showSearchInfo(searchResults, 'По вашему запросу ничего не найдено', 'warning');
    }

    searchResults.show();

    // Обновляем URL для отслеживания состояния (но не делаем полный редирект)
    this.updateSearchUrl(query);
  }

  /**
   * Показ информации о поиске
   * Зачем: информирование пользователя о состоянии поиска
   */
  showSearchInfo(searchResults, message, type = 'info') {
    if (!searchResults.length) return;

    const infoElement = searchResults.find('.search-info');
    if (infoElement.length) {
      infoElement
        .text(message)
        .removeClass('search-info--success search-info--warning search-info--error')
        .addClass(`search-info--${type}`);
    }
  }

  /**
   * Показ подсказки поиска
   * Зачем: помощь пользователю в использовании поиска
   */
  showSearchHint(message) {
    const searchResults = $(this.selectors.searchResults);
    if (searchResults.length) {
      this.showSearchInfo(searchResults, message, 'info');
      searchResults.show();
    }
  }

  /**
   * Установка состояния загрузки
   * Зачем: визуальная индикация процесса поиска
   */
  setLoadingState(isLoading) {
    this.isLoading = isLoading;
    const searchResults = $(this.selectors.searchResults);

    if (isLoading) {
      searchResults.show();
      this.showSearchInfo(searchResults, 'Поиск...', 'info');

      // Добавляем индикатор загрузки к кнопке поиска
      const searchButton = $(this.selectors.searchButton);
      searchButton.addClass('loading');
    } else {
      const searchButton = $(this.selectors.searchButton);
      searchButton.removeClass('loading');
    }
  }

  /**
   * Показ сообщения об ошибке
   * Зачем: информирование пользователя об ошибках
   */
  showErrorMessage(message) {
    const searchResults = $(this.selectors.searchResults);
    this.showSearchInfo(searchResults, message, 'error');
    searchResults.show();
  }

  /**
   * Возврат к оригинальному контенту
   * Зачем: восстановление состояния страницы до поиска
   */
  resetToOriginalContent() {
    if (!this.isSearchActive) return;

    const blogList = $(this.selectors.blogList);
    const paginationList = $(this.selectors.paginationList);
    const searchResults = $(this.selectors.searchResults);

    blogList.html(this.originalBlogListHtml);
    paginationList.html(this.originalPaginationHtml);
    paginationList.show();
    searchResults.hide();

    this.isSearchActive = false;
    this.currentSearchQuery = '';

    // Очищаем URL от параметров поиска
    this.clearSearchUrl();

    // Переинициализируем компоненты если нужно
    this.reinitializeComponents();
  }

  /**
   * Очистка поиска
   * Зачем: полный сброс состояния поиска
   */
  clearSearch() {
    const searchInput = $(this.selectors.searchInput);
    searchInput.val('');
    this.resetToOriginalContent();
  }

  /**
   * Обработка отправки формы поиска
   * Зачем: переход на страницу результатов поиска
   */
  handleSearchSubmit() {
    const query = this.currentSearchQuery.trim();

    if (query.length < this.config.minQueryLength) {
      this.showSearchHint(`Введите минимум ${this.config.minQueryLength} символа для поиска`);
      return;
    }

    // Переходим на страницу поиска
    const url = new URL(window.location.href);
    url.pathname = '/blog/search';
    url.searchParams.set('q', query);
    window.location.href = url.toString();
  }

  /**
   * Обработка фокуса на поле поиска
   * Зачем: показ релевантных подсказок при фокусе
   */
  handleSearchFocus() {
    const query = this.currentSearchQuery.trim();

    if (query.length > 0 && query.length < this.config.minQueryLength) {
      this.showSearchHint(`Введите минимум ${this.config.minQueryLength} символа для поиска`);
    }
  }

  /**
   * Обработка начального поискового запроса из URL
   * Зачем: выполнение поиска при загрузке страницы с параметром поиска
   */
  handleInitialSearchQuery() {
    const urlParams = new URLSearchParams(window.location.search);
    const searchQuery = urlParams.get('q');

    if (searchQuery && searchQuery.length >= this.config.minQueryLength) {
      const searchInput = $(this.selectors.searchInput);
      searchInput.val(searchQuery);
      this.currentSearchQuery = searchQuery;
      this.performSearch(searchQuery);
    }
  }

  /**
   * Обновление URL с параметрами поиска
   * Зачем: сохранение состояния поиска в истории браузера
   */
  updateSearchUrl(query) {
    const url = new URL(window.location.href);
    url.searchParams.set('search', query);

    // Убираем другие параметры при поиске
    url.searchParams.delete('page');
    url.searchParams.delete('category');

    window.history.replaceState({ search: query }, '', url.toString());
  }

  /**
   * Очистка URL от параметров поиска
   * Зачем: возврат к базовому состоянию URL
   */
  clearSearchUrl() {
    const url = new URL(window.location.href);
    url.searchParams.delete('search');

    if (url.searchParams.toString() === '') {
      // Если других параметров нет, убираем знак вопроса
      window.history.replaceState({}, '', url.pathname);
    } else {
      window.history.replaceState({}, '', url.toString());
    }
  }

  /**
   * Переинициализация компонентов
   * Зачем: восстановление функциональности после изменения DOM
   */
  reinitializeComponents() {
    // Переинициализируем пагинацию если она есть
    if (typeof window.initPaginationClickHandlers === 'function') {
      window.initPaginationClickHandlers();
    }

    // Переинициализируем карусели если они есть
    if (typeof window.reinitAllCarousels === 'function') {
      setTimeout(() => {
        window.reinitAllCarousels();
      }, 100);
    }
  }

  /**
   * Получение HTML для "нет результатов"
   * Зачем: единообразное отображение пустых результатов
   */
  getNoResultsHtml(query) {
    return `
      <div class="blog-no-results">
        <h3>По запросу "${query}" ничего не найдено</h3>
        <p>Попробуйте изменить поисковый запрос или воспользуйтесь категориями</p>
        <button class="btn btn-primary" onclick="document.querySelector('${this.selectors.searchInput}').focus()">
          Изменить запрос
        </button>
      </div>
    `;
  }

  // === МЕТОДЫ КЕШИРОВАНИЯ ===

  /**
   * Получение результата из кеша
   * Зачем: ускорение повторных поисковых запросов
   */
  getFromCache(query) {
    const cacheKey = this.generateCacheKey(query);
    const cached = this.searchCache.get(cacheKey);

    if (!cached) return null;

    // Проверяем срок действия кеша
    if (Date.now() - cached.timestamp > this.config.cacheTimeout) {
      this.searchCache.delete(cacheKey);
      return null;
    }

    console.log('Search result loaded from cache:', query);
    return cached.data;
  }

  /**
   * Сохранение результата в кеш
   * Зачем: кеширование для ускорения повторных запросов
   */
  putToCache(query, data) {
    const cacheKey = this.generateCacheKey(query);

    // Очищаем кеш если он переполнен
    if (this.searchCache.size >= this.maxCacheSize) {
      this.clearOldestCacheEntries();
    }

    this.searchCache.set(cacheKey, {
      data: data,
      timestamp: Date.now(),
    });

    console.log('Search result cached:', query);
  }

  /**
   * Генерация ключа кеша
   * Зачем: создание уникального ключа для поискового запроса
   */
  generateCacheKey(query) {
    return `search_${query.toLowerCase().trim()}`;
  }

  /**
   * Очистка старых записей кеша
   * Зачем: предотвращение переполнения памяти
   */
  clearOldestCacheEntries() {
    const entries = Array.from(this.searchCache.entries());
    entries.sort((a, b) => a[1].timestamp - b[1].timestamp);

    // Удаляем 25% самых старых записей
    const toDelete = Math.floor(entries.length * 0.25);
    for (let i = 0; i < toDelete; i++) {
      this.searchCache.delete(entries[i][0]);
    }
  }

  /**
   * Очистка всего кеша
   * Зачем: принудительная очистка при необходимости
   */
  clearCache() {
    this.searchCache.clear();
    console.log('Search cache cleared');
  }

  // === МЕТОДЫ ОТЛАДКИ И МОНИТОРИНГА ===

  /**
   * Получение статистики кеша
   * Зачем: мониторинг эффективности кеширования
   */
  getCacheStats() {
    return {
      size: this.searchCache.size,
      maxSize: this.maxCacheSize,
      keys: Array.from(this.searchCache.keys()),
    };
  }

  /**
   * Получение текущего состояния
   * Зачем: отладка и мониторинг состояния поиска
   */
  getState() {
    return {
      isSearchActive: this.isSearchActive,
      isLoading: this.isLoading,
      currentQuery: this.currentSearchQuery,
      cacheStats: this.getCacheStats(),
    };
  }
}

// Создаем глобальный экземпляр менеджера поиска
let blogSearchManager = null;

/**
 * Инициализация поиска в блоге
 * Зачем: публичный API для инициализации поиска
 */
function initBlogSearch() {
  if (blogSearchManager) {
    console.warn('Blog search already initialized');
    return blogSearchManager;
  }

  blogSearchManager = new BlogSearchManager();
  blogSearchManager.init();

  // Делаем менеджер доступным глобально для отладки
  if (typeof window !== 'undefined') {
    window.blogSearchManager = blogSearchManager;
  }

  return blogSearchManager;
}

/**
 * Получение экземпляра менеджера поиска
 * Зачем: доступ к менеджеру из других модулей
 */
function getBlogSearchManager() {
  return blogSearchManager;
}

/**
 * Сброс поиска (публичный API)
 * Зачем: внешний контроль над состоянием поиска
 */
function resetBlogSearch() {
  if (blogSearchManager) {
    blogSearchManager.clearSearch();
  }
}

/**
 * Выполнение поиска программно (публичный API)
 * Зачем: программное управление поиском
 */
function performBlogSearch(query) {
  if (blogSearchManager && query) {
    const searchInput = $(blogSearchManager.selectors.searchInput);
    searchInput.val(query);
    blogSearchManager.currentSearchQuery = query;
    blogSearchManager.performSearch(query);
  }
}

// === LEGACY ФУНКЦИИ ДЛЯ ОБРАТНОЙ СОВМЕСТИМОСТИ ===

/**
 * Legacy функции для совместимости со старым кодом
 * Зачем: поддержка существующего кода без breaking changes
 */
const performSearch = (query, blogList, pagination, searchResults) => {
  console.warn('performSearch is deprecated, use BlogSearchManager instead');
  if (blogSearchManager) {
    blogSearchManager.performSearch(query);
  }
};

const setLoadingState = (isLoading, searchResults) => {
  console.warn('setLoadingState is deprecated, use BlogSearchManager instead');
  if (blogSearchManager) {
    blogSearchManager.setLoadingState(isLoading);
  }
};

const showNoResultsMessage = (blogList, searchResults, html) => {
  console.warn('showNoResultsMessage is deprecated, use BlogSearchManager instead');
  if (blogSearchManager) {
    blogList.html(html);
    blogList.show();
  }
};

const showErrorMessage = searchResults => {
  console.warn('showErrorMessage is deprecated, use BlogSearchManager instead');
  if (blogSearchManager) {
    blogSearchManager.showErrorMessage('Произошла ошибка при поиске');
  }
};

const resetToOriginalContent = (blogList, pagination, searchResults) => {
  console.warn('resetToOriginalContent is deprecated, use BlogSearchManager instead');
  if (blogSearchManager) {
    blogSearchManager.resetToOriginalContent();
  }
};

// Экспорт всех функций
export {
  BlogSearchManager,
  getBlogSearchManager,
  // Новый API
  initBlogSearch,
  performBlogSearch,
  // Legacy API для обратной совместимости
  performSearch,
  resetBlogSearch,
  resetToOriginalContent,
  setLoadingState,
  showErrorMessage,
  showNoResultsMessage,
};
