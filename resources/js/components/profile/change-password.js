import { config } from "../../config";
import { createAndShowToast } from "@/utils";
import { ajaxFetcher } from "../fetcher/ajax-fetcher";
import loader from "../loader";

const changePassword = () => {
    console.log("changePassword");
    const form = $("#change-password-form");
    if (form) {
        form.on("submit", async (e) => {
            loader.show();
            e.preventDefault();
            const formData = new FormData(form[0]);

            try {
                const response = await ajaxFetcher.form(
                    config.apiProfilePasswordUpdateInitiateEndpoint,
                    formData
                );

                if (response.success) {
                    const message = response.message;
                    const confirmationMethod = response.confirmation_method;

                    // Show success message
                    const successMessage = $(
                        '[data-status="password-updated"]'
                    );
                    if (successMessage.length) {
                        successMessage.find(".message").text(message);
                        successMessage.show();
                    }

                    // Update form for confirmation code
                    const form = $("#change-password-form");
                    form.attr(
                        "action",
                        form.attr("action").replace("initiate", "confirm")
                    );

                    // Show confirmation code input
                    if (confirmationMethod === "email") {
                        form.find("[data-confirmation-method]").html(`
                        <div class="form-item mb-20">
                            <label class="d-block mb-10">${__(
                                "profile.security_settings.confirmation_code_label"
                            )}</label>
                            <input type="text" name="confirmation_code" class="input-h-57" required>
                        </div>
                    `);
                    }
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
