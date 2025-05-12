import { initializeSelectComponent } from "@/helpers";

document.addEventListener("DOMContentLoaded", function () {
    initializeSelectComponent("#per-page-select", {
        selectors: {
            select: ".base-select__dropdown",
            options: ".base-select__option",
            trigger: ".base-select__trigger",
            valueElement: "[data-value]",
        },
        params: {
            valueParam: "per_page",
        },
        resetPage: true,
    });
});
