import ServiceApp from './ServiceApp';

document.addEventListener('DOMContentLoaded', () => {
  // Получаем начальные данные, встроенные в HTML
  const initialDataElement = document.getElementById('services-initial-data');
  const initialData = initialDataElement ? JSON.parse(initialDataElement.textContent) : {};

  new ServiceApp(initialData);
});
