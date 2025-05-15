import { initializeSelectComponent } from "@/helpers";
import { NotificationItem } from "../components/notifications/notification-item";

document.addEventListener("DOMContentLoaded", function () {
    NotificationItem.init();

    const markAllReadBtn = document.getElementById("mark-all-read");
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener("click", function () {
            NotificationItem.markAllAsRead(markAllReadBtn.dataset.url);
        });
    }

    initializeSelectComponent("#per-page", {
        selectors: {
            select: ".base-select__dropdown",
            options: ".base-select__option",
            trigger: ".base-select__trigger",
            valueElement: "[data-value]",
        },
        params: {
            valueParam: "per_page",
        },
        resetPage: true,
    });
});
