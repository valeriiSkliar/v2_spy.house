/**
 * Notification checker helper
 *
 * This helper provides functionality to check for unread notifications
 * and update notification indicators in the UI accordingly.
 */

/**
 * Check for unread notifications and update notification indicators
 *
 * @returns {Promise<number>} Promise resolving to the number of unread notifications
 */
const checkNotifications = async () => {
    try {
        // Fetch the count of unread notifications
        const response = await fetch("/notifications/unread-count", {
            method: "GET",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
            },
            credentials: "same-origin",
        });

        if (!response.ok) {
            throw new Error(`Network response was not ok: ${response.status}`);
        }

        const data = await response.json();
        const unreadCount = data.count || 0;

        // Update notification indicators
        updateNotificationIndicators(unreadCount);

        return unreadCount;
    } catch (error) {
        console.error("Error checking for notifications:", error);
        return 0;
    }
};

/**
 * Update notification indicators in the UI based on unread count
 *
 * @param {number} unreadCount - Number of unread notifications
 */
const updateNotificationIndicators = (unreadCount) => {
    // Get all notification indicator elements
    const settingsIndicator = $("#notification-indicator-preview");
    const menuIndicator = $("#notification-indicator-notification-menu");

    // Update the settings gear icon indicator
    updateIndicator(settingsIndicator, unreadCount);

    // Update the notification menu icon indicator
    updateIndicator(menuIndicator, unreadCount);
};

/**
 * Helper function to update a specific notification indicator
 *
 * @param {string} element - jQuery element
 * @param {number} unreadCount - Number of unread notifications
 */
const updateIndicator = (element, unreadCount) => {
    const parentElement = element;
    if (!parentElement) return;

    // Remove existing indicator if it exists
    const existingIndicator = parentElement.find(".has-notification");
    if (existingIndicator) {
        existingIndicator.remove();
    }

    // Add indicator if there are unread notifications
    if (unreadCount > 0) {
        const indicator = document.createElement("span");
        indicator.className = "has-notification";
        parentElement.append(indicator);
    }
};

export { checkNotifications, updateNotificationIndicators };
