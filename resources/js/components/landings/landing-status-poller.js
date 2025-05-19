// import $ from "jquery";
import { apiTokenHandler } from "@/components";
import landingsStore from "./landingsStore";
import { createAndShowToast } from "../../utils";
import { initDownloadLandingHandler } from "./downloadLandingHandler";

class LandingStatusPoller {
    constructor() {
        this.pollingInterval = 5000; // 5 seconds
        this.activePolls = new Map();
    }

    startPolling(landingId, statusElement) {
        // Register landing in the store
        landingsStore.registerLanding(landingId, statusElement);

        // Prevent duplicate polling for the same landing
        if (this.activePolls.has(landingId)) {
            return;
        }

        const pollInterval = setInterval(() => {
            // Skip this iteration if the element is no longer in the DOM
            if (!landingsStore.isElementInDOM(landingId)) {
                console.log(`Status element for landing ${landingId} is no longer in the DOM, stopping polling.`);
                this.stopPolling(landingId);
                return;
            }
            
            // Get the current status element for this landing, which may have been updated
            const currentStatusElement = landingsStore.getStatusElement(landingId);

            this.checkStatus(landingId, currentStatusElement)
                .then(async (response) => {
                    if (response.ok === false) {
                        console.error("Error polling landing status");
                        this.stopPolling(landingId);
                        this.updateUI(
                            landingId,
                            { status: "failed" },
                            currentStatusElement
                        );
                        createAndShowToast("Error polling landing status", "error");
                        return;
                    }
                    const data = await response.json();
                    if (data.status !== "pending" && data.status !== "in_progress") {
                        // Сначала обновляем UI, затем останавливаем поллинг
                        await this.updateUI(landingId, data, currentStatusElement);
                        this.stopPolling(landingId);
                    }
                })
                .catch((error) => {
                    console.error(`Error polling landing ${landingId}:`, error);
                    // Stop polling on error
                    this.stopPolling(landingId);
                });
        }, this.pollingInterval);

        this.activePolls.set(landingId, pollInterval);
    }

    stopPolling(landingId) {
        const interval = this.activePolls.get(landingId);
        if (interval) {
            clearInterval(interval);
            this.activePolls.delete(landingId);
        }
        
        // Also unregister from the store
        landingsStore.unregisterLanding(landingId);
    }

    async checkStatus(landingId, statusElement) {
        const token = apiTokenHandler.getToken();
        if (!token) {
            console.error("API token not found in localStorage (key: bt)");
            return { ok: false, error: "API token not found" };
        }
        try {
            return await fetch(`/api/landings/${landingId}/status`, {
                method: "GET",
                credentials: "omit",
                headers: {
                    Authorization: "Bearer " + token,
                },
            });
        } catch (error) {
            console.error(`Error fetching status for landing ${landingId}:`, error);
            return { ok: false, error: error.message };
        }
    }

    async updateUI(landingId, data, statusElement) {
        // Проверяем, существует ли элемент и находится ли он в DOM
        if (!statusElement || !statusElement.length || !$.contains(document, statusElement[0])) {
            console.log(`Status element for landing ${landingId} is no longer in the DOM, cannot update UI.`);
            return;
        }

        const $row = statusElement.closest("tr");
        const $controls = $row.find(".table-controls");

        console.log(`Updating UI for landing ${landingId} with status: ${data.status}, current element status: ${statusElement.data('status')}`);
    
        if (data.status === "completed") {
            console.log(`Landing ${landingId} completed, removing status element and adding download button`);
            
            // Проверяем, существует ли уже кнопка скачивания
            const existingDownloadButton = $controls.find('a.icon-download');
            if (existingDownloadButton.length === 0) {
                // Удаляем элемент статуса
                statusElement.remove();
                
                // Добавляем кнопку скачивания с атрибутом data-id для обработчика
                const downloadButtonHtml = `
                <li>
                    <a href="/landings/${landingId}/download" class="btn-icon icon-download download-landing-button" data-id="${landingId}"></a>
                </li>
                `;
                $controls.prepend(downloadButtonHtml); // Добавляем кнопку скачивания в начало списка
                
                // Инициализируем обработчик скачивания
                initDownloadLandingHandler();
            } else {
                console.log(`Download button already exists for landing ${landingId}, updating button attributes and removing status element`);
                
                // Обновляем атрибуты существующей кнопки скачивания
                const existingDownloadButton = $controls.find('a.icon-download');
                existingDownloadButton.addClass('download-landing-button').attr('data-id', landingId);
                
                // Удаляем элемент статуса
                statusElement.remove();
                
                // Инициализируем обработчик скачивания
                initDownloadLandingHandler();
            }
        } else if (data.status === "failed") {
            // statusElement is the <li class="landing-status-icon" data-status="pending">
            // Update to error status icon by replacing the status <li>
            const errorIconHtml = `
                <li class="landing-status-icon" data-status="failed">
                    <span class="btn-icon icon-warning"></span>
                </li>
            `;
            statusElement.replaceWith(errorIconHtml); // Replace the old status <li> with the new error status <li>
        }

        // Re-enable delete button if it was disabled
        // This button is assumed to be already present in its own <li> with all necessary data attributes
        const $deleteButton = $controls.find(".delete-landing-button");
        if ($deleteButton.length) {
            $deleteButton.prop("disabled", false);
        }

        // Unregister from the store as we're done with this landing
        landingsStore.unregisterLanding(landingId);
    }

    // Clean up all active polls
    cleanup() {
        this.activePolls.forEach((interval, landingId) => {
            clearInterval(interval);
        });
        this.activePolls.clear();
        
        // Also clear the store
        landingsStore.clear();
    }
}

export default new LandingStatusPoller();
