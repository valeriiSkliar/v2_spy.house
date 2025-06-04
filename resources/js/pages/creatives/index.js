import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';

flatpickr('#dateRangePicker', {
  mode: 'range',
  dateFormat: 'd-m-Y',
  // locale: 'ru',
});
