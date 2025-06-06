import { checkNotifications } from "../../helpers/notification-checker";
import { createAndShowToast } from "../../utils";
import { ajaxFetcher } from "../fetcher/ajax-fetcher";
import { config } from "@/config";
import { hideInButton, showInButton } from "../loader";

export class NotificationItem {
    static init() {
        const container = document.querySelector(".notification-list");
        if (!container) return;

        container.addEventListener(
            "click",
            this.handleContainerClick.bind(this)
        );
        this.updateMarkAllReadButton();
    }

    static updateMarkAllReadButton() {
        const button = document.getElementById("mark-all-read");
        if (!button) return;

        const hasUnread = document.querySelector(
            ".notification-item:not(._read)"
        );
        button.disabled = !hasUnread;
    }

    static async handleContainerClick(event) {
        const button = event.target.closest(".notification-item__btn button");
        if (!button) return;

        const item = button.closest(".notification-item");
        if (!item) return;

        try {
            const url = button.dataset.url;
            await ajaxFetcher.post(url);

            item.classList.add("_read");
            item.dataset.read = "true";

            const label = item.querySelector(".notification-item__label");

            button.style.display = "none";
            this.updateMarkAllReadButton();
            createAndShowToast("Notification marked as read.", "success");
        } catch (error) {
            console.error("Failed to mark notification as read:", error);
        }
    }

    static async markAllAsRead(url) {
        const markAllReadBtn = document.getElementById("mark-all-read");
        try {
            if (!markAllReadBtn) return Promise.resolve();
            
            showInButton(markAllReadBtn);
            await ajaxFetcher.post(url);
            hideInButton(markAllReadBtn);

            document.querySelectorAll(".notification-item").forEach((item) => {
                item.classList.add("_read");
                item.dataset.read = "true";

                const label = item.querySelector(".notification-item__label");

                const button = item.querySelector(
                    ".notification-item__btn button"
                );
                if (button) {
                    button.style.display = "none";
                }
            });

            this.updateMarkAllReadButton();
            createAndShowToast("All notifications marked as read.", "success");
            checkNotifications();
            return Promise.resolve();
        } catch (error) {
            createAndShowToast(
                error.message ||
                    "Error marking all notifications as read. Please try again.",
                "error"
            );
            hideInButton(markAllReadBtn);
            console.error("Failed to mark all notifications as read:", error);
            return Promise.reject(error);
        }
    }
}
