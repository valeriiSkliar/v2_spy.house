import { Modal, Toast } from "bootstrap";
import $ from "jquery";

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
 * @param {'success'|'error'|'warning'|'info'} type - Тип тоста для стилизации.
 * @param {number} [delay=5000] - Задержка перед автоматическим скрытием (мс).
 */
export function createAndShowToast(message, type = "info", delay = 5000) {
    const toastContainer = document.querySelector(".toast-container");
    if (!toastContainer) {
        console.error(
            'Toast container ".toast-container" not found in the DOM.'
        );
        return;
    }

    const toastId = `toast-${Date.now()}`;
    const icons = {
        success: '<i class="fas fa-check-circle me-2"></i>',
        error: '<i class="fas fa-times-circle me-2"></i>',
        warning: '<i class="fas fa-exclamation-circle me-2"></i>',
        info: '<i class="fas fa-info-circle me-2"></i>',
    };

    const toastHTML = `
        <div id="${toastId}" class="toast opacity-75 align-items-center border-0 toast-${type}" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="${delay}">
            <div class="d-flex align-items-center p-3">
                <div class="toast-icon me-3">
                    ${icons[type] || icons.info}
                </div>
                <div class="toast-body">
                    ${message}
                </div>
            </div>
            <div class="toast-progress" style="animation-duration: ${delay}ms;"></div>
        </div>
    `;
    toastContainer.insertAdjacentHTML("beforeend", toastHTML);
    const toastElement = document.getElementById(toastId);

    // Добавляем классы для стилизации
    toastElement.classList.add(`toast-${type}`);
    toastElement.classList.add("bg-white");
    toastElement.classList.add("shadow");

    const toastInstance = Toast.getOrCreateInstance(toastElement);
    toastInstance.show();

    // Удаляем элемент тоста из DOM после его скрытия
    toastElement.addEventListener("hidden.bs.toast", () => {
        toastElement.remove();
    });
}