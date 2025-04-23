import jQuery from 'jquery';
window.jQuery = jQuery;
window.$ = jQuery;

import './bootstrap';

import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

// Импортируем main.js после инициализации jQuery
import './libs/main';
