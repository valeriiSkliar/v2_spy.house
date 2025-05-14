import { config } from "../../config";
import { createAndShowToast } from "@/utils";
import { ajaxFetcher } from "../fetcher/ajax-fetcher";
import loader from "../loader";

const cancelPasswordUpdate = async () => {
    try {
        loader.show();
        const response = await ajaxFetcher.get(
            config.apiProfilePasswordCancelEndpoint
        );

        if (response.success) {
            // Use the server-provided HTML form
            if (response.initialFormHtml) {
                $("#change-password-form").replaceWith(
                    response.initialFormHtml
                );

                // Reinitialize form handlers
                changePassword();
                createAndShowToast(response.message, "success");
            } else {
                // Fallback to reloading the page if we don't get the form HTML
                window.location.reload();
            }
        } else {
            createAndShowToast(
                response.message || "Error cancelling password update",
                "error"
            );
        }
    } catch (error) {
        console.error("Error cancelling password update:", error);
        createAndShowToast(
            "Error cancelling password update. Please try again.",
            "error"
        );
    } finally {
        loader.hide();
    }
};

const confirmPasswordUpdate = async (formData) => {
    try {
        loader.show();
        const response = await ajaxFetcher.form(
            config.apiProfilePasswordUpdateConfirmEndpoint,
            formData
        );

        if (response.success) {
            // Show success message
            createAndShowToast(response.message, "success");

            // Replace form with success message or original form
            if (response.successFormHtml) {
                $("#change-password-form").replaceWith(
                    response.successFormHtml
                );

                // Add success message if available
                if (response.successMessage) {
                    $("#change-password-form").prepend(response.successMessage);
                }
            } else if (response.initialFormHtml) {
                $("#change-password-form").replaceWith(
                    response.initialFormHtml
                );
            }
            changePassword();
        } else {
            // Show error message for invalid code
            createAndShowToast(
                response.message || "Invalid confirmation code",
                "error"
            );

            // Optionally highlight the code input field
            $('input[name="verification_code"]').addClass("is-invalid").focus();
        }
    } catch (error) {
        console.error("Error confirming password update:", error);
        createAndShowToast(
            "Error confirming password update. Please try again.",
            "error"
        );
    } finally {
        loader.hide();
    }
};

const changePassword = () => {
    console.log("changePassword");
    const form = $("#change-password-form");
    if (form) {
        form.on("submit", async function (e) {
            loader.show();
            e.preventDefault();
            const formData = new FormData(this);

            // Determine if this is a confirmation form or initial form
            const isConfirmationForm =
                $(this).find('input[name="verification_code"]').length > 0 ||
                $(this).attr("action").includes("confirm");

            if (isConfirmationForm) {
                // Handle confirmation submission
                await confirmPasswordUpdate(formData);
            } else {
                // Handle initial password update request
                try {
                    const response = await ajaxFetcher.form(
                        config.apiProfilePasswordUpdateInitiateEndpoint,
                        formData
                    );

                    if (response.success) {
                        const message = response.message;
                        const confirmationMethod = response.confirmation_method;
                        const confirmationFormHtml =
                            response.confirmation_form_html;

                        // Replace form with confirmation form
                        if (confirmationFormHtml) {
                            $(this).replaceWith(confirmationFormHtml);
                            // Reinitialize form handlers
                            changePassword();
                            // Add event listener for cancel button
                            $(".btn._border-red._big").on(
                                "click",
                                function (e) {
                                    e.preventDefault();
                                    cancelPasswordUpdate();
                                }
                            );
                            createAndShowToast(message, "success");
                        }

                        return;
                    }
                } catch (error) {
                    console.error("Error updating password:", error);
                    loader.hide();
                    createAndShowToast(
                        "Error updating password. Please try again.",
                        "error"
                    );
                } finally {
                    loader.hide();
                }
            }
        });
    }

    // Add event listener for cancel button if it exists
    $(".btn._border-red._big").on("click", function (e) {
        e.preventDefault();
        cancelPasswordUpdate();
    });
};

const initChangePassword = () => {
    changePassword();
};

export { changePassword, initChangePassword };
