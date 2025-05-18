// import $ from "jquery";
import { apiTokenHandler } from "@/components";
import landingsStore from "./landingsStore";

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
                        // TODO: Show error toast
                        return;
                    }
                    const data = await response.json();
                    if (data.status !== "pending") {
                        this.stopPolling(landingId);
                        this.updateUI(landingId, data, currentStatusElement);
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
            return;
        }
        return fetch(`/api/landings/${landingId}/status`, {
            method: "GET",
            credentials: "omit",
            headers: {
                Authorization: "Bearer " + token,
            },
        });
    }

    async updateUI(landingId, data, statusElement) {
        // First, check if the element is still in the DOM
        if (!landingsStore.isElementInDOM(landingId)) {
            console.log(`Status element for landing ${landingId} is no longer in the DOM, cannot update UI.`);
            return;
        }

        const $row = statusElement.closest("tr");
        const $controls = $row.find(".table-controls");

        // Assuming statusElement is the <li> tag itself based on selectors like ".landing-status-icon"
        // console.log(statusElement);

        if (data.status === "completed") {
            // statusElement is the <li class="landing-status-icon" data-status="pending">
            statusElement.remove(); // Correctly remove the pending status <li> itself

            // Add download button
            const downloadButtonHtml = `
            <li>
                <a href="/landings/${landingId}/download" class="btn-icon icon-download"></a>
            </li>
            `;
            $controls.prepend(downloadButtonHtml); // Prepend new download button <li> to the <ul>
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
