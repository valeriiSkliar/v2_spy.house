import flatpickr from "flatpickr";

import "flatpickr/dist/flatpickr.css";
import { profileSettingsHandler } from "../components";
import { config } from "../config";
import { initChangePassword } from "../components/profile/change-password";
document.addEventListener("DOMContentLoaded", function () {
    // Initialize profile settings handler with API endpoint

    if (document.getElementById("personal-settings-form")) {
        profileSettingsHandler.init({
            formId: "personal-settings-form",
            apiEndpoint: config.apiProfileSettingsEndpoint,
        });
    }
    initChangePassword();
});
flatpickr("#dateRangePicker", {
    // mode: "range",
    dateFormat: "Y-m-d",
    onOpen: function (selectedDates, dateStr, instance) {
        instance.element
            .closest(".data-control-date-picker")
            .classList.add("active");
    },
    onClose: function (selectedDates, dateStr, instance) {
        instance.element
            .closest(".data-control-date-picker")
            .classList.remove("active");
    },
});
