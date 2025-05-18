import { setupOutsideClickListener } from "./outside-click";

export function initializeSelectComponent(containerId, config) {
    const container = $(containerId);
    if (!container.length) {
        return;
    }

    const select = $(config.selectors.select, container);
    const options = $(config.selectors.options, container);
    const trigger = $(config.selectors.trigger, container);
    const valueElement = $(config.selectors.valueElement, container);
    const selectedLabelElement = $(".base-select__selected-label", container);
    const selectedPleaceHolderElement = $(
        ".base-select__placeholder",
        container
    );

    const orderElement = config.selectors.orderElement
        ? $(config.selectors.orderElement, container)
        : null;

    // Show dropdown on trigger click
    trigger.on("click", function (e) {
        e.stopPropagation();
        select.show();
    });

    // Handle option selection
    options.each(function () {
        $(this).on("click", function (e) {
            e.stopPropagation();
            options.removeClass("is-selected");
            $(this).addClass("is-selected");

            const selectedValue = $(this).data("value");
            const selectedLabel = $(this).data("label");
            const placeholder = $(this).data("placeholder");
            // Update the displayed selected value
            if (selectedLabelElement.length) {
                trigger.text(placeholder.concat(selectedLabel));
                selectedLabelElement.text(selectedLabel);
            }

            // Set value to valueElement (supports both data attribute and input value)
            valueElement.data("value", selectedValue);
            if (valueElement.is("input")) {
                valueElement.val(selectedValue).trigger("change");
            }

            let selectedOrder = null;
            if (orderElement) {
                selectedOrder = $(this).data("order");
                orderElement.data("order", selectedOrder);
                if (orderElement.is("input")) {
                    orderElement.val(selectedOrder).trigger("change");
                }
            }

            select.hide();

            // Check if custom AJAX handler is provided
            if (config.ajaxHandler && typeof config.ajaxHandler === 'function') {
                // Reset page if configured
                const queryParams = {};
                queryParams[config.params.valueParam] = selectedValue;
                
                if (orderElement && config.params.orderParam && selectedOrder) {
                    queryParams[config.params.orderParam] = selectedOrder;
                }
                
                if (config.resetPage) {
                    queryParams.page = 1;
                }
                
                // Use the provided AJAX handler
                config.ajaxHandler(queryParams);
                return;
            }
            
            // Check if the component is part of a form with data-ajax-enabled
            const $form = $(container).closest('form');
            if ($form.length && $form.data('ajax-enabled')) {
                // Let the form handle updates (it should have its own change event handlers)
                return;
            }
            
            // Default behavior - redirect the page
            if (!$form.length) {
                // Update URL only if not in a form
                const url = new URL(window.location.href);
    
                if (selectedValue) {
                    url.searchParams.set(config.params.valueParam, selectedValue);
                } else {
                    url.searchParams.delete(config.params.valueParam);
                }
    
                if (orderElement && config.params.orderParam) {
                    if (selectedOrder) {
                        url.searchParams.set(
                            config.params.orderParam,
                            selectedOrder
                        );
                    } else {
                        url.searchParams.delete(config.params.orderParam);
                    }
                }
    
                if (config.resetPage) {
                    url.searchParams.set("page", "1");
                }
    
                window.location.href = url.toString();
            }
        });
    });

    // Setup click outside listener
    setupOutsideClickListener(container, select, trigger, options);
}
