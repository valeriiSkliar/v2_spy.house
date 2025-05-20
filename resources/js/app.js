import jQuery from 'jquery';
import 'slick-carousel';
window.jQuery = jQuery;
window.$ = jQuery;

import './bootstrap';

import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

// Import the pages
import '@pages';

// Import the components
import '@/components';

// Import the modal system
import './components/modal';

// Import the toast system
import './components/toasts';

// Import helpers
import './helpers';

// Import utils
import '@/utils';
import { initializeNotificationChecking } from './utils/notification-checker-usage';

// Additional imports
import './libs/main';
// import "./rating.js";
// import "./comments.js";
import './base-select.js';
// import "./search-suggestions.js";
import './carousel.js';
import { initLogin2FA } from './pages/login.js';
import './tariffs';

// Initialize notification checking (check every minute)
document.addEventListener('DOMContentLoaded', function () {
  // Initialize login form handler on any page that has the login form
  if (document.getElementById('login-form')) {
    console.log('Login form found, initializing form handler');
    initLogin2FA();
  }

  if (document.querySelector('.user-preview')) {
    // Only initialize notification checking if user is logged in (user preview exists)
    initializeNotificationChecking(60000);
  }
});

// Import the API token system
// API token is now initialized automatically in the module
