// import { initializeSelectComponent } from "@/helpers";
// import {
//     initializeLandingStatus,
//     initializeDynamicLandingStatus,
// } from "@/components";
import $ from "jquery";
import { updateBrowserUrl } from "../helpers/update-browser-url";
import loader, { hideInElement, showInElement } from "../components/loader";
import { ajaxFetcher } from "../components/fetcher/ajax-fetcher";
import { createAndShowToast } from "../utils/uiHelpers";
import { landingsConstants } from "../components/landings/constants";

function initDeleteLandingHandler() {
    const tableContainerSelector =
        landingsConstants.LANDINGS_TABLE_CONTAINER_ID;
    const deleteButtonSelector = ".delete-landing-button";

    $(document).on(
        "click",
        `${tableContainerSelector} ${deleteButtonSelector}`,
        function (event) {
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

            loader.show();

            if (!window.routes || !window.routes.landingsAjaxDestroyBase) {
                console.error(
                    "Route for landingsAjaxDestroyBase is not defined."
                );
                createAndShowToast(
                    "Ошибка конфигурации: URL для удаления не определен.",
                    "error"
                );
                loader.hide();
                return;
            }
            const deleteUrl = window.routes.landingsAjaxDestroyBase.replace(
                ":id",
                landingId
            );
            console.log(deleteUrl);
            ajaxFetcher
                .delete(deleteUrl)
                .done(function (response) {
                    if (response.success) {
                        $button.closest("tr").fadeOut(300, function () {
                            $(this).remove();
                            if (
                                $(tableContainerSelector).find("tbody tr")
                                    .length === 0
                            ) {
                                // Если таблица пуста после удаления на текущей странице,
                                // можно попробовать перезагрузить данные для текущей страницы пагинации.
                                // Это поможет корректно отобразить сообщение "Нет данных" или перейти на предыдущую страницу, если это реализовано в fetchAndReplaceContent.
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
                                const currentUrl = new URL(
                                    window.location.href
                                );
                                queryParams = Object.fromEntries(
                                    currentUrl.searchParams.entries()
                                );

                                if (filterFormSelector) {
                                    const $filterForm = $(filterFormSelector);
                                    if ($filterForm.length) {
                                        const formValues =
                                            $filterForm.serializeArray();
                                        formValues.forEach(function (field) {
                                            if (field.name !== "page") {
                                                // Не перезаписываем страницу из формы, если она уже есть из URL
                                                queryParams[field.name] =
                                                    field.value;
                                            }
                                        });
                                    }
                                }
                                // Если queryParams.page не установлен, установим 1 (или оставим как есть, если сервер сам обрабатывает)
                                // queryParams.page = queryParams.page || 1;

                                if (ajaxUrl && targetSelector) {
                                    fetchAndReplaceContent(
                                        ajaxUrl,
                                        queryParams,
                                        targetSelector,
                                        false
                                    ); // false - не обновлять историю браузера для этого случая
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
                    let errorMessage =
                        "Произошла ошибка при удалении лендинга.";
                    if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                        errorMessage = jqXHR.responseJSON.message;
                    }
                    createAndShowToast(errorMessage, "error");
                })
                .always(function () {
                    loader.hide();
                });
        }
    );
}

$(document).ready(function () {
    // --- Вспомогательные функции ---

    // --- Основная функция загрузки контента ---
    function fetchAndReplaceContent(
        ajaxUrl,
        queryParams,
        targetSelector,
        updateHistory = true
    ) {
        const targetElement = document.querySelector(targetSelector);
        if (!targetElement) {
            console.error(`Element not found for selector: ${targetSelector}`);
            return;
        }

        const loaderInstance = showInElement(targetElement);

        // Очистка пустых параметров
        const finalParams = {};
        for (const key in queryParams) {
            if (
                queryParams[key] !== null &&
                queryParams[key] !== undefined &&
                queryParams[key] !== ""
            ) {
                finalParams[key] = queryParams[key];
            }
        }

        ajaxFetcher.get(ajaxUrl, finalParams, {
            successCallback: function (response) {
                const data = response.data;
                $(targetSelector).html(data.table_html);
                if (updateHistory) {
                    updateBrowserUrl(finalParams);
                }
            },
            errorCallback: function (jqXHR, textStatus, errorThrown) {
                console.error(
                    "Error fetching content: ",
                    textStatus,
                    errorThrown,
                    jqXHR.responseText
                );
                if (loaderInstance) {
                    hideInElement(loaderInstance);
                }
                // Уведомить пользователя об ошибке
                createAndShowToast(
                    "common.error_occurred_common_message",
                    "error"
                );
            },
            completeCallback: function () {
                if (loaderInstance) {
                    hideInElement(loaderInstance);
                }
                // loader.hide();
            },
        });
    }

    // --- Инициализация универсального обработчика пагинации ---
    function initAsyncPaginationHandler() {
        // Делегированный обработчик для всех контейнеров пагинации
        $(document).on(
            "click",
            `[${landingsConstants.PAGINATION_CONTAINER_ATTR}] ${landingsConstants.PAGINATION_LINK_SELECTOR}`,
            function (event) {
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
                const filterFormSelector = $paginationBox.data(
                    "filter-form-selector"
                );

                if (!targetSelector || !ajaxUrl) {
                    console.error(
                        "Pagination data attributes (target-selector or ajax-url) are missing on the pagination container!"
                    );
                    return;
                }

                const clickedHref = $link.attr("href");
                if (!clickedHref) {
                    console.warn(
                        "Clicked pagination link has no href attribute."
                    );
                    return;
                }

                // 1. Получаем параметры из URL кликнутой ссылки
                const linkUrl = new URL(clickedHref, window.location.origin);
                let queryParams = Object.fromEntries(
                    linkUrl.searchParams.entries()
                );

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
            }
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
                const targetSelector = `#${CONTENT_WRAPPER_ID}`;
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

        $form.on("submit", function (event) {
            event.preventDefault();
            console.log("submit");
            loader.show();

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
                    if (
                        response.success &&
                        response.data &&
                        response.data.landing_html
                    ) {
                        createAndShowToast(
                            response.message ||
                                "Лендинг успешно добавлен в очередь.",
                            "success"
                        );
                        $urlInput.val(""); // Очистить поле ввода

                        // Добавляем новую строку в таблицу
                        const $tableBody = $(
                            `#${landingsConstants.LANDINGS_TABLE_BODY_ID}`
                        );
                        if ($tableBody.length) {
                            $tableBody.prepend(response.data.landing_html);
                        } else {
                            // Если tbody не найден, возможно, стоит перезагрузить всю таблицу
                            // Это менее оптимально, но будет работать как fallback
                            const $paginationBox = $(
                                `[${landingsConstants.PAGINATION_CONTAINER_ATTR}]`
                            ).first();
                            if ($paginationBox.length) {
                                const targetSelector =
                                    $paginationBox.data("target-selector");
                                const ajaxUrl = $paginationBox.data("ajax-url");
                                const currentUrlParams = Object.fromEntries(
                                    new URLSearchParams(window.location.search)
                                );
                                fetchAndReplaceContent(
                                    ajaxUrl,
                                    currentUrlParams,
                                    targetSelector,
                                    false
                                );
                            }
                        }
                    } else {
                        // Обработка специфических ошибок валидации от сервера
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
                                response.message ||
                                    "Не удалось добавить лендинг.",
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
                    let errorMessage =
                        "Произошла ошибка при добавлении лендинга.";
                    if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                        errorMessage = jqXHR.responseJSON.message;
                        if (jqXHR.responseJSON.errors) {
                            for (const field in jqXHR.responseJSON.errors) {
                                errorMessage +=
                                    "\n" +
                                    jqXHR.responseJSON.errors[field].join("\n");
                            }
                        }
                    }
                    loader.hide();
                    createAndShowToast(errorMessage, "error");
                },
                completeCallback: function () {
                    loader.hide();
                    $submitButton
                        .prop("disabled", false)
                        .html(originalButtonText);
                },
            });
        });
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

    // Первоначальная установка состояния из URL для popstate
    // ... existing code ...
});
