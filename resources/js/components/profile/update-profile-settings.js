import { createAndShowToast } from "../../utils/uiHelpers";
import { apiTokenHandler } from "../api-token/api-token";
import loader from "../loader";
import { ajaxFetcher } from "../fetcher/ajax-fetcher";
import { config } from "../../config";
import { initSocialMessengerField } from "./social-messenger-field";

async function submitFormHandler(e) {
    try {
        e.preventDefault();
        console.log("Submit event triggered");
        const formData = new FormData(this);
        loader.show();
        const response = await ajaxFetcher.form(
            config.apiProfileSettingsEndpoint,
            formData,
            "PUT"
        );
        if (response.success) {
            // Update form fields with new values from server
            const formContainer = $("#personal-settings-form");
            if (formContainer && response.user) {
                // Update messenger field values
                if (response.user.telegram !== undefined) {
                    $('input[name="telegram"]').val(response.user.telegram);
                }
                if (response.user.viber_phone !== undefined) {
                    $('input[name="viber_phone"]').val(response.user.viber_phone);
                }
                if (response.user.whatsapp_phone !== undefined) {
                    $('input[name="whatsapp_phone"]').val(response.user.whatsapp_phone);
                }

                // Update visible messenger field based on which one is set
                const visibleInput = $('input[name="visible_value"]');
                const currentType = visibleInput.data('type');
                if (currentType === 'telegram' && response.user.telegram !== undefined) {
                    visibleInput.val(response.user.telegram);
                } else if (currentType === 'viber_phone' && response.user.viber_phone !== undefined) {
                    visibleInput.val(response.user.viber_phone);
                } else if (currentType === 'whatsapp_phone' && response.user.whatsapp_phone !== undefined) {
                    visibleInput.val(response.user.whatsapp_phone);
                }

                // Update other form fields
                if (response.user.login !== undefined) {
                    $('input[name="login"]').val(response.user.login);
                }
                if (response.user.experience !== undefined) {
                    $('select[name="experience"]').val(response.user.experience);
                }
                if (response.user.scope_of_activity !== undefined) {
                    $('select[name="scope_of_activity"]').val(response.user.scope_of_activity);
                }

                // If the server still returns HTML, use it as a fallback
                if (response.settingsFormHtml) {
                    formContainer.html(response.settingsFormHtml);
                    updateProfileSettings();
                }
            }
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
}

const updateProfileSettings = () => {
    const form = $("#personal-settings-form");
    if (form.length) {
        form.off("submit").on("submit", submitFormHandler);

        // Initialize social messenger field component
        initSocialMessengerField();
    }
};

const initUpdateProfileSettings = () => {
    if ($("#personal-settings-form").length) {
        updateProfileSettings();
    }
};

export { initUpdateProfileSettings };
