import Swal from 'sweetalert2';

/**
 * SweetAlert2 Service для унифицированных модальных окон
 * Предоставляет переиспользуемые компоненты для различных типов диалогов
 */
class SweetAlertService {
  constructor() {
    // Базовые настройки для всех модальных окон
    this.defaultConfig = {
      customClass: {
        popup: 'swal-popup',
        title: 'swal-title',
        content: 'swal-content',
        confirmButton: 'swal-confirm-btn',
        cancelButton: 'swal-cancel-btn',
        input: 'swal-input',
      },
      buttonsStyling: false,
      reverseButtons: true,
    };
  }

  /**
   * Показывает окно подтверждения действия
   * @param {string} title - Заголовок окна
   * @param {string} message - Текст сообщения
   * @param {Function} onConfirm - Callback функция при подтверждении
   * @param {Object} options - Дополнительные опции
   * @returns {Promise<boolean>} - Результат подтверждения
   */
  async confirm(
    title = 'Вы уверены?',
    message = 'Это действие нельзя отменить',
    onConfirm = null,
    options = {}
  ) {
    const config = {
      ...this.defaultConfig,
      title,
      text: message,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Подтвердить',
      cancelButtonText: 'Отмена',
      allowOutsideClick: false,
      allowEscapeKey: false,
      customClass: {
        ...this.defaultConfig.customClass,
        confirmButton: 'btn _flex _green _big ml-4',
        cancelButton: 'btn _flex _red _big',
      },
      ...options,
    };

    try {
      const result = await Swal.fire(config);

      if (result.isConfirmed && onConfirm && typeof onConfirm === 'function') {
        await onConfirm();
      }

      return result.isConfirmed;
    } catch (error) {
      console.error('Ошибка в окне подтверждения:', error);
      return false;
    }
  }

  /**
   * Показывает уведомление об успехе
   * @param {string} title - Заголовок уведомления
   * @param {string} message - Текст сообщения
   * @param {Object} options - Дополнительные опции
   * @returns {Promise<void>}
   */
  async success(title = 'Успех!', message = 'Операция выполнена успешно', options = {}) {
    const config = {
      ...this.defaultConfig,
      title,
      text: message,
      icon: 'success',
      confirmButtonText: 'ОК',
      timer: 3000,
      timerProgressBar: true,
      customClass: {
        ...this.defaultConfig.customClass,
        confirmButton: 'swal-confirm-btn btn-success',
      },
      ...options,
    };

    return await Swal.fire(config);
  }

  /**
   * Показывает уведомление об ошибке
   * @param {string} title - Заголовок уведомления
   * @param {string} message - Текст сообщения об ошибке
   * @param {Object} options - Дополнительные опции
   * @returns {Promise<void>}
   */
  async error(title = 'Ошибка!', message = 'Произошла ошибка. Попробуйте снова', options = {}) {
    const config = {
      ...this.defaultConfig,
      title,
      text: message,
      icon: 'error',
      confirmButtonText: 'ОК',
      customClass: {
        ...this.defaultConfig.customClass,
        confirmButton: 'swal-confirm-btn btn-danger',
      },
      ...options,
    };

    return await Swal.fire(config);
  }

  /**
   * Показывает модальное окно с полем ввода
   * @param {string} title - Заголовок окна
   * @param {string} placeholder - Placeholder для поля ввода
   * @param {string} inputType - Тип поля ввода (text, email, password и т.д.)
   * @param {Function} validator - Функция валидации
   * @param {Object} options - Дополнительные опции
   * @returns {Promise<string|null>} - Введенное значение или null при отмене
   */
  async input(
    title = 'Введите данные',
    placeholder = '',
    inputType = 'text',
    validator = null,
    options = {}
  ) {
    const config = {
      ...this.defaultConfig,
      title,
      input: inputType,
      inputPlaceholder: placeholder,
      showCancelButton: true,
      confirmButtonText: 'Отправить',
      cancelButtonText: 'Отмена',
      allowOutsideClick: false,
      customClass: {
        ...this.defaultConfig.customClass,
        confirmButton: 'swal-confirm-btn btn-primary',
        cancelButton: 'swal-cancel-btn btn-secondary',
      },
      inputValidator:
        validator ||
        (value => {
          if (!value || value.trim() === '') {
            return 'Поле не может быть пустым';
          }

          if (inputType === 'email') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
              return 'Введите корректный email адрес';
            }
          }

          return null;
        }),
      ...options,
    };

    try {
      const result = await Swal.fire(config);
      return result.isConfirmed ? result.value : null;
    } catch (error) {
      console.error('Ошибка в окне ввода:', error);
      return null;
    }
  }

  /**
   * Показывает предупреждение с таймером
   * @param {string} title - Заголовок предупреждения
   * @param {string} message - Текст предупреждения
   * @param {number} timer - Время в миллисекундах (по умолчанию 10 секунд)
   * @param {Function} onContinue - Callback при нажатии "Продолжить"
   * @param {Object} options - Дополнительные опции
   * @returns {Promise<boolean>} - true если нажали "Продолжить", false если закрылось по таймеру
   */
  async timedWarning(
    title = 'Внимание!',
    message = 'Сессия истекает',
    timer = 10000,
    onContinue = null,
    options = {}
  ) {
    const config = {
      ...this.defaultConfig,
      title,
      text: message,
      icon: 'warning',
      timer,
      timerProgressBar: true,
      showCancelButton: false,
      confirmButtonText: 'Продолжить',
      allowOutsideClick: false,
      customClass: {
        ...this.defaultConfig.customClass,
        confirmButton: 'swal-confirm-btn btn-warning',
      },
      ...options,
    };

    try {
      const result = await Swal.fire(config);

      if (result.isConfirmed && onContinue && typeof onContinue === 'function') {
        await onContinue();
      }

      return result.isConfirmed;
    } catch (error) {
      console.error('Ошибка в предупреждении с таймером:', error);
      return false;
    }
  }

  /**
   * Показывает модальное окно с выбором из списка
   * @param {string} title - Заголовок окна
   * @param {Array} options - Массив опций для выбора [{value: 'val', text: 'Текст'}]
   * @param {string} placeholder - Placeholder для селекта
   * @param {Function} onSelect - Callback при выборе
   * @param {Object} additionalOptions - Дополнительные опции
   * @returns {Promise<string|null>} - Выбранное значение или null при отмене
   */
  async select(
    title = 'Выберите вариант',
    options = [],
    placeholder = 'Выберите...',
    onSelect = null,
    additionalOptions = {}
  ) {
    // Формируем HTML для селекта
    const selectOptions = options
      .map(option => `<option value="${option.value}">${option.text}</option>`)
      .join('');

    const config = {
      ...this.defaultConfig,
      title,
      html: `
        <select class="swal-select form-select" id="swal-select">
          <option value="" disabled selected>${placeholder}</option>
          ${selectOptions}
        </select>
      `,
      showCancelButton: true,
      confirmButtonText: 'Выбрать',
      cancelButtonText: 'Отмена',
      allowOutsideClick: false,
      customClass: {
        ...this.defaultConfig.customClass,
        confirmButton: 'swal-confirm-btn btn-primary',
        cancelButton: 'swal-cancel-btn btn-secondary',
      },
      preConfirm: () => {
        const select = document.getElementById('swal-select');
        const value = select.value;

        if (!value) {
          Swal.showValidationMessage('Пожалуйста, выберите вариант');
          return false;
        }

        return value;
      },
      ...additionalOptions,
    };

    try {
      const result = await Swal.fire(config);

      if (result.isConfirmed && onSelect && typeof onSelect === 'function') {
        await onSelect(result.value);
      }

      return result.isConfirmed ? result.value : null;
    } catch (error) {
      console.error('Ошибка в окне выбора:', error);
      return null;
    }
  }

  /**
   * Показывает информационное сообщение
   * @param {string} title - Заголовок
   * @param {string} message - Текст сообщения
   * @param {Object} options - Дополнительные опции
   * @returns {Promise<void>}
   */
  async info(title = 'Информация', message = '', options = {}) {
    const config = {
      ...this.defaultConfig,
      title,
      text: message,
      icon: 'info',
      confirmButtonText: 'ОК',
      customClass: {
        ...this.defaultConfig.customClass,
        confirmButton: 'swal-confirm-btn btn-info',
      },
      ...options,
    };

    return await Swal.fire(config);
  }

  /**
   * Закрывает текущее модальное окно
   */
  close() {
    Swal.close();
  }

  /**
   * Проверяет, открыто ли модальное окно
   * @returns {boolean}
   */
  isVisible() {
    return Swal.isVisible();
  }
}

// Создаем единственный экземпляр сервиса
const sweetAlertService = new SweetAlertService();

// Экспортируем как экземпляр и отдельные методы для удобства
export default sweetAlertService;

// Привязываем методы к контексту экземпляра
export const confirm = sweetAlertService.confirm.bind(sweetAlertService);
export const success = sweetAlertService.success.bind(sweetAlertService);
export const error = sweetAlertService.error.bind(sweetAlertService);
export const input = sweetAlertService.input.bind(sweetAlertService);
export const timedWarning = sweetAlertService.timedWarning.bind(sweetAlertService);
export const select = sweetAlertService.select.bind(sweetAlertService);
export const info = sweetAlertService.info.bind(sweetAlertService);
export const close = sweetAlertService.close.bind(sweetAlertService);
export const isVisible = sweetAlertService.isVisible.bind(sweetAlertService);
