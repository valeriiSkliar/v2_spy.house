import { createAndShowToast } from "../../utils/uiHelpers";
import { apiTokenHandler } from "../api-token/api-token";
import loader from "../loader";
import { ajaxFetcher } from "../fetcher/ajax-fetcher";

/**
 * Profile settings form handler
 * Manages asynchronous form submission with full-screen loading state
 */
export const updateProfileSettingsHandler = {
    init() {
        console.log("Update profile settings handler initialized");
    },
};

const updateProfileSettings = () => {
    const form = $("#personal-settings-form");
    if (form.length) {
        form.on("submit", async function (e) {
            try {
                e.preventDefault();
                console.log("Submit event triggered");
                const formData = new FormData(this);
                loader.show();
                const response = await ajaxFetcher.form(
                    config.apiProfileSettingsEndpoint,
                    formData
                );
                if (response.success) {
                    createAndShowToast(response.message, "success");
                } else {
                    createAndShowToast(response.message, "error");
                }
            } catch (error) {
                console.error("Error updating profile settings:", error);
                createAndShowToast("Error updating profile settings", "error");
            } finally {
                loader.hide();
            }
        });
    }
};

const initUpdateProfileSettings = () => {
    if ($("#personal-settings-form").length) {
        updateProfileSettings();
    }
};

export { initUpdateProfileSettings };
