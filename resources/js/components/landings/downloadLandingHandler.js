import { landingsConstants } from "./constants";
import { createAndShowToast } from "../../utils/uiHelpers";

/**
 * Обработчик для асинхронного скачивания лендинга
 * @param {Event} event - Событие клика
 */
export const downloadLandingHandler = function (event) {
    event.preventDefault();
    
    const $button = $(this);
    const landingId = $button.data("id");
    
    if (!landingId) {
        console.error("Landing ID not found in download button data attributes");
        return;
    }
    
    // Добавляем класс загрузки к кнопке
    $button.addClass("is-loading");
    
    // Выполняем AJAX запрос для получения URL скачивания
    $.ajax({
        url: `/api/landings/${landingId}/download`,
        method: "GET",
        success: function(response) {
            if (response.success) {
                // Создаем временную ссылку для скачивания файла
                const downloadUrl = response.data.download_url;
                
                // Создаем невидимый элемент <a> и имитируем клик для скачивания
                const downloadLink = document.createElement("a");
                downloadLink.href = downloadUrl;
                downloadLink.target = "_blank";
                downloadLink.style.display = "none";
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);
                
                // Показываем уведомление об успешном скачивании
                createAndShowToast("Файл скачивается в фоновом режиме", "success");
            } else {
                // Обрабатываем известные ошибки
                let errorTitle = "Ошибка скачивания";
                let errorMessage = response.message || "Не удалось скачать лендинг";
                
                // Переводим сообщения об ошибках// Было
                createAndShowToast({
                    title: "Скачивание начато",
                    message: "Файл скачивается в фоновом режиме",
                    type: "success"
                });
                
                // Стало
                createAndShowToast("Файл скачивается в фоновом режиме", "success");
                if (errorMessage === "landings.download.file_not_found") {
                    errorMessage = "Файл лендинга не найден на сервере. Возможно, он был удален.";
                } else if (errorMessage === "landings.download.not_completed") {
                    errorMessage = "Лендинг еще не готов к скачиванию. Дождитесь завершения загрузки.";
                }
                
                // Показываем уведомление об ошибке
                createAndShowToast(errorMessage, "error");
                
                console.error(`Ошибка скачивания лендинга ID=${landingId}:`, response);
            }
        },
        error: function(xhr, status, error) {
            let errorTitle = "Ошибка скачивания";
            let errorMessage = "Произошла ошибка при скачивании лендинга";
            
            // Обрабатываем разные типы ошибок
            if (xhr.status === 404) {
                errorMessage = `Лендинг с ID=${landingId} не найден на сервере`;
            } else if (xhr.status === 403) {
                errorMessage = "У вас нет прав для скачивания этого лендинга";
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                // Переводим сообщения об ошибках
                if (xhr.responseJSON.message === "landings.download.file_not_found") {
                    errorMessage = "Файл лендинга не найден на сервере. Возможно, он был удален.";
                } else if (xhr.responseJSON.message === "landings.download.not_completed") {
                    errorMessage = "Лендинг еще не готов к скачиванию. Дождитесь завершения загрузки.";
                } else {
                    errorMessage = xhr.responseJSON.message;
                }
            }
            
            // Показываем уведомление об ошибке
            createAndShowToast(errorMessage, "error");
            
            console.error(`Ошибка скачивания лендинга ID=${landingId}:`, { status, error, xhr });
        },
        complete: function() {
            // Убираем класс загрузки с кнопки
            $button.removeClass("is-loading");
        }
    });
};

/**
 * Инициализация обработчика скачивания лендинга
 */
export function initDownloadLandingHandler() {
    const tableContainerSelector = landingsConstants.LANDINGS_TABLE_CONTAINER_ID;
    const downloadButtonSelector = ".download-landing-button";
    
    // Удаляем предыдущие обработчики и добавляем новый с делегированием событий
    $(document)
        .off("click", `${tableContainerSelector} ${downloadButtonSelector}`)
        .on(
            "click",
            `${tableContainerSelector} ${downloadButtonSelector}`,
            downloadLandingHandler
        );
}
