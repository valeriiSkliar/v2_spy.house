import { config } from "../../config";
import { createAndShowToast } from "@/utils";
import { ajaxFetcher } from "../fetcher/ajax-fetcher";
import loader from "../loader";

const cancelPersonalGreetingUpdate = async () => {
    try {
        loader.show();
        const response = await ajaxFetcher.get(
            config.apiProfilePersonalGreetingCancelEndpoint
        );

        if (response.success) {
            // Use the server-provided HTML form
            if (response.initialFormHtml) {
                $("#personal-greeting-form").replaceWith(
                    response.initialFormHtml
                );

                // Reinitialize form handlers
                changePersonalGreeting();
                createAndShowToast(response.message, "success");
            } else {
                // Fallback to reloading the page if we don't get the form HTML
                window.location.reload();
            }
        } else {
            createAndShowToast(
                response.message || "Error cancelling personal greeting update",
                "error"
            );
        }
    } catch (error) {
        console.error("Error cancelling personal greeting update:", error);
        createAndShowToast(
            "Error cancelling personal greeting update. Please try again.",
            "error"
        );
    } finally {
        loader.hide();
    }
};

const confirmPersonalGreetingUpdate = async (formData) => {
    try {
        loader.show();
        const response = await ajaxFetcher.form(
            config.apiProfilePersonalGreetingUpdateConfirmEndpoint,
            formData
        );

        if (response.success) {
            // Show success message
            createAndShowToast(response.message, "success");

            // Replace form with success message or original form
            if (response.successFormHtml) {
                $("#personal-greeting-form").replaceWith(
                    response.successFormHtml
                );

                // Add success message if available
                if (response.successMessage) {
                    $("#personal-greeting-form").prepend(response.successMessage);
                }
            } else if (response.initialFormHtml) {
                $("#personal-greeting-form").replaceWith(
                    response.initialFormHtml
                );
            }
            changePersonalGreeting();
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
        console.error("Error confirming personal greeting update:", error);
        createAndShowToast(
            "Error confirming personal greeting update. Please try again.",
            "error"
        );
    } finally {
        loader.hide();
    }
};

const changePersonalGreeting = () => {
    const form = $("#personal-greeting-form");
    if (form.length) {
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
                await confirmPersonalGreetingUpdate(formData);
            } else {
                // Handle initial personal greeting update request
                try {
                    const response = await ajaxFetcher.form(
                        config.apiProfilePersonalGreetingUpdateInitiateEndpoint,
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
                            changePersonalGreeting();
                            // Add event listener for cancel button
                            $(".btn._border-red._big").on(
                                "click",
                                function (e) {
                                    e.preventDefault();
                                    cancelPersonalGreetingUpdate();
                                }
                            );
                            createAndShowToast(message, "success");
                        }

                        return;
                    } else {
                        createAndShowToast(
                            response.message || "Error updating personal greeting. Please try again.",
                            "error"
                        );
                    }
                } catch (error) {
                    console.error("Error updating personal greeting:", error);
                    createAndShowToast(
                        "Error updating personal greeting. Please try again.",
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
        cancelPersonalGreetingUpdate();
    });
};

const initChangePersonalGreeting = () => {
    changePersonalGreeting();
};

export { changePersonalGreeting, initChangePersonalGreeting };