import { createAndShowToast } from "../../utils/uiHelpers";
import { ajaxFetcher } from "../fetcher/ajax-fetcher";
import { hideInElement, showInButton, showInElement } from "../loader";
import { landingsConstants } from "./constants";
import { initializeLandingStatus } from "./initialize-landing-status";
import landingStatusPoller from "./landing-status-poller";
import { fetchAndReplaceContent } from "./fetchAndReplaceContnt";

export const addLandingHandler = function (event) {
    event.preventDefault();
    event.stopImmediatePropagation();
    let loaderElement = null;
    const emptyLandings = $(`#${landingsConstants.EMPTY_LANDINGS_ID}`);
    const tableBody = $(`#${landingsConstants.CONTENT_WRAPPER_ID}`);
    if (emptyLandings.length) {
        console.log("emptyLandings", emptyLandings.length);
        loaderElement = showInElement(landingsConstants.EMPTY_LANDINGS_ID);
    } else if (tableBody.length) {
        loaderElement = showInElement(tableBody[0]);
    }

    const $form = $(event.target);
    const $urlInput = $form.find(
        `input[name="${landingsConstants.ADD_LANDING_URL_INPUT_NAME}"]`
    );
    const url = $urlInput.val();
    const csrfToken = $('meta[name="csrf-token"]').attr("content");

    const $submitButton = $(
        `#${landingsConstants.ADD_LANDING_SUBMIT_BUTTON_ID}`
    );
    const originalButtonText = $submitButton.html();
    $submitButton.prop("disabled", true);
    showInButton($submitButton);

    const formData = new FormData();
    formData.append("url", url);
    formData.append("_token", csrfToken);
    const landingsAjaxStore = window.routes.landingsAjaxStore;

    ajaxFetcher.submit(landingsAjaxStore, {
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
                        targetSelector =
                            landingsConstants.CONTENT_WRAPPER_SELECTOR;
                    }

                    const $targetContainer = $(targetSelector);
                    if ($targetContainer.length) {
                        landingStatusPoller.cleanup(); // Stop all current polls before replacing HTML
                        $targetContainer.html(response.data.table_html);

                        // Get the current page size
                        const perPage = parseInt(
                            response.data.per_page || 12,
                            10
                        );

                        // Count items on current page and total items to determine if pagination should change
                        const $tableRows = $(
                            `${landingsConstants.LANDINGS_TABLE_CONTAINER_ID} tbody tr`
                        );
                        const currentItemCount = $tableRows.length;

                        // Check if we've just exceeded the per_page limit for the current page
                        if (currentItemCount > perPage) {
                            console.log(
                                `Item count (${currentItemCount}) exceeds per_page limit (${perPage}). Fetching updated content.`
                            );

                            // Fetch the last page where the new item would be visible
                            const currentUrl = new URL(window.location.href);
                            const queryParams = Object.fromEntries(
                                currentUrl.searchParams.entries()
                            );

                            // Calculate which page the newest item would be on
                            // For most sorting methods (like created_at desc) the newest item would be on the first page
                            // But if we're sorting by another method, we need to determine the correct page
                            const sortField = queryParams.sort || "created_at";
                            const sortDirection =
                                queryParams.direction || "desc";

                            // For created_at desc, new items go to page 1
                            if (
                                sortField === "created_at" &&
                                sortDirection === "desc"
                            ) {
                                queryParams.page = 1;
                            } else {
                                // For other sort methods we'll navigate to the last page
                                // The server's response should contain the last page number
                                if (response.data.last_page) {
                                    queryParams.page = response.data.last_page;
                                } else {
                                    // Fallback: Calculate the last page based on item count
                                    const totalItems =
                                        response.data.total_items ||
                                        currentItemCount + 1;
                                    const lastPage = Math.ceil(
                                        totalItems / perPage
                                    );
                                    queryParams.page = lastPage;
                                }
                            }

                            // Make a request to fetch the proper page with correct item count
                            const ajaxUrl = window.routes.landingsAjaxList;

                            // Use fetchAndReplaceContent to update the content
                            fetchAndReplaceContent(
                                ajaxUrl,
                                queryParams,
                                targetSelector,
                                true // Update browser URL
                            );
                        } else {
                            // Wait for DOM to fully update before re-initializing if we're not refreshing
                            setTimeout(() => {
                                initializeLandingStatus(); // Re-initialize all status tracking
                            }, 100);
                        }
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
                hideInElement(loaderElement);
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
            hideInElement(loaderElement);
            createAndShowToast(errorMessage, "error");
        },
        completeCallback: function () {
            // loader.hide();

            hideInElement(loaderElement);
            $submitButton.prop("disabled", false).html(originalButtonText);
        },
    });
};
