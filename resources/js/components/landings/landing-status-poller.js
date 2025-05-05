// import $ from "jquery";
import { apiTokenHandler } from "@/components";
class LandingStatusPoller {
    constructor() {
        this.pollingInterval = 5000; // 5 seconds
        this.activePolls = new Map();
    }

    startPolling(landingId, statusElement) {
        // Prevent duplicate polling for the same landing
        if (this.activePolls.has(landingId)) {
            return;
        }

        const pollInterval = setInterval(() => {
            this.checkStatus(landingId, statusElement)
                .then(async (response) => {
                    if (response.ok === false) {
                        console.error("Error polling landing status");
                        this.stopPolling(landingId);
                        this.updateUI(
                            landingId,
                            { status: "failed" },
                            statusElement
                        );
                        // TODO: Show error toast
                        return;
                    }
                    const data = await response.json();
                    if (data.status !== "pending") {
                        this.stopPolling(landingId);
                        this.updateUI(landingId, data, statusElement);
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
        const $row = statusElement.closest("tr");
        const $controls = $row.find(".table-controls");

        // Remove the status icon container
        console.log(statusElement);

        if (data.status === "completed") {
            // Add download button
            const downloadButton = `
            <li>
            <a href="/landings/${landingId}/download" class="btn-icon icon-download"></a>
            </li>
            `;
            statusElement.parent().remove();
            $controls.prepend(downloadButton);
        } else if (data.status === "failed") {
            // Update to error status icon
            statusElement.remove();
            const errorIcon = `
                <li class="landing-status-icon" data-status="failed">
                    <span class="btn-icon icon-warning"></span>
                </li>
            `;
            $controls.prepend(errorIcon);
        }

        // Re-enable delete button if it was disabled
        const $deleteButton = $controls.find(".delete-landing-button");
        $deleteButton.prop("disabled", false);
    }

    // Clean up all active polls
    cleanup() {
        this.activePolls.forEach((interval, landingId) => {
            clearInterval(interval);
        });
        this.activePolls.clear();
    }
}

export default new LandingStatusPoller();
