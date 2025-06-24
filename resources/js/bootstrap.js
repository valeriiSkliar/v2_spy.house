import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

// Импортируем axios
import axios from 'axios';

// Libs
import 'bootstrap-select';
import 'css-element-queries';
// import 'swiper';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Импортируем main.js после всех зависимостей и инициализации jQuery
