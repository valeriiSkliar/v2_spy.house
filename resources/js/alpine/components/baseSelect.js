export default function baseSelect(config) {
  return {
    open: false,
    selectedOption: { value: null, label: null }, // Internal reactive state for the selected option object
    initialValue: config.initialSelectedValue, // Initial value passed to the component (e.g., from parent Alpine data)
    optionsArray: config.optionsArray || [], // Array of option objects
    elementId: config.elementId || '', // ID of the component instance
    useFlags: config.useFlags || false, // Whether to display flags
    iconClass: config.iconClass || null, // CSS class for an icon
    placeholderText: config.placeholderText || '', // Placeholder text when no option is selected

    // Новые свойства для интеграции со store
    storeKey: config.storeKey || null, // Ключ в Alpine store для синхронизации
    storePath: config.storePath || null, // Путь к свойству в store (например, 'creatives.perPage')
    onChangeCallback: config.onChangeCallback || null, // Название callback метода в store

    init() {
      // Инициализируем выбранную опцию
      this.updateSelectedOptionFromValue(this.initialValue);

      // Настраиваем синхронизацию со store если указан storePath
      if (this.storePath) {
        this.setupStoreSync();
      }

      // Слушатель для внешних обновлений (оставляем для обратной совместимости)
      this.$el.addEventListener(`update-base-select-${this.elementId}`, event => {
        if (event.detail && event.detail.value !== undefined) {
          this.updateSelectedOptionFromValue(event.detail.value);
        }
      });

      // Слушатель для обновления опций извне
      this.$el.addEventListener(`update-options-${this.elementId}`, event => {
        if (event.detail && event.detail.options) {
          this.optionsArray = event.detail.options;
          // Пересинхронизируем выбранную опцию после обновления списка
          this.updateSelectedOptionFromValue(this.selectedOption.value);
        }
      });
    },

    /**
     * Настраивает двустороннюю синхронизацию со store
     */
    setupStoreSync() {
      const pathParts = this.storePath.split('.');
      const storeKey = pathParts[0];
      const propertyPath = pathParts.slice(1);

      // Получаем значение из store при инициализации
      const storeValue = this.getStoreValue(storeKey, propertyPath);
      if (storeValue !== undefined) {
        this.updateSelectedOptionFromValue(storeValue);
      }

      // Наблюдаем за изменениями в store
      this.$watch(`$store.${this.storePath}`, newValue => {
        if (String(newValue) !== String(this.selectedOption.value)) {
          this.updateSelectedOptionFromValue(newValue);
        }
      });
    },

    /**
     * Получает значение из store по пути
     */
    getStoreValue(storeKey, propertyPath) {
      let value = this.$store[storeKey];
      for (const prop of propertyPath) {
        if (value && typeof value === 'object' && prop in value) {
          value = value[prop];
        } else {
          return undefined;
        }
      }
      return value;
    },

    /**
     * Устанавливает значение в store по пути
     */
    setStoreValue(storeKey, propertyPath, value) {
      let target = this.$store[storeKey];
      for (let i = 0; i < propertyPath.length - 1; i++) {
        if (target && typeof target === 'object' && propertyPath[i] in target) {
          target = target[propertyPath[i]];
        } else {
          return false;
        }
      }

      if (target && typeof target === 'object') {
        const lastProp = propertyPath[propertyPath.length - 1];
        target[lastProp] = value;
        return true;
      }
      return false;
    },

    /**
     * Updates the internal selectedOption state based on a provided value.
     * It finds the corresponding option in optionsArray or sets up a temporary option if not found.
     * @param {*} valueToSelect - The value of the option to select.
     */
    updateSelectedOptionFromValue(valueToSelect) {
      // Ensure comparison is consistent, e.g., by converting to string if values can be numbers or strings
      const valueStr =
        valueToSelect !== null && valueToSelect !== undefined ? String(valueToSelect) : null;

      const foundOption = this.optionsArray.find(opt => String(opt.value) === valueStr);

      if (foundOption) {
        this.selectedOption = { ...foundOption }; // Use a copy to ensure reactivity
      } else if (valueStr !== null) {
        // If a value is provided but it's not in the options list,
        // create a temporary selectedOption using the value itself as the label.
        this.selectedOption = { value: valueToSelect, label: String(valueToSelect) };
      } else {
        // No value provided or value is null/undefined, so no option is selected.
        // The placeholder will be shown if available.
        this.selectedOption = { value: null, label: null };
      }
    },

    /**
     * Toggles the visibility of the dropdown.
     */
    toggleDropdown() {
      this.open = !this.open;
    },

    /**
     * Handles the selection of an option from the dropdown.
     * Updates the internal state and dispatches an event with the new selection.
     * @param {object} option - The selected option object.
     */
    selectOption(option) {
      this.selectedOption = { ...option }; // Update internal state with a copy
      this.open = false; // Close the dropdown

      // Обновляем store если настроена синхронизация
      if (this.storePath) {
        const pathParts = this.storePath.split('.');
        const storeKey = pathParts[0];
        const propertyPath = pathParts.slice(1);

        // Конвертируем значение в числовой тип если это необходимо
        let valueToStore = this.selectedOption.value;
        if (this.selectedOption.value && !isNaN(this.selectedOption.value)) {
          valueToStore = Number(this.selectedOption.value);
        }

        // Обновляем значение в store

        console.log('baseSelect: updating store', {
          storeKey,
          propertyPath,
          valueToStore,
          onChangeCallback: this.onChangeCallback,
        });

        this.setStoreValue(storeKey, propertyPath, valueToStore);

        // Выбираем способ уведомления store об изменении
        if (
          this.onChangeCallback &&
          this.$store[storeKey] &&
          typeof this.$store[storeKey][this.onChangeCallback] === 'function'
        ) {
          // Используем конкретный callback если указан
          this.$store[storeKey][this.onChangeCallback](valueToStore);
        } else if (
          this.$store[storeKey] &&
          typeof this.$store[storeKey].handleFieldChange === 'function'
        ) {
          // Используем универсальный handler
          this.$store[storeKey].handleFieldChange(
            propertyPath[propertyPath.length - 1],
            valueToStore
          );
        } else {
          // Fallback: старая логика для обратной совместимости
          const fieldName = propertyPath[propertyPath.length - 1];
          if (
            fieldName === 'perPage' &&
            this.$store[storeKey] &&
            typeof this.$store[storeKey].setPerPage === 'function'
          ) {
            this.$store[storeKey].setPerPage(valueToStore);
          }
        }
      }

      // Dispatch custom event для обратной совместимости
      this.$dispatch('base-select:change', {
        id: this.elementId,
        value: this.selectedOption.value,
        label: this.selectedOption.label,
        order: this.selectedOption.order, // Include order if present
        option: { ...this.selectedOption }, // Include the full selected option object
      });
    },

    /**
     * Программное обновление опций компонента
     * @param {Array} newOptions - Новый массив опций
     */
    updateOptions(newOptions) {
      this.optionsArray = newOptions || [];
      // Пересинхронизируем выбранную опцию
      this.updateSelectedOptionFromValue(this.selectedOption.value);
    },
  };
}
