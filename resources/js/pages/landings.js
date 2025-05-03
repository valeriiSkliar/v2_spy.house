import { initializeSelectComponent } from "@/helpers";
import {
    initializeLandingStatus,
    initializeDynamicLandingStatus,
} from "../components/landings";

document.addEventListener("DOMContentLoaded", function () {
    initializeSelectComponent("#sort-select", {
        selectors: {
            select: ".base-select__dropdown",
            options: ".base-select__option",
            trigger: ".base-select__trigger",
            valueElement: "[data-value]",
            orderElement: "[data-order]",
        },
        params: {
            valueParam: "sort",
            orderParam: "direction",
        },
        resetPage: true,
    });

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

    // Initialize landing status polling
    initializeLandingStatus();
    initializeDynamicLandingStatus();
});
