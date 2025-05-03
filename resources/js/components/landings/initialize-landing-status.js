import landingStatusPoller from "./landing-status-poller";

const initializeLandingStatus = () => {
    const $landingStatusIcons = $(".landing-status-icon");

    if ($landingStatusIcons.length > 0) {
        $landingStatusIcons.each((index, element) => {
            const $element = $(element);
            const status = $element.data("status");

            // Only poll for pending statuses
            if (status === "pending") {
                const landingId = $element
                    .closest("tr")
                    .find(".delete-landing-button")
                    .data("delete-url")
                    .match(/\/(\d+)\?/)[1];
                landingStatusPoller.startPolling(landingId, $element);
            }
        });
    }

    // Clean up when page is unloaded
    $(window).on("beforeunload", () => {
        landingStatusPoller.cleanup();
    });
};

// Also handle dynamically added landings
const initializeDynamicLandingStatus = () => {
    $(document).on("landings:new", (event, landingElement) => {
        const $statusIcon = $(landingElement).find(".landing-status-icon");
        if ($statusIcon.length && $statusIcon.data("status") === "pending") {
            const landingId = $(landingElement)
                .find(".delete-landing-button")
                .data("delete-url")
                .match(/\/(\d+)\?/)[1];
            landingStatusPoller.startPolling(landingId, $statusIcon);
        }
    });
};

export { initializeLandingStatus, initializeDynamicLandingStatus };
