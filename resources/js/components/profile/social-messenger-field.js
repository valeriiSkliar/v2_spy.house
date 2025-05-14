/**
 * Social messenger field component for profile settings
 * Handles the selection and validation of different messenger types (Telegram, Viber, WhatsApp)
 */

import { profileFormElements } from "./profile-form-elements";

const initSocialMessengerField = () => {
    // Object with placeholders for each messenger type
    const placeholders = {
        telegram: "@username",
        viber_phone: "+1 (999) 999-99-99",
        whatsapp_phone: "+1 (999) 999-99-99",
    };

    // Function to validate messenger values
    function validateMessengerValue(type, value) {
        if (!value) return true;

        switch (type) {
            case "telegram":
                return /^@[A-Za-z0-9_]{5,32}$/.test(value);
            case "viber_phone":
            case "whatsapp_phone":
                const cleanValue = value.replace(/[^0-9+]/g, "");
                return /^\+?[0-9]{10,15}$/.test(cleanValue);
            default:
                return false;
        }
    }

    // Function to update hidden field values
    function updateHiddenField() {
        const value = profileFormElements.visibleValue.val();
        const type = profileFormElements.visibleValue.data("type");

        // Clear all hidden fields
        profileFormElements.telegram.val("");
        profileFormElements.viberPhone.val("");
        profileFormElements.whatsappPhone.val("");

        // Update the value of the corresponding hidden field
        if (type === "telegram") {
            profileFormElements.telegram.val(value);
        } else if (type === "viber_phone") {
            profileFormElements.viberPhone.val(value);
        } else if (type === "whatsapp_phone") {
            profileFormElements.whatsappPhone.val(value);
        }
    }

    // Handler for messenger selection in dropdown
    profileFormElements.profileMessangerSelectOptions
        .off("click")
        .on("click", function () {
            const selectedValue = $(this).data("value");
            const selectedPhone = $(this).data("phone");

            // Update value in visible input field
            profileFormElements.visibleValue.val(selectedPhone);

            // Update data-type attribute on visible input
            profileFormElements.visibleValue.data("type", selectedValue);
            profileFormElements.visibleValue.attr("data-type", selectedValue);

            // Update placeholder based on selected messenger
            profileFormElements.visibleValue.attr(
                "placeholder",
                placeholders[selectedValue]
            );

            // Update selected class in dropdown
            profileFormElements.profileMessangerSelectOptions.removeClass(
                "is-selected"
            );
            $(this).addClass("is-selected");

            // Update image in trigger
            const imgSrc = $(this).find("img").attr("src");
            const $trigger = profileFormElements.profileMessangerSelectTrigger;

            // Update trigger structure
            $trigger.html(`
            <span class="base-select__value">
                <span class="base-select__img">
                    <img src="${imgSrc}" alt="${selectedValue}">
                </span>
            </span>
            <span class="base-select__arrow"></span>
        `);

            // Close dropdown
            profileFormElements.profileMessangerSelectDropdown.hide();

            // Update hidden field value
            updateHiddenField();
        });

    // Handler for input in visible field
    profileFormElements.visibleValue.off("input").on("input", function () {
        const type = $(this).data("type");
        const value = $(this).val();

        if (!validateMessengerValue(type, value)) {
            $(this).addClass("is-invalid");
        } else {
            $(this).removeClass("is-invalid");
        }

        updateHiddenField();
    });

    // Toggle dropdown on trigger click
    profileFormElements.profileMessangerSelectTrigger
        .off("click")
        .on("click", function () {
            profileFormElements.profileMessangerSelectDropdown.toggle();
        });

    // Close dropdown when clicking outside
    $(document)
        .off("click.messengerDropdown")
        .on("click.messengerDropdown", function (e) {
            if (
                !$(e.target).closest(profileFormElements.profileMessangerSelect)
                    .length
            ) {
                profileFormElements.profileMessangerSelectDropdown.hide();
            }
        });

    // Initialize on page load
    updateHiddenField();
};

export { initSocialMessengerField };
