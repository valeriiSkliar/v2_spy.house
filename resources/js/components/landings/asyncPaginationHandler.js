import { landingsConstants } from "./constants";
import { fetchAndReplaceContent } from "./fetchAndReplaceContnt";

/**
 * Handler for pagination links. Fetches content via AJAX instead of full page load.
 * The fetched content will automatically re-initialize landing status tracking.
 */
export const asyncPaginationHandler = function (event) {
    const $link = $(this);

    // Предотвращаем действие, если ссылка отключена (disabled) или является активной (current page, href="#")
    // Активные ссылки в вашем шаблоне имеют href="#"
    if (
        $link.hasClass("disabled") ||
        $link.attr("aria-disabled") === "true" ||
        $link.hasClass("active") ||
        $link.attr("href") === "#"
    ) {
        event.preventDefault(); // Предотвращаем переход, но не делаем AJAX запрос
        return;
    }
    event.preventDefault(); // Отменяем стандартный переход для всех других активных ссылок

    const $paginationBox = $link.closest(
        `[${landingsConstants.PAGINATION_CONTAINER_ATTR}]`
    );

    const targetSelector = $paginationBox.data("target-selector");
    const ajaxUrl = $paginationBox.data("ajax-url");
    const filterFormSelector = $paginationBox.data("filter-form-selector");

    if (!targetSelector || !ajaxUrl) {
        console.error(
            "Pagination data attributes (target-selector or ajax-url) are missing on the pagination container!"
        );
        return;
    }

    const clickedHref = $link.attr("href");
    if (!clickedHref) {
        console.warn("Clicked pagination link has no href attribute.");
        return;
    }

    // 1. Получаем параметры из URL кликнутой ссылки
    const linkUrl = new URL(clickedHref, window.location.origin);
    let queryParams = Object.fromEntries(linkUrl.searchParams.entries());

    // 2. Добавляем/перезаписываем параметры из формы фильтров/сортировки
    if (filterFormSelector) {
        const $filterForm = $(filterFormSelector);
        if ($filterForm.length) {
            const formValues = $filterForm.serializeArray();
            formValues.forEach(function (field) {
                // Значение из формы будет приоритетнее, кроме 'page'.
                if (field.name !== "page") {
                    queryParams[field.name] = field.value;
                }
            });
        }
    }

    // Вызываем основную функцию для загрузки и замены контента
    fetchAndReplaceContent(ajaxUrl, queryParams, targetSelector);
};
