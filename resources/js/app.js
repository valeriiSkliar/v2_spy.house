import jQuery from 'jquery';
import 'slick-carousel';
window.jQuery = jQuery;
window.$ = jQuery;

import './bootstrap';

import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

// Import the pages dynamically
const importPages = () => import('@pages');
importPages();

// Import the components dynamically
const importComponents = () => import('@/components');
importComponents();

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

// Dynamic imports for conditionally loaded components
const importBaseSelect = () => import('./base-select.js');
const importCarousel = () => import('./carousel.js');
const importProfileSettings = () =>
  import('./components/profile/profile-settings-form-validation.js');
const importTariffs = () => import('./tariffs');
const importLogin2FA = async () => {
  const module = await import('./pages/login.js');
  return module.initLogin2FA;
};

// Initialize components on demand
document.addEventListener('DOMContentLoaded', async function () {
  // Load base components that are used on all pages
  importBaseSelect();
  importCarousel();

  // Initialize login form handler on any page that has the login form
  if (document.getElementById('login-form')) {
    console.log('Login form found, initializing form handler');
    const initLogin2FAFunc = await importLogin2FA();
    initLogin2FAFunc();
  }

  // Only load profile settings if the profile page is active
  if (document.querySelector('.profile-settings-form')) {
    importProfileSettings();
  }

  // Only load tariffs if the tariffs section exists
  if (document.querySelector('.tariffs-section')) {
    importTariffs();
  }

  if (document.querySelector('.user-preview')) {
    // Only initialize notification checking if user is logged in (user preview exists)
    initializeNotificationChecking(60000);
  }
});

// Import the API token system
// API token is now initialized automatically in the module
