import * as bootstrap from "bootstrap";
window.bootstrap = bootstrap;

// Импортируем axios
import axios from "axios";

// Libs
import "bootstrap-datepicker";
import "bootstrap-select";
import "select2";
import "css-element-queries";
import "slick-carousel";
// import 'counterup';
// import '../js/libs/jquery.star-rating-svg.js';
// import '../js/libs/jquery.sticky-sidebar.min.js';
import "swiper";

window.axios = axios;
window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

// Импортируем main.js после всех зависимостей и инициализации jQuery
