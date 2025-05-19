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
 * @param {string|number} selectedValue - Значение фильтра
 * @param {string} orderParam - Название параметра для порядка сортировки
 * @param {string|number} selectedOrder - Значение порядка сортировки
 * @param {boolean} resetPage - Сбросить ли номер страницы на 1
 * @returns {string|null} - Новый URL для перенаправления или null при ошибке валидации
 * @throws {Error} - Выбрасывает ошибку при критических проблемах валидации
 */
function updateUrlWithRedirect(valueParam, selectedValue, orderParam, selectedOrder, resetPage = false) {
    try {
        // Проверка типа resetPage
        if (typeof resetPage !== 'boolean') {
            resetPage = Boolean(resetPage);
        }
        
        // Валидация параметров
        if (!valueParam || typeof valueParam !== 'string' || valueParam.trim() === '') {
            console.error('Ошибка валидации: valueParam должен быть непустой строкой');
            return null;
        }
        
        // Создаем URL
        const url = new URL(window.location.href);
        
        // Обработка selectedValue
        if (selectedValue !== undefined && selectedValue !== null && selectedValue !== '') {
            // Преобразуем к строке, если это не строка
            const valueStr = String(selectedValue).trim();
            if (valueStr !== '') {
                url.searchParams.set(valueParam, valueStr);
            } else {
                url.searchParams.delete(valueParam);
            }
        } else {
            url.searchParams.delete(valueParam);
        }
        
        // Обработка orderParam и selectedOrder
        if (orderParam) {
            if (typeof orderParam !== 'string' || orderParam.trim() === '') {
                console.error('Ошибка валидации: orderParam должен быть непустой строкой');
            } else if (selectedOrder !== undefined && selectedOrder !== null && selectedOrder !== '') {
                // Преобразуем к строке, если это не строка
                const orderStr = String(selectedOrder).trim();
                if (orderStr !== '') {
                    url.searchParams.set(orderParam, orderStr);
                } else {
                    url.searchParams.delete(orderParam);
                }
            } else {
                url.searchParams.delete(orderParam);
            }
        }
        
        // Сброс страницы
        if (resetPage) {
            url.searchParams.set('page', '1');
        }
        
        return url.toString();
    } catch (error) {
        console.error('Ошибка при формировании URL:', error);
        return null;
    }
}

/**
 * Формирует объект параметров запроса для AJAX-обработчика
 * @param {jQuery} $option - jQuery-элемент выбранной опции
 * @param {string} selectedValue - Выбранное значение
 * @param {string} selectedOrder - Выбранный порядок сортировки
 * @param {Object} params - Параметры конфигурации (valueParam, orderParam)
 * @param {boolean} resetPage - Флаг сброса страницы
 * @returns {Object} - Объект с параметрами запроса
 */
function buildQueryParams($option, selectedValue, selectedOrder, params, resetPage = false) {
    const queryParams = {};
    
    // Добавляем параметр значения
    if (params.valueParam) {
        if (selectedValue) {
            queryParams[params.valueParam] = selectedValue;
        } else {
            // Используем значение из HTML напрямую
            const rawValue = $option.attr("data-value");
            if (rawValue) {
                queryParams[params.valueParam] = rawValue;
                logger("Используем значение из data-value:", rawValue);
            }
        }
    }
    
    // Добавляем параметр порядка сортировки
    if (params.orderParam && selectedOrder) {
        queryParams[params.orderParam] = selectedOrder;
    }
    
    // Сбрасываем страницу, если нужно
    if (resetPage) {
        queryParams.page = 1;
    }
    
    return queryParams;
}

export { updateBrowserUrl, updateUrlWithRedirect, buildQueryParams };
