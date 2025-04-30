document.addEventListener("DOMContentLoaded", function () {
    const sortContainer = $("#sort-by");
    if (sortContainer.length) {
        const sortSelect = $(".base-select__dropdown", sortContainer);
        const sortOptions = $(".base-select__option", sortContainer);
        const sortTrigger = $(".base-select__trigger", sortContainer);
        const sortValue = $("[data-value]", sortContainer);
        const sortOrderValue = $("[data-order]", sortContainer);

        sortTrigger.on("click", function (e) {
            e.stopPropagation();
            sortSelect.show();
        });

        sortOptions.each(function () {
            $(this).on("click", function (e) {
                e.stopPropagation();
                sortOptions.removeClass("is-selected");
                $(this).addClass("is-selected");
                sortValue.data("value", $(this).data("value"));
                sortOrderValue.data("order", $(this).data("order"));
                sortSelect.hide();

                let sortBy = sortValue.data("value");
                let sortOrder = sortOrderValue.data("order");

                const url = new URL(window.location.href);
                if (sortBy && sortOrder) {
                    url.searchParams.set("sortBy", sortBy);
                    url.searchParams.set("sortOrder", sortOrder);
                } else {
                    url.searchParams.delete("sortBy");
                    url.searchParams.delete("sortOrder");
                }
                url.searchParams.set("page", "1");
                window.location.href = url.toString();
            });
        });

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

    const perPageContainer = $("#per-page");
    if (perPageContainer.length) {
        const perPageSelect = $(".base-select__dropdown", perPageContainer);
        const perPageOptions = $(".base-select__option", perPageContainer);
        const perPageTrigger = $(".base-select__trigger", perPageContainer);
        const perPageValue = $("[data-value]", perPageContainer);
        const perPageOrderValue = $("[data-order]", perPageContainer);

        perPageTrigger.on("click", function (e) {
            e.stopPropagation();
            perPageSelect.show();
        });

        perPageOptions.each(function () {
            $(this).on("click", function (e) {
                e.stopPropagation();
                perPageOptions.removeClass("is-selected");
                $(this).addClass("is-selected");
                perPageValue.data("value", $(this).data("value"));
                perPageOrderValue.data("order", $(this).data("order"));
                perPageSelect.hide();

                let perPage = perPageValue.data("value");

                const url = new URL(window.location.href);
                if (perPage) {
                    url.searchParams.set("perPage", perPage);
                } else {
                    url.searchParams.delete("perPage");
                }
                url.searchParams.set("page", "1");
                window.location.href = url.toString();
            });
        });

        $(document).on("click", function (e) {
            const clickedElement = $(e.target);
            const isClickInside =
                perPageContainer.is(clickedElement) ||
                perPageContainer.find(clickedElement).length > 0 ||
                perPageSelect.is(clickedElement) ||
                perPageTrigger.is(clickedElement) ||
                perPageOptions.is(clickedElement);

            if (!isClickInside) {
                perPageSelect.hide();
            }
        });
    }
});
