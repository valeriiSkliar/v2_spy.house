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
import { initializeNotificationChecking } from "./utils/notification-checker-usage";

// Additional imports
import "./libs/main";
// import "./rating.js";
// import "./comments.js";
import "./base-select.js";
// import "./search-suggestions.js";
import "./carousel.js";
import "./tariffs";

// Initialize notification checking (check every minute)
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.user-preview')) {
        // Only initialize if user is logged in (user preview exists)
        initializeNotificationChecking(60000);
    }
});

// Import the API token system
// API token is now initialized automatically in the module
import { apiTokenHandler } from "@/components/api-token/api-token.js";
