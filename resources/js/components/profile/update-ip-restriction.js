import { config } from "../../config";
import { createAndShowToast } from "@/utils";
import { ajaxFetcher } from "../fetcher/ajax-fetcher";
import loader from "../loader";

/**
 * Handles the IP restriction form submission
 */
const updateIpRestriction = () => {
    if (typeof $ === "undefined") {
        console.error("jQuery is not loaded");
        return;
    }

    const form = $("#ip-restriction-form");
    if (!form.length) {
        console.error("IP restriction form not found");
        return;
    }

    // Ensure textareas auto-resize
    const adjustHeight = (element) => {
        element.style.height = "auto";
        element.style.height = element.scrollHeight + "px";
    };

    const textareas = document.querySelectorAll(".auto-resize");
    textareas.forEach((textarea) => {
        adjustHeight(textarea);
        textarea.addEventListener("input", function () {
            adjustHeight(this);
        });
    });

    // Handle form submission
    form.on("submit", async function (e) {
        e.preventDefault();
        loader.show();

        const formData = new FormData(this);

        try {
            const response = await ajaxFetcher.form(
                config.apiProfileIpRestrictionUpdateEndpoint,
                formData
            );

            if (response.success) {
                createAndShowToast(response.message, "success");

                if (response.successFormHtml) {
                    $("#ip-restriction-form").replaceWith(
                        response.successFormHtml
                    );
                    updateIpRestriction();
                }

                $('input[name="password"]').val("");
            } else {
                if (response.errors) {
                    Object.keys(response.errors).forEach((field) => {
                        const errorMessage = response.errors[field].join(", ");
                        $(`[name="${field}"]`).addClass("is-invalid");
                        $(`[name="${field}"]`).after(
                            `<span class="text-danger">${errorMessage}</span>`
                        );
                    });
                }

                createAndShowToast(
                    response.message || "Error updating IP restrictions",
                    "error"
                );
            }
        } catch (error) {
            console.error("Error updating IP restrictions:", error);
            createAndShowToast(
                "Error updating IP restrictions. Please try again.",
                "error"
            );
        } finally {
            loader.hide();
        }
    });
};

const initUpdateIpRestriction = () => {
    updateIpRestriction();
};

export { updateIpRestriction, initUpdateIpRestriction };
