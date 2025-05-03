// src/js/uiHelpers.js
import { Modal, Toast } from 'bootstrap';
import $ from 'jquery';

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
 * Показывает тост Bootstrap.
 * @param {string} toastId - ID HTML-элемента тоста (без #).
 * @param {object} [options] - Опции для конструктора Toast (необязательно).
 */
export function showToast(toastId, options = {}) {
  const toastElement = document.getElementById(toastId);
  if (toastElement) {
    const toastInstance = Toast.getOrCreateInstance(toastElement, options);
    toastInstance.show();
  } else {
    console.error(`Toast with id "${toastId}" not found.`);
  }
}

/**
 * Динамически создает и показывает тост с сообщением.
 * Требует наличия контейнера для тостов в HTML, например:
 * <div class="toast-container position-fixed bottom-0 end-0 p-3"></div>
 * @param {string} message - Текст сообщения.
 * @param {'success'|'error'|'warning'|'info'} type - Тип тоста для стилизации (добавьте соответствующие CSS классы).
 * @param {number} [delay=5000] - Задержка перед автоматическим скрытием.
 */
export function createAndShowToast(message, type = 'info', delay = 5000) {
    const toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        console.error('Toast container ".toast-container" not found in the DOM.');
        return;
    }
    console.log('Toast container found in the DOM.');

    const toastId = `toast-${Date.now()}`;
    const toastHTML = `
        <div id="${toastId}" class="toast align-items-center text-bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="${delay}">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    console.log('Toast HTML created.');
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    const toastElement = document.getElementById(toastId);
    const toastInstance = Toast.getOrCreateInstance(toastElement);
    console.log('Toast instance created.');
    toastInstance.show();
    console.log('Toast shown.', toastInstance);

    // Удаляем элемент тоста из DOM после его скрытия
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}