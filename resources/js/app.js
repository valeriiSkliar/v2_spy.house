import jQuery from "jquery";
window.jQuery = jQuery;
window.$ = jQuery;
import "slick-carousel";

import "./bootstrap";

import Alpine from "alpinejs";
window.Alpine = Alpine;
Alpine.start();

// Import the pages
import "@pages";

// Import the components
import "@/components";

// Import the modal system
import "./components/modal";

// Import the toast system
import "./components/toasts";

// Import helpers
import "./helpers";

// Import utils
import "@/utils";

// Additional imports
import "./libs/main";
// import "./rating.js";
// import "./comments.js";
import "./base-select.js";
// import "./search-suggestions.js";
import "./carousel.js";
import "./tariffs";

// Import the API token system
import { apiTokenHandler } from "@/components/api-token/api-token.js";

document.addEventListener("DOMContentLoaded", () => {
    const apiTokenValue = $("#api_token").val();

    if (apiTokenValue) {
        apiTokenHandler.init(apiTokenValue);
    }
});
