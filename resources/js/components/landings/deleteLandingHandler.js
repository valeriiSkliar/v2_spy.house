import { createAndShowToast } from "../../utils/uiHelpers";
import { ajaxFetcher } from "../fetcher/ajax-fetcher";
import loader, { hideInButton, showInButton } from "../loader";
import { landingsConstants } from "./constants";
import { fetchAndReplaceContent } from "./fetchAndReplaceContnt";

export const deleteLandingHandler = function (event) {
    event.preventDefault();
    event.stopImmediatePropagation();

    const $button = $(this);
    const landingId = $button.data("id");
    const landingName = $button.data("name") || "этот лендинг";

    if (!landingId) {
        console.error("Landing ID not found on delete button.");
        createAndShowToast("Ошибка: ID лендинга не найден.", "error");
        return;
    }

    if (!confirm(`Вы уверены, что хотите удалить ${landingName}?`)) {
        return;
    }

    showInButton($button, "_dark");

    if (!window.routes || !window.routes.landingsAjaxDestroyBase) {
        console.error("Route for landingsAjaxDestroyBase is not defined.");
        createAndShowToast(
            "Ошибка конфигурации: URL для удаления не определен.",
            "error"
        );
        hideInButton($button);
        return;
    }
    const deleteUrl = window.routes.landingsAjaxDestroyBase.replace(
        ":id",
        landingId
    );
    ajaxFetcher
        .delete(deleteUrl)
        .done(function (response) {
            if (response.success) {
                $button.closest("tr").fadeOut(300, function () {
                    $(this).remove();
                    if (
                        $(
                            `${landingsConstants.LANDINGS_TABLE_CONTAINER_ID}`
                        ).find("tbody tr").length === 0
                    ) {
                        console.log(
                            "tableContainerSelector",
                            landingsConstants.LANDINGS_TABLE_CONTAINER_ID
                        );
                        // Если таблица пуста после удаления на текущей странице,
                        // проверим номер текущей страницы и перейдем на предыдущую, если мы не на первой странице
                        const $paginationBox = $(
                            `[${landingsConstants.PAGINATION_CONTAINER_ATTR}]`
                        ).first();
                        const ajaxUrl = $paginationBox.data("ajax-url");
                        const targetSelector =
                            $paginationBox.data("target-selector");
                        const filterFormSelector = $paginationBox.data(
                            "filter-form-selector"
                        );

                        let queryParams = {};
                        // Попытаемся получить текущие параметры фильтрации и номер страницы
                        const currentUrl = new URL(window.location.href);
                        queryParams = Object.fromEntries(
                            currentUrl.searchParams.entries()
                        );

                        if (filterFormSelector) {
                            console.log(
                                "filterFormSelector",
                                filterFormSelector
                            );
                            const $filterForm = $(filterFormSelector);
                            if ($filterForm.length) {
                                const formValues = $filterForm.serializeArray();
                                formValues.forEach(function (field) {
                                    if (field.name !== "page") {
                                        // Не перезаписываем страницу из формы, если она уже есть из URL
                                        queryParams[field.name] = field.value;
                                    }
                                });
                            }
                        }
                        
                        // Check if we're on page > 1 and adjust accordingly
                        const currentPage = parseInt(queryParams.page, 10);
                        if (currentPage && currentPage > 1) {
                            // Navigate to the previous page
                            queryParams.page = currentPage - 1;
                            console.log("Last item on page removed, navigating to previous page:", queryParams.page);
                        } else {
                            // We're on page 1 or page parameter isn't set, stay on page 1
                            queryParams.page = 1;
                        }

                        if (ajaxUrl && targetSelector) {
                            // This will call initializeLandingStatus() after content is replaced
                            fetchAndReplaceContent(
                                ajaxUrl,
                                queryParams,
                                targetSelector,
                                true // true - обновлять историю браузера, чтобы URL отражал текущую страницу
                            );
                        }
                    }
                });
                createAndShowToast(
                    response.message || "Лендинг успешно удален.",
                    "success"
                );
            } else {
                createAndShowToast(
                    response.message || "Не удалось удалить лендинг.",
                    "error"
                );
            }
        })
        .fail(function (jqXHR, textStatus, errorThrown) {
            console.error(
                "Error deleting landing: ",
                textStatus,
                errorThrown,
                jqXHR.responseText
            );
            let errorMessage = "Произошла ошибка при удалении лендинга.";
            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                errorMessage = jqXHR.responseJSON.message;
            }
            createAndShowToast(errorMessage, "error");
        })
        .always(function () {
            // Just hide the button loader - content update is already handled in the success callback
            // when we check if the table is empty
            hideInButton($button);
        });
};
