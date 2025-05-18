import { ajaxFetcher } from "../fetcher/ajax-fetcher";
import { hideInElement, showInElement } from "../loader";
import landingStatusPoller from "./landing-status-poller";
import { initializeLandingStatus } from "./initialize-landing-status";
import { createAndShowToast } from "../../utils/uiHelpers";

/**
 * Update browser URL with the provided parameters
 * @param {Object} params - Query parameters to update in URL
 */
function updateBrowserUrl(params) {
    const url = new URL(window.location.href);
    
    // Clear existing parameters
    [...url.searchParams.keys()].forEach(key => {
        url.searchParams.delete(key);
    });
    
    // Add new parameters
    Object.entries(params).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== '') {
            url.searchParams.set(key, value);
        }
    });
    
    // Update URL without reloading the page
    window.history.pushState({}, '', url.toString());
}

/**
 * Main content loading function
 * @param {string} ajaxUrl - URL to fetch content from
 * @param {Object} queryParams - Query parameters to send
 * @param {string} targetSelector - CSS selector for the target element
 * @param {boolean} updateHistory - Whether to update browser URL history
 */
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

    // Clean empty parameters
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

    // First cleanup all active pollers
    landingStatusPoller.cleanup();

    ajaxFetcher.get(ajaxUrl, finalParams, {
        successCallback: function (response) {
            const data = response.data;
            $(targetSelector).html(data.table_html);
            
            // Reinitialize landing status tracking on the new content
            setTimeout(() => {
                initializeLandingStatus();
            }, 100); // Small delay to ensure DOM is fully updated
            
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
            // Notify user about error
            createAndShowToast("common.error_occurred_common_message", "error");
        },
        completeCallback: function () {
            if (loaderInstance) {
                hideInElement(loaderInstance);
            }
        },
    });
}
