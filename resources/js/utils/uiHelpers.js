// import { Modal } from 'bootstrap';
import Swal from 'sweetalert2';

// Предполагаем, что SweetAlert2 уже импортирован глобально как Swal
// Если используется ES modules, можно добавить: import Swal from 'sweetalert2';

/**
 * UI HELPERS - МИГРАЦИЯ НА SWEETALERT2
 *
 * Данный модуль был рефакторен для использования SweetAlert2 вместо Bootstrap Toast.
 * Обратная совместимость сохранена - все существующие вызовы будут работать.
 *
 * ТРЕБОВАНИЯ:
 * - SweetAlert2 должен быть подключен к проекту (глобально как Swal)
 * - Для подключения: npm install sweetalert2 или <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
 *
 * РЕКОМЕНДУЕМОЕ ИСПОЛЬЗОВАНИЕ:
 * - createAndShowToast(message, type, delay, clearPrevious) - основная функция для тостов
 * - showToast(toastId, options) - legacy функция (работает, но лучше не использовать)
 * - clearAllToasts() - очистка всех активных тостов
 *
 * ПРИМЕРЫ:
 * createAndShowToast('Успешно сохранено!', 'success');
 * createAndShowToast('Произошла ошибка', 'error', 3000);
 * createAndShowToast('Предупреждение', 'warning', 0); // 0 = без автозакрытия
 */

// Хранилище активных SweetAlert тостов для управления ими
let activeToasts = new Set();

/**
 * Показывает модальное окно Bootstrap по его ID.
 * @param {string} modalId - ID HTML-элемента модального окна (без #).
 */
export function showModal(modalId) {
  const modalElement = document.getElementById(modalId);
  if (modalElement) {
    const modalInstance = Modal.getOrCreateInstance(modalElement);
    modalInstance.show();
  } else {
    console.error(`Modal with id "${modalId}" not found.`);
  }
}

/**
 * Скрывает модальное окно Bootstrap по его ID.
 * @param {string} modalId - ID HTML-элемента модального окна (без #).
 */
export function hideModal(modalId) {
  const modalElement = document.getElementById(modalId);
  if (modalElement) {
    const modalInstance = Modal.getInstance(modalElement);
    if (modalInstance) {
      modalInstance.hide();
    }
  } else {
    console.error(`Modal with id "${modalId}" not found.`);
  }
}

/**
 * Показывает тост SweetAlert по его ID.
 * Для обратной совместимости принимает toastId, но теперь игнорирует его
 * и создает новый SweetAlert тост.
 * @param {string} toastId - ID тоста (игнорируется, сохранен для совместимости).
 * @param {object} [options] - Опции для тоста.
 */
export function showToast(toastId, options = {}) {
  console.warn('showToast(toastId) is deprecated. Use createAndShowToast() for better control.');

  // Извлекаем сообщение из опций или используем ID как сообщение
  const message = options.message || options.text || toastId || 'Уведомление';
  const type = options.type || options.icon || 'info';
  const delay = options.delay || options.timer || 5000;

  createAndShowToast(message, type, delay, false);
}

/**
 * Удаляет все активные SweetAlert тосты.
 */
export function clearAllToasts() {
  // Закрываем все активные SweetAlert тосты
  activeToasts.forEach(toast => {
    if (toast && typeof toast.close === 'function') {
      toast.close();
    }
  });
  activeToasts.clear();
}

/**
 * Динамически создает и показывает SweetAlert тост с сообщением.
 * @param {string} message - Текст сообщения.
 * @param {'success'|'error'|'warning'|'info'} type - Тип тоста для стилизации.
 * @param {number} [delay=5000] - Задержка перед автоматическим скрытием (мс).
 * @param {boolean} [clearPrevious=true] - Очищать ли предыдущие тосты перед показом нового.
 */
export function createAndShowToast(message, type = 'info', delay = 5000, clearPrevious = true) {
  // Проверяем доступность SweetAlert
  if (typeof Swal === 'undefined') {
    console.error('SweetAlert2 is not available. Please include SweetAlert2 library.');
    // Фоллбэк на обычный alert для критических сообщений
    if (type === 'error') {
      alert(`Ошибка: ${message}`);
    }
    return;
  }

  // Очищаем предыдущие тосты если указано
  if (clearPrevious) {
    clearAllToasts();
  }

  // Маппинг типов на иконки SweetAlert
  const iconMap = {
    success: 'success',
    error: 'error',
    warning: 'warning',
    info: 'info',
  };

  // Настройки для SweetAlert тоста
  const swalOptions = {
    toast: true,
    position: 'bottom-end',
    showConfirmButton: false,
    timer: delay > 0 ? delay : undefined, // Если delay = 0, тост не закроется автоматически
    timerProgressBar: delay > 0,
    icon: iconMap[type] || 'info',
    title: message,
    didOpen: toast => {
      // Добавляем в хранилище активных тостов
      activeToasts.add({
        element: toast,
        close: () => Swal.close(),
      });

      // Добавляем обработчик наведения для паузы таймера
      toast.addEventListener('mouseenter', Swal.stopTimer);
      toast.addEventListener('mouseleave', Swal.resumeTimer);
    },
    didClose: toast => {
      // Удаляем из хранилища активных тостов
      activeToasts.forEach(activeToast => {
        if (activeToast.element === toast) {
          activeToasts.delete(activeToast);
        }
      });
    },
  };

  // Дополнительная настройка в зависимости от типа
  switch (type) {
    case 'success':
      swalOptions.background = '#d4edda';
      swalOptions.color = '#155724';
      break;
    case 'error':
      swalOptions.background = '#f8d7da';
      swalOptions.color = '#721c24';
      break;
    case 'warning':
      swalOptions.background = '#fff3cd';
      swalOptions.color = '#856404';
      break;
    case 'info':
    default:
      swalOptions.background = '#cce7ff';
      swalOptions.color = '#004085';
      break;
  }

  // Показываем SweetAlert тост
  const swalInstance = Swal.fire(swalOptions);

  return swalInstance;
}
