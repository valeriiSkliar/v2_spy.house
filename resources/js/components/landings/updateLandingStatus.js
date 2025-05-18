import landingsStore from "./landingsStore";

/**
 * Обновляет статус лендинга в DOM
 * @param {string} landingId - ID лендинга
 * @param {string} status - Новый статус ('failed', 'completed', etc.)
 */
export function updateLandingStatus(landingId, status) {
    // Находим строку с лендингом
    const $row = $(`.delete-landing-button[data-id="${landingId}"]`).closest("tr");
    
    if (!$row.length) {
        console.error(`Не удалось найти строку для лендинга с ID ${landingId}`);
        return;
    }
    
    const $controls = $row.find(".table-controls");
    
    // Находим кнопку скачивания
    const $downloadButton = $controls.find(".download-landing-button").closest("li");
    
    if (status === "failed") {
        // Заменяем кнопку скачивания на иконку ошибки
        const errorIconHtml = `
            <li class="landing-status-icon" data-status="failed">
                <span class="btn-icon icon-warning"></span>
            </li>
        `;
        
        if ($downloadButton.length) {
            // Если есть кнопка скачивания, заменяем ее на иконку ошибки
            $downloadButton.replaceWith(errorIconHtml);
        } else {
            // Если нет кнопки скачивания, добавляем иконку ошибки в начало
            $controls.prepend(errorIconHtml);
        }
    }
}

/**
 * Обновляет статус лендинга на "failed" при ошибке скачивания
 * @param {string} landingId - ID лендинга
 */
export function markLandingAsFailed(landingId) {
    updateLandingStatus(landingId, "failed");
}
