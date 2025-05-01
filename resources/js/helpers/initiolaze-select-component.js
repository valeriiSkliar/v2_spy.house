import { setupOutsideClickListener } from "./outside-click";
// Generalized function to initialize select components
export function initializeSelectComponent(containerId, config) {
    const container = $(containerId);
    if (!container.length) {
        return; // Exit if the container doesn't exist
    }

    const select = $(config.selectors.select, container);
    const options = $(config.selectors.options, container);
    const trigger = $(config.selectors.trigger, container);
    const valueElement = $(config.selectors.valueElement, container);
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
            valueElement.data("value", selectedValue);

            let selectedOrder = null;
            if (orderElement) {
                selectedOrder = $(this).data("order");
                orderElement.data("order", selectedOrder);
            }

            select.hide();

            // Update URL
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
        });
    });

    // Setup click outside listener
    setupOutsideClickListener(container, select, trigger, options);
}
