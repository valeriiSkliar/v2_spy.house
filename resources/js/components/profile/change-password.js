import { config } from "../../config";
import { createAndShowToast } from "@/utils";
import { ajaxFetcher } from "../fetcher/ajax-fetcher";
import loader from "../loader";

const canselPasswordUpdate = () => {
    console.log("canselPasswordUpdate");
};

const changePassword = () => {
    console.log("changePassword");
    const form = $("#change-password-form");
    if (form) {
        form.on("submit", async function (e) {
            loader.show();
            e.preventDefault();
            const formData = new FormData(this);

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
        });
    }
};

const initChangePassword = () => {
    changePassword();
};

export { changePassword, initChangePassword };
