import { createAndShowToast } from "../../utils/uiHelpers";
import { ajaxFetcher } from "../fetcher/ajax-fetcher";
import { hideInElement, showInElement } from "../loader";
import { landingsConstants } from "./constants";
import { initializeLandingStatus } from "./initialize-landing-status";

export const addLandingHandler = function (event) {
    event.preventDefault();
    event.stopImmediatePropagation();
    const emptyLandings = $(`#${landingsConstants.EMPTY_LANDINGS_ID}`);
    if (emptyLandings.length) {
        console.log("emptyLandings", emptyLandings.length);
        showInElement(landingsConstants.EMPTY_LANDINGS_ID);
    }

    const $form = $(event.target);
    const $urlInput = $form.find(
        `input[name="${landingsConstants.ADD_LANDING_URL_INPUT_NAME}"]`
    );
    const url = $urlInput.val();
    const csrfToken = $('meta[name="csrf-token"]').attr("content");

    const $submitButton = $form.find('button[type="submit"]');
    const originalButtonText = $submitButton.html();
    $submitButton
        .prop("disabled", true)
        .html('Добавление... <i class="fas fa-spinner fa-spin"></i>');

    const formData = new FormData();
    formData.append("url", url);
    formData.append("_token", csrfToken);

    ajaxFetcher.submit(window.routes.landingsAjaxStore, {
        data: formData,
        successCallback: function (response) {
            if (response.success && response.data) {
                createAndShowToast(
                    response.message || "Лендинг успешно добавлен в очередь.",
                    "success"
                );
                $urlInput.val(""); // Очистить поле ввода

                if (response.data.table_html) {
                    // Full table HTML (content wrapper) is provided
                    // Determine targetSelector (same as used by pagination/sorting updates)
                    let targetSelector;
                    const $paginationBox = $(
                        `[${landingsConstants.PAGINATION_CONTAINER_ATTR}]`
                    ).first();
                    if (
                        $paginationBox.length &&
                        $paginationBox.data("target-selector")
                    ) {
                        targetSelector = $paginationBox.data("target-selector");
                    } else {
                        // Fallback: If this is the very first load and no pagination box exists yet,
                        // use a known ID for the content area.
                        // This should be the ID of the element that `renderContentWrapperView`'s output replaces.
                        // We assume 'landingsConstants.CONTENT_WRAPPER_SELECTOR' or a default like '#landings-list-container'
                        // If 'landings' is not available here, we might need to define it or use a string literal.
                        // For now, let's assume 'landings-list-container' is a reasonable default if not in constants.
                        targetSelector =
                            landingsConstants.CONTENT_WRAPPER_SELECTOR;
                    }

                    const $targetContainer = $(targetSelector);
                    if ($targetContainer.length) {
                        $targetContainer.html(response.data.table_html);
                        // TODO: Re-initialize any dynamic JS components within the new HTML if needed (e.g., tooltips, dropdowns)
                        initializeLandingStatus();
                    } else {
                        console.error(
                            `Target container '${targetSelector}' for full table update not found. Reloading page as a fallback.`
                        );
                        window.location.reload();
                    }
                } else if (response.data.landing_html) {
                    // Only a new row HTML is provided
                    const $tableBody = $(
                        `#${landingsConstants.LANDINGS_TABLE_BODY_ID}`
                    );
                    if ($tableBody.length) {
                        $tableBody.prepend(response.data.landing_html);
                        const $newRow = $tableBody.find("tr:first-child");
                        if ($newRow.length) {
                            $(document).trigger("landings:new", [$newRow]);
                        }
                    } else {
                        // Table body not found. Use existing fallback to reload content.
                        console.warn(
                            "Table body not found for prepending row. Attempting existing fallback to reload content."
                        );
                        const $fallbackPaginationBox = $(
                            `[${landingsConstants.PAGINATION_CONTAINER_ATTR}]`
                        ).first();
                        if (
                            $fallbackPaginationBox.length &&
                            $fallbackPaginationBox.data("target-selector") &&
                            $fallbackPaginationBox.data("ajax-url")
                        ) {
                            const fallbackTargetSelector =
                                $fallbackPaginationBox.data("target-selector");
                            const ajaxUrl =
                                $fallbackPaginationBox.data("ajax-url");
                            const currentUrlParams = Object.fromEntries(
                                new URLSearchParams(window.location.search)
                            );
                            fetchAndReplaceContent(
                                ajaxUrl,
                                currentUrlParams,
                                fallbackTargetSelector,
                                false
                            );
                        } else {
                            console.error(
                                "Fallback mechanism (pagination box or its data) not found for content refresh. Reloading page."
                            );
                            window.location.reload();
                        }
                    }
                }
            } else {
                // Handle non-success or missing data (existing error handling)
                if (response.errors) {
                    let errorMessages = "";
                    for (const field in response.errors) {
                        errorMessages +=
                            response.errors[field].join("\n") + "\n";
                    }
                    createAndShowToast(errorMessages.trim(), "error", {
                        title: response.message || "Ошибка валидации",
                    });
                } else {
                    createAndShowToast(
                        response.message || "Не удалось добавить лендинг.",
                        "error"
                    );
                }
            }
        },
        errorCallback: function (jqXHR, textStatus, errorThrown) {
            console.error(
                "Error adding landing:",
                textStatus,
                errorThrown,
                jqXHR.responseText
            );
            let errorMessage = "Произошла ошибка при добавлении лендинга.";
            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                errorMessage = jqXHR.responseJSON.message;
                if (jqXHR.responseJSON.errors) {
                    for (const field in jqXHR.responseJSON.errors) {
                        errorMessage +=
                            "\n" + jqXHR.responseJSON.errors[field].join("\n");
                    }
                }
            }
            // loader.hide();
            hideInElement(landingsConstants.EMPTY_LANDINGS_ID);
            createAndShowToast(errorMessage, "error");
        },
        completeCallback: function () {
            // loader.hide();

            // hideInElement(landingsConstants.EMPTY_LANDINGS_ID);
            $submitButton.prop("disabled", false).html(originalButtonText);
        },
    });
};
