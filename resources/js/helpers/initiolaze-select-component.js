import { setupOutsideClickListener } from "./outside-click";
import { logger, loggerError } from "./logger";
import { updateUrlWithRedirect } from "./update-browser-url";

/**
 * Обновляет отображаемую метку выбранного элемента
 * @param {Object} selectors - Объект с кэшированными jQuery-элементами
 * @param {string} placeholder - Текст плейсхолдера
 * @param {string} selectedLabel - Текст выбранного элемента
 */
function updateSelectedLabel(selectors, placeholder, selectedLabel) {
    if (selectors.selectedLabel.length) {
        selectors.trigger.text(placeholder.concat(selectedLabel));
        selectors.selectedLabel.text(selectedLabel);
    }
}

/**
 * Обновляет элемент значения
 * @param {Object} selectors - Объект с кэшированными jQuery-элементами
 * @param {string} selectedValue - Выбранное значение
 */
function updateValueElement(selectors, selectedValue) {
    selectors.valueElement.data("value", selectedValue);
    if (selectors.valueElement.is("input")) {
        selectors.valueElement.val(selectedValue).trigger("change");
    }
}

/**
 * Обновляет элемент порядка сортировки
 * @param {Object} selectors - Объект с кэшированными jQuery-элементами
 * @param {string} selectedOrder - Выбранный порядок сортировки
 */
function updateOrderElement(selectors, selectedOrder) {
    if (selectors.orderElement) {
        selectors.orderElement.data("order", selectedOrder);
        if (selectors.orderElement.is("input")) {
            selectors.orderElement.val(selectedOrder).trigger("change");
        }
    }
}

export function initializeSelectComponent(containerId, config) {
    // Проверка входных параметров
    if (!containerId || !config || !config.selectors || !config.params) {
        loggerError('Неверные параметры containerId или config');
        return false;
    }
    
    const container = $(containerId);
    if (!container.length) {
        loggerError(`Контейнер ${containerId} не найден`);
        return false;
    }

    // Кэширование jQuery-объектов в одном месте
    const selectors = {
        container: container,
        select: $(config.selectors.select, container),
        options: $(config.selectors.options, container),
        trigger: $(config.selectors.trigger, container),
        valueElement: $(config.selectors.valueElement, container),
        selectedLabel: $(".base-select__selected-label", container),
        placeholder: $(".base-select__placeholder", container),
        orderElement: config.selectors.orderElement ? $(config.selectors.orderElement, container) : null
    };

    // Show dropdown on trigger click
    selectors.trigger.on("click", function (e) {
        e.stopPropagation();
        selectors.select.show();
    });

    // Handle option selection using event delegation
    selectors.container.on('click', config.selectors.options, function(e) {
        e.stopPropagation();
        
        // Получаем выбранный элемент
        const $option = $(this);
        
        // Обновляем классы выбранных элементов
        selectors.options.removeClass("is-selected");
        $option.addClass("is-selected");

        // Используем attr() вместо data() для получения значений из HTML-атрибутов
        const selectedValue = $option.attr("data-value");
        const selectedLabel = $option.attr("data-label");
        const placeholder = $option.attr("data-placeholder");
        
        logger("Выбранный элемент:", {
            element: $option[0],
            rawValue: selectedValue,
            rawOrder: $option.attr("data-order"),
            rawLabel: selectedLabel
        });
            // Обновляем отображаемые элементы
            updateSelectedLabel(selectors, placeholder, selectedLabel);
            updateValueElement(selectors, selectedValue);

            // Получаем значение порядка сортировки из атрибута data-order выбранного элемента
            let selectedOrder = $option.attr("data-order");
            logger("Raw selectedOrder from data attribute:", selectedOrder);
            
            // Проверяем, что значение не undefined и не null
            if (selectedOrder === undefined || selectedOrder === null) {
                selectedOrder = "asc"; // Значение по умолчанию, если не указано
            }
            
            updateOrderElement(selectors, selectedOrder);

            selectors.select.hide();

            // Check if custom AJAX handler is provided
            if (config.ajaxHandler && typeof config.ajaxHandler === 'function') {
                // Reset page if configured
                const queryParams = {};
                
                // Проверяем и логируем значения перед их использованием
                logger("Config params:", config.params);
                logger("Value param name:", config.params.valueParam);
                logger("Order param name:", config.params.orderParam);
                
                // Проверяем, что selectedValue имеет значение
                if (selectedValue) {
                    queryParams[config.params.valueParam] = selectedValue;
                } else {
                    loggerError("Ошибка: selectedValue не определен");
                    // Используем значение из HTML напрямую
                    const rawValue = $option.attr("data-value");
                    if (rawValue) {
                        queryParams[config.params.valueParam] = rawValue;
                        logger("Используем значение из data-value:", rawValue);
                    }
                }
                
                // Получаем значение order из текущего выбранного элемента
                if (selectors.orderElement && config.params.orderParam) {
                    if (selectedOrder) {
                        queryParams[config.params.orderParam] = selectedOrder;
                    } else {
                        loggerError("Ошибка: selectedOrder не определен");
                        // Используем значение из HTML напрямую
                        const rawOrder = $option.attr("data-order");
                        if (rawOrder) {
                            queryParams[config.params.orderParam] = rawOrder;
                            logger("Используем значение из data-order:", rawOrder);
                        }
                    }
                }
                
                if (config.resetPage) {
                    queryParams.page = 1;
                }
                
                // Выводим отладочную информацию
                logger("Передаем в AJAX обработчик:", queryParams);
                
                // Use the provided AJAX handler
                config.ajaxHandler(queryParams);
                return;
            }
            
            // Check if the component is part of a form with data-ajax-enabled
            const $form = selectors.container.closest('form');
            if ($form.length && $form.data('ajax-enabled')) {
                // Let the form handle updates (it should have its own change event handlers)
                return;
            }
            
            // Default behavior - redirect the page
            if (!$form.length) {
                // Используем функцию для обновления URL
                const redirectUrl = updateUrlWithRedirect(
                    config.params.valueParam,
                    selectedValue,
                    config.params.orderParam,
                    selectedOrder,
                    config.resetPage
                );
                
                window.location.href = redirectUrl;
            }
        });

    // Setup click outside listener
    setupOutsideClickListener(selectors.container, selectors.select, selectors.trigger, selectors.options);
}
