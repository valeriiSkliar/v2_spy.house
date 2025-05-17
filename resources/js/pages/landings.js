// import { initializeSelectComponent } from "@/helpers";
import {
    initializeLandingStatus,
    initializeDynamicLandingStatus,
} from "@/components";
import $ from "jquery";
import { updateBrowserUrl } from "../helpers/update-browser-url";
import loader, { hideInElement, showInElement } from "../components/loader";
import { ajaxFetcher } from "../components/fetcher/ajax-fetcher";
import { createAndShowToast } from "../utils/uiHelpers";
import { landingsConstants } from "../components/landings/constants";
import {
    asyncPaginationHandler,
    deleteLandingHandler,
    fetchAndReplaceContent,
    addLandingHandler,
} from "../components/landings";

export function initDeleteLandingHandler() {
    const tableContainerSelector =
        landingsConstants.LANDINGS_TABLE_CONTAINER_ID;
    const deleteButtonSelector = ".delete-landing-button";

    $(document)
        .off("click", `${tableContainerSelector} ${deleteButtonSelector}`)
        .on(
            "click",
            `${tableContainerSelector} ${deleteButtonSelector}`,
            deleteLandingHandler
        );
}

$(document).ready(function () {
    // --- Вспомогательные функции ---

    // --- Инициализация универсального обработчика пагинации ---
    function initAsyncPaginationHandler() {
        // Делегированный обработчик для всех контейнеров пагинации
        $(document).on(
            "click",
            `[${landingsConstants.PAGINATION_CONTAINER_ATTR}] ${landingsConstants.PAGINATION_LINK_SELECTOR}`,
            asyncPaginationHandler
        );
    }

    // --- Инициализация обработчиков сортировки (адаптируем для универсальности) ---
    function initSortAndFilterHandlers() {
        // Предполагаем, что форма сортировки одна для текущей страницы лендингов
        const $sortForm = $("#landings-sort-form"); // Селектор формы сортировки
        if (!$sortForm.length) return;

        $sortForm.on("change", "select", function () {
            // Найти связанный контейнер пагинации, чтобы взять его data-атрибуты
            const $paginationBox = $(
                `[data-filter-form-selector="#${$sortForm.attr("id")}"]`
            ).first();
            if (!$paginationBox.length) {
                // Если нет настроенной пагинации, просто сабмитим форму (старое поведение)
                // $sortForm.submit();
                console.warn(
                    "No associated pagination container found for sort/filter form. AJAX call will not be made via pagination handler."
                );
                // Можно сделать прямой AJAX запрос, если нужно, но лучше полагаться на data-атрибуты пагинации.
                // Для простоты, если нет `data-pagination-container` с таким `data-filter-form-selector`
                // AJAX-обработка этого изменения не произойдет через этот механизм.
                // Вместо этого, просто вызовем fetchAndReplaceContent напрямую, если знаем URL и target
                const ajaxUrl = window.routes.landingsAjaxList; // Должен быть доступен
                const targetSelector = `#${landingsConstants.CONTENT_WRAPPER_ID}`;
                if (ajaxUrl && $(targetSelector).length) {
                    let queryParams = Object.fromEntries(
                        new URLSearchParams($sortForm.serialize()).entries()
                    );
                    queryParams.page = 1; // Сброс на первую страницу
                    fetchAndReplaceContent(
                        ajaxUrl,
                        queryParams,
                        targetSelector
                    );
                } else {
                    $sortForm.submit(); // Fallback
                }
                return;
            }

            const targetSelector = $paginationBox.data("target-selector");
            const ajaxUrl = $paginationBox.data("ajax-url");

            let queryParams = Object.fromEntries(
                new URLSearchParams($sortForm.serialize()).entries()
            );
            queryParams.page = 1; // Сброс на первую страницу

            fetchAndReplaceContent(ajaxUrl, queryParams, targetSelector);
        });
    }

    // --- Обработка истории браузера ---
    $(window).on("popstate", function (event) {
        const state = event.originalEvent.state;
        if (state) {
            // Ищем первый универсальный контейнер пагинации и используем его конфигурацию
            const $paginationBox = $(
                `[${landingsConstants.PAGINATION_CONTAINER_ATTR}]`
            ).first();
            if ($paginationBox.length) {
                const targetSelector = $paginationBox.data("target-selector");
                const ajaxUrl = $paginationBox.data("ajax-url");
                if (targetSelector && ajaxUrl) {
                    fetchAndReplaceContent(
                        ajaxUrl,
                        state,
                        targetSelector,
                        false
                    );
                }
            }
        } else {
            // Начальная загрузка страницы или ручное изменение URL, не через pushState
            // Можно попытаться восстановить состояние из URL, если необходимо
            const currentUrlParams = Object.fromEntries(
                new URLSearchParams(window.location.search)
            );
            if (Object.keys(currentUrlParams).length > 0) {
                const $paginationBox = $(
                    `[${landingsConstants.PAGINATION_CONTAINER_ATTR}]`
                ).first();
                if ($paginationBox.length) {
                    const targetSelector =
                        $paginationBox.data("target-selector");
                    const ajaxUrl = $paginationBox.data("ajax-url");
                    if (targetSelector && ajaxUrl) {
                        fetchAndReplaceContent(
                            ajaxUrl,
                            currentUrlParams,
                            targetSelector,
                            false
                        );
                    }
                }
            }
        }
    });

    // --- Инициализация Select2 для глобальных селектов (вне обновляемого контента) ---
    function initializeGlobalSelects() {
        if ($.fn.select2) {
            $("#sort-by, #sort-direction, #items-per-page").each(function () {
                $(this).select2({
                    theme: "bootstrap-5",
                    width: $(this).data("width")
                        ? $(this).data("width")
                        : $(this).hasClass("w-100")
                        ? "100%"
                        : "style",
                    placeholder: $(this).data("placeholder") || "Выберите...",
                    minimumResultsForSearch:
                        $(this).data("search") === "true" ? 0 : Infinity,
                });
            });
        }
    }

    // --- Обработчик добавления нового лендинга ---
    function initAddLandingHandler() {
        const $form = $(`#${landingsConstants.ADD_LANDING_FORM_ID}`);
        if (!$form.length) {
            console.warn(
                `Form with ID #${landingsConstants.ADD_LANDING_FORM_ID} not found.`
            );
            return;
        }

        $form.on("submit", addLandingHandler);
    }

    // --- Передача маршрутов из Blade (обязательно!) ---
    // Убедитесь, что объект window.routes определен в Blade перед этим скриптом, например:
    // <script>
    //  window.routes = {
    //      landingsAjaxList: '{{ route("landings.ajax.list") }}',
    //      landingsAjaxStore: '{{ route("landings.ajax.store") }}',
    //      landingsAjaxDestroyBase: '{{ route("landings.ajax.destroy", ["landing" => "_LANDING_ID_"]) }}'
    //  };
    //  window.translations = { // Для JS локализации, если нужно
    //      commonError: "{{ __('common.error_occurred_common_message') }}"
    //  }
    // </script>
    if (!window.routes || !window.routes.landingsAjaxList) {
        console.error(
            "JS route `landingsAjaxList` is not defined in window.routes."
        );
    }
    // if (!window.translations || !window.translations.commonError) {
    //     console.warn('JS translations are not defined in window.translations.');
    // }
    // window.__ = key => window.translations[key] || key; // Простая функция для локализации в JS

    // --- Запуск инициализаций ---
    initSortAndFilterHandlers();
    initAddLandingHandler();
    initDeleteLandingHandler();
    initializeGlobalSelects();
    initAsyncPaginationHandler();
    initializeLandingStatus();
    initializeDynamicLandingStatus();

    // Первоначальная установка состояния из URL для popstate
    // ... existing code ...
});
