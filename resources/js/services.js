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

    const categoryContainer = $("#category-filter");
    if (categoryContainer.length) {
        const categorySelect = $(".base-select__dropdown", categoryContainer);
        const categoryOptions = $(".base-select__option", categoryContainer);
        const categoryTrigger = $(".base-select__trigger", categoryContainer);
        const categoryValue = $("[data-value]", categoryContainer);

        categoryTrigger.on("click", function (e) {
            e.stopPropagation();
            categorySelect.show();
        });

        categoryOptions.each(function () {
            $(this).on("click", function (e) {
                e.stopPropagation();
                categoryOptions.removeClass("is-selected");
                $(this).addClass("is-selected");
                categoryValue.data("value", $(this).data("value"));
                categorySelect.hide();

                let category = categoryValue.data("value");

                const url = new URL(window.location.href);
                if (category) {
                    url.searchParams.set("category", category);
                } else {
                    url.searchParams.delete("category");
                }
                // url.searchParams.set("page", "1");
                window.location.href = url.toString();
            });
        });

        $(document).on("click", function (e) {
            const clickedElement = $(e.target);
            const isClickInside =
                categoryContainer.is(clickedElement) ||
                categoryContainer.find(clickedElement).length > 0 ||
                categorySelect.is(clickedElement) ||
                categoryTrigger.is(clickedElement) ||
                categoryOptions.is(clickedElement);

            if (!isClickInside) {
                categorySelect.hide();
            }
        });
    }
});
