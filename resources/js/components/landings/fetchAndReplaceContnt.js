import { ajaxFetcher } from "../fetcher/ajax-fetcher";
import { hideInElement, showInElement } from "../loader";
import landingStatusPoller from "./landing-status-poller";

// --- Основная функция загрузки контента ---
export function fetchAndReplaceContent(
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
            landingStatusPoller.cleanup();
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
            createAndShowToast("common.error_occurred_common_message", "error");
        },
        completeCallback: function () {
            if (loaderInstance) {
                hideInElement(loaderInstance);
            }
            // loader.hide();
        },
    });
}
