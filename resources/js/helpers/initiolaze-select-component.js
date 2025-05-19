import { setupOutsideClickListener } from "./outside-click";
import { logger, loggerError } from "./logger";
import { buildQueryParams, updateUrlWithRedirect } from "./update-browser-url";

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
    if (selectors.orderElement && selectors.orderElement.length) {
        selectors.orderElement.data("order", selectedOrder);
        if (selectors.orderElement.is("input")) {
            selectors.orderElement.val(selectedOrder).trigger("change");
        }
    }
}

/**
 * Получает значение порядка сортировки из элемента с установкой значения по умолчанию
 * @param {jQuery} $option - jQuery-элемент опции
 * @param {string} defaultValue - Значение по умолчанию
 * @returns {string} - Значение порядка сортировки
 */
function getOrderValue($option, defaultValue = "asc") {
    const order = $option.attr("data-order");
    return (order !== undefined && order !== null && order !== '') ? order : defaultValue;
}



export function initializeSelectComponent(containerId, config) {
    if (!containerId || !config || !config.selectors || !config.params) {
        loggerError(`Неверные параметры инициализации: containerId, config, config.selectors или config.params отсутствуют.`);
        return false;
    }
    const container = $(containerId);
    if (!container.length) {
        loggerError(`Контейнер с селектором ${containerId} не найден.`);
        return false;
    }

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
        return false;
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

            // Получаем значение порядка сортировки с использованием вспомогательной функции
            const selectedOrder = getOrderValue($option);
            logger("Selected order value:", selectedOrder);
            
            updateOrderElement(selectors, selectedOrder);

            selectors.select.hide();

            // Check if custom AJAX handler is provided
            if (config.ajaxHandler && typeof config.ajaxHandler === 'function') {
                // Формируем параметры запроса с помощью вспомогательной функции
                const queryParams = buildQueryParams($option, selectedValue, selectedOrder, config.params, config.resetPage);
                
                // Выводим отладочную информацию
                logger("Передаем в AJAX обработчик:", queryParams);
                
                // Use the provided AJAX handler with error handling
                try {
                    config.ajaxHandler(queryParams);
                } catch (error) {
                    loggerError("Ошибка при выполнении AJAX-обработчика:", error);
                }
                return;
            }
            
            // Check if the component is part of a form with AJAX handling
            const $form = selectors.container.closest('form');
            if ($form.length && ($form.attr('data-form-type') === 'ajax' || $form.attr('data-update-method') === 'ajax')) {
                // Форма с AJAX-обработкой, позволяем ей обрабатывать изменения
                // Генерируем событие 'select:change' для формы
                const eventData = {
                    value: selectedValue,
                    order: selectedOrder,
                    element: $option[0]
                };
                $form.trigger('select:change', [eventData]);
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
