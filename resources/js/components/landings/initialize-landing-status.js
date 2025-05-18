import landingStatusPoller from "./landing-status-poller";
import landingsStore from "./landingsStore";

/**
 * Extract landing ID from delete URL
 * @param {string} deleteUrl - URL for deleting a landing
 * @returns {string|null} Landing ID or null if not found
 */
const extractLandingId = (deleteUrl) => {
    if (!deleteUrl) return null;

    const regex = /landings\/(\d+)(?:[\/?#]|$)/;
    const match = deleteUrl.match(regex);
    return match && match[1] ? match[1] : null;
};

/**
 * Initialize landing status tracking for all landings on the page
 */
const initializeLandingStatus = () => {
    // Start with a clean slate - important after DOM updates
    landingStatusPoller.cleanup();

    const $landingStatusIcons = $(".landing-status-icon");

    if ($landingStatusIcons.length > 0) {
        $landingStatusIcons.each((index, element) => {
            const $element = $(element);
            const status = $element.data("status");

            // Only poll for pending statuses
            if (status === "pending" || status === "in_progress") {
                const deleteButton = $element
                    .closest("tr")
                    .find(".delete-landing-button");
                const deleteUrl = deleteButton.data("delete-url");
                const landingId = extractLandingId(deleteUrl);

                if (landingId) {
                    landingStatusPoller.startPolling(landingId, $element);
                } else {
                    console.warn(
                        `Could not extract landingId from delete-url: ${deleteUrl} for initial landing status.`
                    );
                }
            }
        });
    }

    // Clean up when page is unloaded
    $(window).on("beforeunload", () => {
        landingStatusPoller.cleanup();
    });
};

/**
 * Handle dynamically added landings via event system
 */
const initializeDynamicLandingStatus = () => {
    $(document).on("landings:new", (event, landingElement) => {
        const $statusIcon = $(landingElement).find(".landing-status-icon");
        if (
            $statusIcon.length &&
            ($statusIcon.data("status") === "pending" ||
                $statusIcon.data("status") === "in_progress")
        ) {
            const deleteButton = $(landingElement).find(
                ".delete-landing-button"
            );
            const deleteUrl = deleteButton.data("delete-url");
            const landingId = extractLandingId(deleteUrl);

            if (landingId) {
                landingStatusPoller.startPolling(landingId, $statusIcon);
            } else {
                console.warn(
                    `Could not extract landingId from delete-url: ${deleteUrl} for dynamic landing.`
                );
            }
        }
    });
};

export { initializeLandingStatus, initializeDynamicLandingStatus };
