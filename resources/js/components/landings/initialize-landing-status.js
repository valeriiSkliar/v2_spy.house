import landingStatusPoller from "./landing-status-poller";

const initializeLandingStatus = () => {
    const $landingStatusIcons = $(".landing-status-icon");

    if ($landingStatusIcons.length > 0) {
        $landingStatusIcons.each((index, element) => {
            const $element = $(element);
            const status = $element.data("status");

            // Only poll for pending statuses
            if (status === "pending") {
                const deleteButton = $element
                    .closest("tr")
                    .find(".delete-landing-button");
                const deleteUrl = deleteButton.data("delete-url");
                if (deleteUrl) {
                    const regex = /landings\/(\d+)(?:[\/?#]|$)/;
                    const match = deleteUrl.match(regex);
                    if (match && match[1]) {
                        const landingId = match[1];
                        landingStatusPoller.startPolling(landingId, $element);
                    } else {
                        console.warn(
                            `Could not extract landingId from delete-url: ${deleteUrl} for initial landing status. Regex was ${regex.toString()}`
                        );
                    }
                } else {
                    console.warn(
                        "delete-url not found on delete button for initial landing status."
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

// Also handle dynamically added landings
const initializeDynamicLandingStatus = () => {
    $(document).on("landings:new", (event, landingElement) => {
        const $statusIcon = $(landingElement).find(".landing-status-icon");
        if ($statusIcon.length && $statusIcon.data("status") === "pending") {
            const deleteButton = $(landingElement).find(
                ".delete-landing-button"
            );
            const deleteUrl = deleteButton.data("delete-url");
            if (deleteUrl) {
                const regex = /landings\/(\d+)(?:[\/?#]|$)/;
                const match = deleteUrl.match(regex);
                if (match && match[1]) {
                    const landingId = match[1];
                    landingStatusPoller.startPolling(landingId, $statusIcon);
                } else {
                    console.warn(
                        `Could not extract landingId from delete-url: ${deleteUrl} for dynamic landing. Regex was ${regex.toString()}`
                    );
                }
            } else {
                console.warn(
                    "delete-url not found on delete button for dynamic landing."
                );
            }
        }
    });
};

export { initializeLandingStatus, initializeDynamicLandingStatus };
