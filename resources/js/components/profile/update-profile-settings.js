import { createAndShowToast } from "../../utils/uiHelpers";
import { apiTokenHandler } from "../api-token/api-token";
import loader from "../loader";
import { ajaxFetcher } from "../fetcher/ajax-fetcher";
import { config } from "../../config";
import { initSocialMessengerField } from "./social-messenger-field";
import {
    profileFormElements,
    initProfileFormElements,
} from "./profile-form-elements";

// Function to update selected messenger state
const updateSelectedMessengerState = (type, value) => {
    const selectedOption =
        profileFormElements.profileMessangerSelectOptions.filter(
            `[data-value="${type}"]`
        );

    if (selectedOption.length) {
        // Update selected class
        profileFormElements.profileMessangerSelectOptions.removeClass(
            "is-selected"
        );
        selectedOption.addClass("is-selected");

        // Update trigger image and structure
        const imgSrc = selectedOption.find("img").attr("src");
        profileFormElements.profileMessangerSelectTrigger.html(`
            <span class="base-select__value">
                <span class="base-select__img">
                    <img src="${imgSrc}" alt="${type}">
                </span>
            </span>
            <span class="base-select__arrow"></span>
        `);
    }
};

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
            const formContainer = profileFormElements.form;
            if (formContainer && response.user) {
                // Update messenger field values
                if (response.user.telegram !== undefined) {
                    profileFormElements.telegram.val(response.user.telegram);
                    profileFormElements.profileMessangerSelectOptions
                        .filter('[data-value="telegram"]')
                        .attr("data-phone", response.user.telegram);
                }
                if (response.user.viber_phone !== undefined) {
                    profileFormElements.viberPhone.val(
                        response.user.viber_phone
                    );
                    profileFormElements.profileMessangerSelectOptions
                        .filter('[data-value="viber_phone"]')
                        .attr("data-phone", response.user.viber_phone);
                }
                if (response.user.whatsapp_phone !== undefined) {
                    profileFormElements.whatsappPhone.val(
                        response.user.whatsapp_phone
                    );
                    profileFormElements.profileMessangerSelectOptions
                        .filter('[data-value="whatsapp_phone"]')
                        .attr("data-phone", response.user.whatsapp_phone);
                }

                // Update visible messenger field based on which one is set
                const visibleInput = profileFormElements.visibleValue;
                const currentType = visibleInput.data("type");
                if (
                    currentType === "telegram" &&
                    response.user.telegram !== undefined
                ) {
                    visibleInput.val(response.user.telegram);
                    updateSelectedMessengerState(
                        "telegram",
                        response.user.telegram
                    );
                } else if (
                    currentType === "viber_phone" &&
                    response.user.viber_phone !== undefined
                ) {
                    visibleInput.val(response.user.viber_phone);
                    updateSelectedMessengerState(
                        "viber_phone",
                        response.user.viber_phone
                    );
                } else if (
                    currentType === "whatsapp_phone" &&
                    response.user.whatsapp_phone !== undefined
                ) {
                    visibleInput.val(response.user.whatsapp_phone);
                    updateSelectedMessengerState(
                        "whatsapp_phone",
                        response.user.whatsapp_phone
                    );
                }

                // Update other form fields
                if (response.user.login !== undefined) {
                    profileFormElements.login.val(response.user.login);
                    if (profileFormElements.userPreviewName.length) {
                        profileFormElements.userPreviewName.text(
                            response.user.login
                        );
                    }
                }
                if (response.user.experience !== undefined) {
                    profileFormElements.experience.val(
                        response.user.experience
                    );
                }
                if (response.user.scope_of_activity !== undefined) {
                    profileFormElements.scopeOfActivity.val(
                        response.user.scope_of_activity
                    );
                }

                // If the server still returns HTML, use it as a fallback
                if (response.settingsFormHtml) {
                    formContainer.html(response.settingsFormHtml);
                    initProfileFormElements();
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
    if (profileFormElements.form.length) {
        profileFormElements.form.off("submit").on("submit", submitFormHandler);
        initSocialMessengerField();
    }
};

const initUpdateProfileSettings = () => {
    initProfileFormElements();
    if (profileFormElements.form.length) {
        updateProfileSettings();
    }
};

export { initUpdateProfileSettings };
