import { logger } from '../../../helpers/logger';

class ServiceListRenderer {
  constructor(containerSelector = '#services-container', store) {
    this.container = document.querySelector(containerSelector);
    if (!this.container) {
      console.error(`Container not found for selector: ${containerSelector}`);
      return;
    }
    this.store = store;
    this.unsubscribeStore = null; // Для хранения функции отписки
    this.isFirstRender = true; // Флаг для отслеживания первого рендера
    this.bindEvents(); // Инициализация обработчиков для "увлажнения"
  }

  // Инициализация обработчиков событий для существующих DOM-элементов
  bindEvents() {
    // Здесь мы можем навесить слушатели на уже отрендеренные сервером элементы,
    // например, на ссылки "Подробнее" внутри каждого service-item
    this.container.addEventListener('click', event => {
      const serviceItem = event.target.closest('.market-list__item');
      if (serviceItem) {
        const serviceId = serviceItem.dataset.id;
        // Вместо прямого перехода, оповещаем стор или роутер
        // Роутер должен быть подписан на изменения currentServiceId в сторе
        // или мы можем передать колбэк для навигации
        logger('serviceId', serviceId, { debug: true });
        this.store.setCurrentServiceId(serviceId); // Роутер отреагирует на это
        event.preventDefault(); // Предотвращаем дефолтное действие ссылки, если она есть
      }
    });
  }

  // Метод для подписки на изменения в сторе и обновления UI
  init() {
    // Отписка от предыдущей подписки, если есть (для избежания дублирования)
    if (this.unsubscribeStore) {
      this.unsubscribeStore();
    }
    this.unsubscribeStore = this.store.subscribe(state => this.render(state.services));
    // Первоначальная отрисовка, основываясь на данных, уже существующих в DOM
    // Мы не перерисовываем, а "увлажняем" существующее
    this.render(this.store.getState().services); // Просто для инициализации, чтобы не было пустой отрисовки
  }

  // Метод для отрисовки/обновления списка услуг
  render(services) {
    // При первом запуске (после SSR), container уже содержит данные.
    // Мы можем добавить логику для "увлажнения" существующих элементов
    // Если это первый рендер и DOM уже есть, то мы не пересоздаем, а просто убеждаемся в интерактивности
    // Для последующих обновлений (после AJAX), мы полностью перерисовываем
    if (this.isFirstRender && this.container.children.length > 0) {
      // Предполагаем, что сервер отрендерил корректные элементы.
      // Здесь можно, например, привязать слушатели к существующим кнопкам.
      // В нашем случае, click-делегирование в bindEvents уже справляется.
      // this.isFirstRender = false;
      return false; // Не перерисовываем то, что уже есть
    }

    this.container.innerHTML = ''; // Очищаем контейнер при каждом обновлении

    if (services.length === 0) {
      this.container.innerHTML = '<div class="empty-services">Нет доступных услуг</div>';
      return;
    }

    const marketList = document.createElement('div');
    marketList.className = 'market-list';

    services.forEach(service => {
      const serviceItem = document.createElement('div');
      serviceItem.className = 'market-list__item'; // Используем правильный класс
      serviceItem.dataset.id = service.id; // Для получения ID при клике

      // Создаем HTML-структуру, соответствующую дизайну
      serviceItem.innerHTML = `
        <div class="market__box">
          <div class="market__header">
            <div class="market__name">${service.name}</div>
          </div>
          <div class="market__body">
            <div class="market__description">${service.description || ''}</div>
          </div>
          <div class="market__footer">
            <a href="/services/${service.id}" class="btn _red">Подробнее</a>
          </div>
        </div>
      `;

      marketList.appendChild(serviceItem);
    });

    this.container.appendChild(marketList);
  }

  // Метод для очистки, например, при переходе на детальную страницу
  clear() {
    this.container.innerHTML = '';
    if (this.unsubscribeStore) {
      this.unsubscribeStore();
    }
  }
}

export default ServiceListRenderer;
