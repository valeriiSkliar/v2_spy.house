/**
 * Обновляет URL без перезагрузки страницы, используя History API
 * @param {Object} queryParams - Параметры запроса для добавления в URL
 */
function updateBrowserUrl(queryParams) {
    const currentUrl = new URL(window.location.href);
    Object.keys(queryParams).forEach((key) => {
        if (
            queryParams[key] === null ||
            queryParams[key] === undefined ||
            queryParams[key] === ""
        ) {
            currentUrl.searchParams.delete(key);
        } else {
            currentUrl.searchParams.set(key, queryParams[key]);
        }
    });
    // Удаляем 'page' если значение 1 для более чистого URL первой страницы
    if (currentUrl.searchParams.get("page") === "1") {
        currentUrl.searchParams.delete("page");
    }
    history.pushState(queryParams, "", currentUrl.toString());
}

/**
 * Формирует URL для перенаправления на основе выбранных параметров фильтрации и сортировки
 * @param {string} valueParam - Название параметра для значения фильтра
 * @param {string} selectedValue - Значение фильтра
 * @param {string} orderParam - Название параметра для порядка сортировки
 * @param {string} selectedOrder - Значение порядка сортировки
 * @param {boolean} resetPage - Сбросить ли номер страницы на 1
 * @returns {string} - Новый URL для перенаправления
 */
function updateUrlWithRedirect(valueParam, selectedValue, orderParam, selectedOrder, resetPage = false) {
    const url = new URL(window.location.href);
    
    if (selectedValue) {
        url.searchParams.set(valueParam, selectedValue);
    } else {
        url.searchParams.delete(valueParam);
    }
    
    if (orderParam && selectedOrder) {
        url.searchParams.set(orderParam, selectedOrder);
    } else if (orderParam) {
        url.searchParams.delete(orderParam);
    }
    
    if (resetPage) {
        url.searchParams.set('page', '1');
    }
    
    return url.toString();
}

export { updateBrowserUrl, updateUrlWithRedirect };
