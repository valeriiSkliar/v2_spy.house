// resources/js/services.js
document.addEventListener("DOMContentLoaded", function () {
    // Filter toggle on mobile
    console.log("Filter toggle on mobile");

    // Sort functionality
    const sortContainer = $("#sort-by");
    if (sortContainer.length) {
        const sortSelect = $(".base-select__dropdown", sortContainer);
        const sortOptions = $(".base-select__option", sortContainer);
        const sortTrigger = $(".base-select__trigger", sortContainer);
        const sortValue = $("[data-value]", sortContainer);
        const sortOrderValue = $("[data-order]", sortContainer);

        // Toggle dropdown on trigger click
        sortTrigger.on("click", function (e) {
            e.stopPropagation();
            sortSelect.show();
        });

        sortOptions.each(function () {
            $(this).on("click", function (e) {
                e.stopPropagation();
                // Remove selected class from all options
                sortOptions.removeClass("is-selected");
                // Add selected class to clicked option
                $(this).addClass("is-selected");
                // Update trigger text
                // sortValue.text($(this).text());
                sortValue.data("value", $(this).data("value"));
                sortOrderValue.data("order", $(this).data("order"));
                // Hide dropdown
                sortSelect.hide();

                // Get sort parameters
                // const sortText = $(this).text();

                let sortBy = sortValue.data("value");
                let sortOrder = sortOrderValue.data("order");

                // console.log(sortText);

                // Update URL with sort parameters
                const url = new URL(window.location.href);
                if (sortBy && sortOrder) {
                    url.searchParams.set("sortBy", sortBy);
                    url.searchParams.set("sortOrder", sortOrder);
                } else {
                    url.searchParams.delete("sortBy");
                    url.searchParams.delete("sortOrder");
                }
                url.searchParams.set("page", "1"); // Reset to first page
                window.location.href = url.toString();
            });
        });

        // Close dropdown when clicking outside
        $(document).on("click", function (e) {
            const clickedElement = $(e.target);
            const isClickInside =
                sortContainer.is(clickedElement) ||
                sortContainer.find(clickedElement).length > 0 ||
                sortSelect.is(clickedElement) ||
                sortTrigger.is(clickedElement) ||
                sortOptions.is(clickedElement);

            if (!isClickInside) {
                sortSelect.hide();
            }
        });
    }
});
