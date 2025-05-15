/**
 * Notification checker helper
 *
 * This helper provides functionality to check for unread notifications
 * and update notification indicators in the UI accordingly.
 */

import { profileFormSelectors } from "../components/profile/profile-form-selectors";

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
    const settingsIndicator = $(
        profileFormSelectors.notificationIndicatorPreview
    );
    const menuIndicator = $(
        profileFormSelectors.notificationIndicatorNotificationMenu
    );

    // Update both indicators
    updateIndicator(settingsIndicator, unreadCount);
    updateIndicator(menuIndicator, unreadCount);
};

/**
 * Helper function to update a specific notification indicator
 *
 * @param {jQuery} element - jQuery element
 * @param {number} unreadCount - Number of unread notifications
 */
const updateIndicator = (element, unreadCount) => {
    if (!element.length) return;

    // Remove existing indicators if they exist
    element.find(".has-notification").remove();
    element.siblings(".has-notification").remove();

    // Add indicator if there are unread notifications
    if (unreadCount > 0) {
        const indicator = $("<span>").addClass("has-notification");

        // For notification menu icon, add inside
        if (element.attr("id") === "notification-indicator-notification-menu") {
            element.append(indicator);
        } else {
            // For settings icon, add after
            element.after(indicator);
        }
    }
};

export { checkNotifications, updateNotificationIndicators };
