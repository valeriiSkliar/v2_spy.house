// import $ from "jquery";

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
                .then((response) => {
                    if (response.status !== "pending") {
                        this.stopPolling(landingId);
                        this.updateUI(landingId, response, statusElement);
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
        return $.ajax({
            url: `/landings/${landingId}/status`,
            method: "GET",
            dataType: "json",
        });
    }

    updateUI(landingId, response, statusElement) {
        const $row = statusElement.closest("tr");
        const $controls = $row.find(".table-controls");

        // Remove the status icon container
        statusElement.parent().remove();

        if (response.status === "completed") {
            // Add download button
            const downloadButton = `
                <li>
                    <a href="/landings/${landingId}/download" class="btn-icon icon-download"></a>
                </li>
            `;
            $controls.prepend(downloadButton);
        } else if (response.status === "failed") {
            // Update to error status icon
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
