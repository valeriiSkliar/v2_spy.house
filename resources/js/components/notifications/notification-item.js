import { ajaxFetcher } from "../fetcher/ajax-fetcher";
import { config } from "@/config";

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
        } catch (error) {
            console.error("Failed to mark notification as read:", error);
        }
    }

    static async markAllAsRead(url) {
        try {
            await ajaxFetcher.post(url);

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
        } catch (error) {
            console.error("Failed to mark all notifications as read:", error);
            if (window.showToast) {
                window.showToast(
                    window.translations.notifications.mark_all_read_error,
                    "error"
                );
            }
        }
    }
}
