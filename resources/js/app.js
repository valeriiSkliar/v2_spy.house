import jQuery from 'jquery';
import 'jquery-validation';
import 'slick-carousel';
window.jQuery = jQuery;
window.$ = jQuery;

import './bootstrap';

// Import the pages
import '@pages';

// Import the components
import '@/components';
import './base-select.js';

// Import the modal system
import './components/modal';

// Import the toast system
import './components/toasts';

// Import SweetAlert2 service
import './services/sweetAlertExamples';
import './services/sweetAlertService';

// Import helpers
import './helpers';

// Import utils
import '@/utils';
import { initializeNotificationChecking } from './utils/notification-checker-usage';

// Import localization utility
import './utils/localization';

// Additional imports
import './libs/main';
// import "./rating.js";
// import "./comments.js";
// import "./search-suggestions.js";
import './carousel.js';
import './components/profile/profile-settings-form-validation.js';
import { initLogin2FA } from './pages/login.js';
// import './pages/verify-email.js';
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
