import { initializeSelectComponent } from '../../../helpers/initiolaze-select-component';
import { logger } from '../../../helpers/logger';

/**
 * Класс для управления фильтрами сервисов.
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

    this.bindEvents();
    this.initFilters();
  }

  bindEvents() {
    // Добавляем слушатели событий для base-select
    if (this.categoryFilter) {
      // Выбираем реализацию в зависимости от опций
      if (this.options.useJqueryHelper) {
        this.initializeSelectWithHelper(this.categoryFilter, 'category');
      } else {
        this.initBaseSelectListener(this.categoryFilter, 'category');
      }
    }

    if (this.bonusesFilter) {
      // Выбираем реализацию в зависимости от опций
      if (this.options.useJqueryHelper) {
        this.initializeSelectWithHelper(this.bonusesFilter, 'bonuses');
      } else {
        this.initBaseSelectListener(this.bonusesFilter, 'bonuses');
      }
    }

    // Поиск
    if (this.searchInput) {
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
    if (this.resetButton) {
      this.resetButton.addEventListener('click', e => {
        e.preventDefault();
        this.resetFilters();
      });
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

    // Сбрасываем поле поиска
    if (this.searchInput) {
      this.searchInput.value = '';
    }

    // Сбрасываем все фильтры в сторе
    this.store.setFilters({});
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
}

export default ServiceFilterManager;
