import { initializeSelectComponent } from '../../../helpers/initiolaze-select-component';
import { logger } from '../../../helpers/logger';

/**
 * Класс для управления фильтрами, сортировкой и пагинацией сервисов.
 *
 * Возможности:
 * - Фильтрация по категориям и бонусам
 * - Поиск по тексту
 * - Сортировка данных
 * - Пагинация результатов
 * - Выбор количества элементов на странице
 *
 * @example
 * // Использование с jQuery-хелпером (по умолчанию)
 * const store = new ServiceStore({});
 * const filterManager = new ServiceFilterManager('.filter', store);
 *
 * // Использование с нативной реализацией
 * const filterManager = new ServiceFilterManager('.filter', store, { useJqueryHelper: false });
 */
class ServiceFilterManager {
  constructor(containerSelector = '.filter', store, options = {}) {
    this.container = document.querySelector(containerSelector);
    if (!this.container) {
      console.warn(`Filter container not found for selector: ${containerSelector}`);
      return;
    }
    this.store = store;

    // Опции инициализации
    this.options = {
      // По умолчанию используем jQuery helper функцию
      useJqueryHelper: options.useJqueryHelper !== undefined ? options.useJqueryHelper : true,
    };

    // Получаем элементы фильтров
    this.categoryFilter = document.getElementById('category-filter');
    this.bonusesFilter = document.getElementById('bonuses-filter');
    this.searchInput = this.container.querySelector('input[type="search"]');
    this.resetButton = this.container.querySelector('.reset-btn a');

    // Элементы пагинации и сортировки
    this.sortingSelect = document.getElementById('sort-by');
    this.perPageSelect = document.getElementById('per-page');
    this.paginationContainer = document.querySelector('.pagination');

    // Подписываемся на изменения фильтров и пагинации
    this.storeUnsubscribe = this.store.subscribe(this.handleStoreChanges.bind(this));

    this.bindEvents();
    this.initFilters();
    this.initPagination();
    this.initSorting();
  }

  // Проверка существования элемента в DOM
  elementExists(element) {
    return element && element instanceof HTMLElement && document.body.contains(element);
  }

  bindEvents() {
    // Добавляем слушатели событий для base-select
    if (this.elementExists(this.categoryFilter)) {
      // Всегда используем jQuery helper
      this.initializeSelectWithHelper(this.categoryFilter, 'category');
    }

    if (this.elementExists(this.bonusesFilter)) {
      // Всегда используем jQuery helper
      this.initializeSelectWithHelper(this.bonusesFilter, 'bonuses');
    }

    // Добавляем обработчики для сортировки, если элемент найден
    if (this.elementExists(this.sortingSelect)) {
      // Всегда используем jQuery helper
      this.initializeSortingWithHelper();
    }

    // Добавляем обработчики для выбора количества элементов на странице
    if (this.elementExists(this.perPageSelect)) {
      // Всегда используем jQuery helper
      this.initializePerPageWithHelper();
    }

    // Поиск
    if (this.elementExists(this.searchInput)) {
      this.searchInput.addEventListener('input', this.handleSearchInput.bind(this));
      // Предотвращаем стандартную отправку формы
      const searchForm = this.searchInput.closest('form');
      if (searchForm) {
        searchForm.addEventListener('submit', e => {
          e.preventDefault();
          this.handleSearchSubmit();
        });
      }
    }

    // Кнопка сброса
    if (this.elementExists(this.resetButton)) {
      this.resetButton.addEventListener('click', e => {
        e.preventDefault();
        this.resetFilters();
      });
    }

    // Инициализация пагинации
    if (this.elementExists(this.paginationContainer)) {
      this.initPaginationListeners();
    }
  }

  // Метод-адаптер для использования внешней функции initializeSelectComponent
  initializeSelectWithHelper(selectContainer, filterName) {
    // Проверяем, что контейнер существует
    if (!selectContainer) return;

    // ID контейнера для jQuery-селектора
    const containerId = `#${selectContainer.id}`;

    // Конфигурация для jQuery-компонента
    const config = {
      selectors: {
        select: '.base-select__dropdown',
        options: '.base-select__option',
        trigger: '.base-select__trigger',
        valueElement: '.base-select__value',
      },
      params: {
        valueParam: filterName,
        orderParam: 'order',
      },
      // Собственный AJAX-обработчик, который будет обновлять фильтры в сторе
      ajaxHandler: queryParams => {
        if (queryParams && queryParams.hasOwnProperty(filterName)) {
          this.updateFilter(filterName, queryParams[filterName]);
        }
      },
    };

    // Инициализируем компонент с помощью внешней функции
    initializeSelectComponent(containerId, config);
  }

  // Инициализируем слушателя для base-select (исходная реализация)
  initBaseSelectListener(selectContainer, filterName) {
    const options = selectContainer.querySelectorAll('.base-select__option');
    const trigger = selectContainer.querySelector('.base-select__trigger');

    // Слушатель для открытия/закрытия выпадающего списка
    if (trigger) {
      trigger.addEventListener('click', () => {
        const dropdown = selectContainer.querySelector('.base-select__dropdown');
        if (dropdown) {
          dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        }
      });
    }

    // Слушатели для опций
    options.forEach(option => {
      option.addEventListener('click', () => {
        const value = option.dataset.value;
        const label = option.dataset.label;

        // Обновляем внешний вид выбранного элемента
        options.forEach(opt => opt.classList.remove('is-selected'));
        option.classList.add('is-selected');

        // Обновляем отображаемую метку
        const selectedLabel = selectContainer.querySelector('.base-select__selected-label');
        if (selectedLabel) {
          selectedLabel.textContent = label;
        }

        // Закрываем выпадающий список
        const dropdown = selectContainer.querySelector('.base-select__dropdown');
        if (dropdown) {
          dropdown.style.display = 'none';
        }

        // Обновляем фильтры в сторе
        this.updateFilter(filterName, value);
      });
    });

    // Закрытие при клике вне элемента
    document.addEventListener('click', e => {
      if (!selectContainer.contains(e.target)) {
        const dropdown = selectContainer.querySelector('.base-select__dropdown');
        if (dropdown) {
          dropdown.style.display = 'none';
        }
      }
    });
  }

  handleSearchInput(event) {
    // Можно добавить debounce, чтобы не обновлять при каждом нажатии клавиши
    // В данной реализации просто обновляем при изменении
    const searchValue = event.target.value.trim();
    this.updateFilter('search', searchValue);
  }

  handleSearchSubmit() {
    const searchValue = this.searchInput.value.trim();
    this.updateFilter('search', searchValue);
  }

  updateFilter(filterName, value) {
    const newFilters = { ...this.store.getState().filters };

    if (value === '' || value === null) {
      // Если значение пустое, удаляем фильтр
      delete newFilters[filterName];
    } else {
      // Иначе устанавливаем новое значение
      newFilters[filterName] = value;
    }

    // Логируем обновленные фильтры
    logger('newFilters', newFilters, { debug: true });

    this.store.setFilters(newFilters);
  }

  resetFilters() {
    // Сбрасываем визуальное состояние селектов
    if (this.categoryFilter) {
      this.resetBaseSelect(this.categoryFilter);
    }

    if (this.bonusesFilter) {
      this.resetBaseSelect(this.bonusesFilter);
    }

    // Сбрасываем настройки сортировки
    if (this.sortingSelect) {
      this.resetBaseSelect(this.sortingSelect);
    }

    // Сбрасываем настройки количества элементов на странице
    if (this.perPageSelect) {
      this.resetBaseSelect(this.perPageSelect);
    }

    // Сбрасываем поле поиска
    if (this.searchInput) {
      this.searchInput.value = '';
    }

    // Сбрасываем все фильтры в сторе
    this.store.setFilters({});

    // Сбрасываем страницу на первую
    this.store.setCurrentPage(1);

    // Возвращаем значение по умолчанию для количества элементов на странице
    this.store.setPerPage(12); // или другое значение по умолчанию
  }

  resetBaseSelect(selectContainer) {
    const options = selectContainer.querySelectorAll('.base-select__option');
    options.forEach(opt => opt.classList.remove('is-selected'));

    const selectedLabel = selectContainer.querySelector('.base-select__selected-label');
    if (selectedLabel) {
      selectedLabel.textContent = '';
    }

    const placeholder = selectContainer.querySelector('.base-select__placeholder');
    if (placeholder) {
      placeholder.style.display = 'inline';
    }
  }

  // Инициализация UI фильтров на основе текущего состояния стора (из URL или SSR)
  initFilters() {
    const currentFilters = this.store.getState().filters;

    // Инициализируем категорию
    if (this.categoryFilter && currentFilters.category) {
      this.setBaseSelectValue(this.categoryFilter, currentFilters.category);
    }

    // Инициализируем бонусы
    if (this.bonusesFilter && currentFilters.bonuses) {
      this.setBaseSelectValue(this.bonusesFilter, currentFilters.bonuses);
    }

    // Инициализируем поиск
    if (this.searchInput && currentFilters.search) {
      this.searchInput.value = currentFilters.search;
    }
  }

  setBaseSelectValue(selectContainer, value) {
    const options = selectContainer.querySelectorAll('.base-select__option');
    let selectedOption = null;

    options.forEach(option => {
      if (option.dataset.value === value) {
        option.classList.add('is-selected');
        selectedOption = option;
      } else {
        option.classList.remove('is-selected');
      }
    });

    if (selectedOption) {
      const selectedLabel = selectContainer.querySelector('.base-select__selected-label');
      if (selectedLabel) {
        selectedLabel.textContent = selectedOption.dataset.label;
      }

      const placeholder = selectContainer.querySelector('.base-select__placeholder');
      if (placeholder) {
        placeholder.style.display = 'none';
      }
    }
  }

  // Методы для работы с пагинацией
  initPagination() {
    // Инициализация состояния пагинации на основе данных из хранилища
    const pagination = this.store.getState().pagination;
    logger('Initial pagination state', pagination, { debug: true });

    // Проверяем, есть ли установленное значение количества элементов на странице
    const perPage = pagination.per_page;

    // Если есть значение per_page, инициализируем селектор
    if (perPage && this.perPageSelect) {
      this.setPerPageSelectValue(perPage.toString());
    }
    // Если нет, устанавливаем значение по умолчанию из первой опции или 12
    else if (this.perPageSelect) {
      // Получаем первую опцию или устанавливаем 12 как значение по умолчанию
      const defaultOption = this.perPageSelect.querySelector('.base-select__option');
      if (defaultOption) {
        const defaultValue = parseInt(defaultOption.dataset.value);
        logger('Setting default per_page', defaultValue, { debug: true });

        // Устанавливаем значение по умолчанию в сторе, но не обновляем UI,
        // так как это происходит при первоначальной загрузке
        this.store.setPerPage(defaultValue);
      } else {
        // Если нет опций, устанавливаем стандартное значение 12
        this.store.setPerPage(12);
      }
    }
  }

  initPaginationListeners() {
    // Находим все ссылки пагинации и добавляем обработчики
    const pageLinks = this.paginationContainer.querySelectorAll('a[data-page]');

    pageLinks.forEach(link => {
      link.addEventListener('click', e => {
        e.preventDefault();
        const page = parseInt(link.dataset.page);
        if (!isNaN(page)) {
          this.setPage(page);
        }
      });
    });
  }

  setPage(page) {
    // Устанавливаем текущую страницу
    logger('Setting page to:', page, { debug: true });
    this.store.setCurrentPage(page);
  }

  // Методы для работы с сортировкой
  initSorting() {
    // Инициализация состояния сортировки на основе данных из хранилища
    const filters = this.store.getState().filters;
    const sortBy = filters.sort_by || filters.sortBy;
    const sortOrder = filters.sort_order || filters.sortOrder || 'asc'; // Добавляем значение по умолчанию

    logger(
      'Initial sorting state',
      {
        sort_by: sortBy,
        sort_order: sortOrder,
      },
      { debug: true }
    );

    // Если в сторе нет значений сортировки, устанавливаем значения по умолчанию
    if (!sortBy && this.sortingSelect) {
      // Получаем первую опцию или устанавливаем 'default' как значение по умолчанию
      const defaultOption = this.sortingSelect.querySelector('.base-select__option');
      if (defaultOption) {
        const defaultValue = defaultOption.dataset.value;
        const defaultOrder = defaultOption.dataset.order || 'asc';

        logger(
          'Setting default sorting',
          {
            sort_by: defaultValue,
            sort_order: defaultOrder,
          },
          { debug: true }
        );

        // Устанавливаем значения по умолчанию в сторе
        this.updateSorting({
          sort_by: defaultValue,
          sort_order: defaultOrder,
        });
      } else {
        // Если нет опций, устанавливаем стандартные значения
        this.updateSorting({
          sort_by: 'default',
          sort_order: 'desc',
        });
      }
    }
    // Если есть значения сортировки в сторе, обновляем визуальное состояние
    else if (sortBy && this.sortingSelect) {
      // Используем и значение сортировки, и направление
      this.setSortingSelectValue(sortBy, sortOrder);
    }
  }

  setSortingSelectValue(value, order = 'asc') {
    logger('Setting UI sort value', { value, order }, { debug: true });

    const options = this.sortingSelect.querySelectorAll('.base-select__option');
    let selectedOption = null;
    let fallbackOption = null; // Для случая, если не найдем точное соответствие

    options.forEach(option => {
      // Проверяем как значение, так и направление сортировки
      const optionValue = option.dataset.value;
      const optionOrder = option.dataset.order || 'asc'; // Если order не указан, считаем asc по умолчанию

      // Проверяем точное соответствие
      if (optionValue === value && optionOrder === order) {
        option.classList.add('is-selected');
        selectedOption = option;
      }
      // Запасной вариант - просто совпадение по значению
      else if (optionValue === value && !fallbackOption) {
        fallbackOption = option;
        option.classList.remove('is-selected');
      } else {
        option.classList.remove('is-selected');
      }
    });

    // Если точное соответствие не найдено, но есть совпадение по значению
    if (!selectedOption && fallbackOption) {
      fallbackOption.classList.add('is-selected');
      selectedOption = fallbackOption;
      logger(
        'Exact match not found, using fallback option with matching value',
        { value, fallbackOrder: fallbackOption.dataset.order },
        { debug: true, isWarning: true }
      );
    }

    if (selectedOption) {
      const selectedLabel = this.sortingSelect.querySelector('.base-select__selected-label');
      if (selectedLabel) {
        selectedLabel.textContent = selectedOption.dataset.label;
        logger('Updated sort select UI with label', selectedOption.dataset.label, { debug: true });
      }
    } else {
      logger(
        'No matching option found for sort value and order',
        { value, order },
        { debug: true, isWarning: true }
      );
    }
  }

  initializeSortingWithHelper() {
    // Используем jQuery helper для инициализации сортировки
    const containerId = `#${this.sortingSelect.id}`;

    const config = {
      selectors: {
        select: '.base-select__dropdown',
        options: '.base-select__option',
        trigger: '.base-select__trigger',
        valueElement: '.base-select__value',
        orderElement: '.base-select__order',
      },
      params: {
        valueParam: 'sort_by',
        orderParam: 'sort_order',
      },
      ajaxHandler: queryParams => {
        // Логируем все переданные параметры
        logger('Sort select change with params', queryParams, { debug: true });

        // Передаем все параметры в обработчик, чтобы учесть page и другие возможные параметры
        this.updateSorting(queryParams);
      },
      // Добавляем resetPage для сброса пагинации при изменении сортировки
      resetPage: true,
    };

    // Инициализируем компонент с помощью внешней функции
    initializeSelectComponent(containerId, config);
  }

  initSortingListener() {
    // Нативная реализация инициализации сортировки
    const options = this.sortingSelect.querySelectorAll('.base-select__option');
    const trigger = this.sortingSelect.querySelector('.base-select__trigger');

    if (trigger) {
      trigger.addEventListener('click', () => {
        const dropdown = this.sortingSelect.querySelector('.base-select__dropdown');
        if (dropdown) {
          dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        }
      });
    }

    options.forEach(option => {
      option.addEventListener('click', () => {
        const field = option.dataset.value;
        const order = option.dataset.order || 'asc';
        const label = option.dataset.label;

        // Обновляем внешний вид - снимаем выделение со всех элементов
        options.forEach(opt => opt.classList.remove('is-selected'));

        // Устанавливаем выделение только для выбранного элемента
        option.classList.add('is-selected');

        // Обновляем отображаемую метку
        const selectedLabel = this.sortingSelect.querySelector('.base-select__selected-label');
        if (selectedLabel) {
          selectedLabel.textContent = label;
        }

        // Закрываем выпадающий список
        const dropdown = this.sortingSelect.querySelector('.base-select__dropdown');
        if (dropdown) {
          dropdown.style.display = 'none';
        }

        // Обновляем сортировку с учетом конкретного порядка
        this.updateSorting({
          sort_by: field,
          sort_order: order,
        });
      });
    });

    // Закрытие при клике вне элемента
    document.addEventListener('click', e => {
      if (!this.sortingSelect.contains(e.target)) {
        const dropdown = this.sortingSelect.querySelector('.base-select__dropdown');
        if (dropdown) {
          dropdown.style.display = 'none';
        }
      }
    });
  }

  updateSorting(params) {
    // Обновляем параметры сортировки в хранилище
    logger('Updating sorting with params:', params, { debug: true });

    // Проверяем, что параметры корректные
    if (!params || (!params.sort_by && !params.sortBy)) {
      logger('Invalid sorting params', params, { debug: true, isError: true });
      return;
    }

    const sorting = {
      field: params.sort_by || params.sortBy,
      order: params.sort_order || params.sortOrder || 'asc',
    };

    // Обновляем визуальное представление, если селектор существует
    if (this.sortingSelect) {
      this.setSortingSelectValue(sorting.field, sorting.order);
    }

    // Обновляем сортировку в сторе
    this.store.setSorting(sorting);

    // Если передана страница - устанавливаем её, иначе сбрасываем на первую
    if (params.page) {
      // Устанавливаем указанную страницу
      this.store.setCurrentPage(parseInt(params.page));
    } else {
      // При изменении сортировки без указания страницы сбрасываем на первую
      this.store.setCurrentPage(1);
    }
  }

  // Методы для работы с количеством элементов на странице
  initializePerPageWithHelper() {
    // Используем jQuery helper для инициализации выбора количества элементов
    const containerId = `#${this.perPageSelect.id}`;

    const config = {
      selectors: {
        select: '.base-select__dropdown',
        options: '.base-select__option',
        trigger: '.base-select__trigger',
        valueElement: '.base-select__value',
      },
      params: {
        valueParam: 'per_page',
      },
      ajaxHandler: queryParams => {
        if (queryParams.per_page) {
          this.updatePerPage(parseInt(queryParams.per_page));
        }
      },
      // Добавляем resetPage для сброса пагинации при изменении количества элементов
      resetPage: true,
    };

    // Инициализируем компонент с помощью внешней функции
    initializeSelectComponent(containerId, config);
  }

  initPerPageListener() {
    // Нативная реализация для выбора количества элементов на странице
    const options = this.perPageSelect.querySelectorAll('.base-select__option');
    const trigger = this.perPageSelect.querySelector('.base-select__trigger');

    if (trigger) {
      trigger.addEventListener('click', () => {
        const dropdown = this.perPageSelect.querySelector('.base-select__dropdown');
        if (dropdown) {
          dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        }
      });
    }

    options.forEach(option => {
      option.addEventListener('click', () => {
        const perPage = parseInt(option.dataset.value);
        const label = option.dataset.label;

        // Обновляем внешний вид
        options.forEach(opt => opt.classList.remove('is-selected'));
        option.classList.add('is-selected');

        // Обновляем отображаемую метку
        const selectedLabel = this.perPageSelect.querySelector('.base-select__selected-label');
        if (selectedLabel) {
          selectedLabel.textContent = label;
        }

        // Закрываем выпадающий список
        const dropdown = this.perPageSelect.querySelector('.base-select__dropdown');
        if (dropdown) {
          dropdown.style.display = 'none';
        }

        // Обновляем количество элементов на странице
        this.updatePerPage(perPage);
      });
    });

    // Закрытие при клике вне элемента
    document.addEventListener('click', e => {
      if (!this.perPageSelect.contains(e.target)) {
        const dropdown = this.perPageSelect.querySelector('.base-select__dropdown');
        if (dropdown) {
          dropdown.style.display = 'none';
        }
      }
    });
  }

  updatePerPage(perPage) {
    // Обновляем количество элементов на странице в хранилище
    if (!isNaN(perPage) && perPage > 0) {
      logger('Updating per page to:', perPage, { debug: true });

      // Обновляем визуальное представление, если селектор существует
      if (this.perPageSelect) {
        this.setPerPageSelectValue(perPage.toString());
      }

      // Обновляем количество элементов на странице в сторе
      this.store.setPerPage(perPage);

      // При изменении количества элементов, сбрасываем страницу на первую
      this.store.setCurrentPage(1);
    } else {
      logger('Invalid per page value:', perPage, { debug: true, isError: true });
    }
  }

  setPerPageSelectValue(value) {
    logger('Setting UI per_page value', value, { debug: true });

    const options = this.perPageSelect.querySelectorAll('.base-select__option');
    let selectedOption = null;

    options.forEach(option => {
      if (option.dataset.value === value) {
        option.classList.add('is-selected');
        selectedOption = option;
      } else {
        option.classList.remove('is-selected');
      }
    });

    if (selectedOption) {
      const selectedLabel = this.perPageSelect.querySelector('.base-select__selected-label');
      if (selectedLabel) {
        selectedLabel.textContent = selectedOption.dataset.label;
        logger('Updated per_page select UI with label', selectedOption.dataset.label, {
          debug: true,
        });
      }
    } else {
      logger('No matching option found for per_page value', value, {
        debug: true,
        isWarning: true,
      });
    }
  }

  // Обработчик изменений в сторе
  handleStoreChanges(state) {
    // Сохраняем предыдущее состояние при первом запуске
    if (!this.previousState) {
      this.previousState = { ...state };
      return;
    }

    // Ищем и логируем изменения
    const changes = {};

    // Проверяем изменения в фильтрах
    if (JSON.stringify(state.filters) !== JSON.stringify(this.previousState.filters)) {
      changes.filters = {
        previous: this.previousState.filters,
        current: state.filters,
      };
    }

    // Проверяем изменения в пагинации
    if (JSON.stringify(state.pagination) !== JSON.stringify(this.previousState.pagination)) {
      changes.pagination = {
        previous: this.previousState.pagination,
        current: state.pagination,
      };
    }

    // Логируем изменения, если они есть
    if (Object.keys(changes).length > 0) {
      logger('Store changes detected', changes, { debug: true });
    }

    // Обновляем предыдущее состояние
    this.previousState = { ...state };
  }

  // Обновляем все селекторы в соответствии с текущими фильтрами и пагинацией
  updateSelectorsFromStore() {
    const state = this.store.getState();
    const { filters, pagination } = state;

    // Обновляем селектор категорий
    if (this.categoryFilter && filters.category) {
      this.setBaseSelectValue(this.categoryFilter, filters.category);
    }

    // Обновляем селектор бонусов
    if (this.bonusesFilter && filters.bonuses) {
      this.setBaseSelectValue(this.bonusesFilter, filters.bonuses);
    }

    // Обновляем селектор сортировки
    if (this.sortingSelect && (filters.sort_by || filters.sortBy)) {
      const sortBy = filters.sort_by || filters.sortBy;
      const sortOrder = filters.sort_order || filters.sortOrder || 'asc';
      this.setSortingSelectValue(sortBy, sortOrder);
    }

    // Обновляем селектор количества элементов на странице
    if (this.perPageSelect && pagination.per_page) {
      this.setPerPageSelectValue(pagination.per_page.toString());
    }
  }

  // Метод для освобождения ресурсов при уничтожении компонента
  destroy() {
    // Отписываемся от изменений в сторе
    if (this.storeUnsubscribe) {
      this.storeUnsubscribe();
    }

    // Удаляем все обработчики событий, если необходимо
    // ...

    logger('ServiceFilterManager destroyed', null, { debug: true });
  }
}

export default ServiceFilterManager;
