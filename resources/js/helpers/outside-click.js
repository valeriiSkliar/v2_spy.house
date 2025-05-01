// Function to handle clicks outside a given select component
export function setupOutsideClickListener(container, select, trigger, options) {
    $(document).on("click", function (e) {
        const clickedElement = $(e.target);
        const isClickInside =
            container.is(clickedElement) ||
            container.find(clickedElement).length > 0 ||
            select.is(clickedElement) ||
            trigger.is(clickedElement) ||
            options.is(clickedElement);

        if (!isClickInside) {
            select.hide();
        }
    });
}
