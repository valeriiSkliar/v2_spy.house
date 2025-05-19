import { createAndShowToast } from "../../utils/uiHelpers";
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
                if (response.user.messenger_type !== undefined && response.user.messenger_contact !== undefined) {
                    profileFormElements.messengerType.val(response.user.messenger_type);
                    profileFormElements.messengerContact.val(response.user.messenger_contact);
                    
                    // Update selected messenger in dropdown
                    profileFormElements.profileMessangerSelectOptions.removeClass('is-selected');
                    const selectedOption = profileFormElements.profileMessangerSelectOptions
                        .filter(`[data-value="${response.user.messenger_type}"]`);
                    
                    if (selectedOption.length) {
                        selectedOption.addClass('is-selected');
                        
                        // Update trigger image
                        const imgSrc = selectedOption.find("img").attr("src");
                        profileFormElements.profileMessangerSelectTrigger.html(`
                            <span class="base-select__value">
                                <span class="base-select__img">
                                    <img src="${imgSrc}" alt="${response.user.messenger_type}">
                                </span>
                            </span>
                            <span class="base-select__arrow"></span>
                        `);
                    }
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
