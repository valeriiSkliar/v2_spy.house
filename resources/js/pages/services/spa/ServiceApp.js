// public/js/services/ServiceApp.js

import ServiceAPIManager from './ServiceAPIManager.js';
import ServiceDetailRenderer from './ServiceDetailRenderer.js';
import ServiceFilterManager from './ServiceFilterManager.js';
import ServiceListRenderer from './ServiceListRenderer.js';
import ServiceRouter from './ServiceRouter.js';
import ServiceStore from './ServiceStore.js';

class ServiceApp {
  constructor(initialData) {
    this.store = new ServiceStore(initialData);
    this.apiManager = new ServiceAPIManager();
    this.listRenderer = new ServiceListRenderer('#services-container', this.store);
    this.detailRenderer = new ServiceDetailRenderer('#services-container', this.store);
    this.router = new ServiceRouter(this.store);
    this.filterManager = new ServiceFilterManager('.filter', this.store);

    this.init();
  }

  init() {
    this.store.subscribe(state => this.handleStateChange(state));
    this.listRenderer.init();
    this.detailRenderer.init();

    // Проверяем начальное состояние URL для определения, что отображать
    const initialPath = window.location.pathname;
    if (initialPath.match(/\/services\/\d+$/)) {
      const serviceId = initialPath.split('/').pop();
      this.store.setCurrentServiceId(serviceId);
      this.fetchServiceDetail(serviceId);
    } else {
      // Если мы на странице списка, запускаем загрузку, если данных нет (хотя SSR должен был их предоставить)
      // или если фильтры изменились
      if (
        this.store.getState().services.length === 0 ||
        Object.keys(this.store.getState().filters).length > 0
      ) {
        this.fetchServices();
      } else {
        // Если данные уже есть (из SSR) и фильтры по умолчанию,
        // просто убеждаемся, что listRenderer активен.
        this.listRenderer.render(this.store.getState().services);
      }
    }
  }

  // Обработчик изменений в сторе
  async handleStateChange(state) {
    console.log('State changed:', {
      currentServiceId: state.currentServiceId,
      filtersCount: Object.keys(state.filters).length,
      servicesCount: state.services.length,
    });

    // Логика для загрузки данных при изменении фильтров или страницы
    if (
      !this.lastFetchedFilters ||
      JSON.stringify(state.filters) !== JSON.stringify(this.lastFetchedFilters) ||
      state.pagination.currentPage !== this.lastFetchedPage
    ) {
      console.log('Фильтры или страница изменились, загружаем услуги');
      this.fetchServices();
    }

    // Логика для переключения между списком и детальной страницей
    if (state.currentServiceId) {
      console.log('Отображаем детали услуги', state.currentServiceId);
      this.listRenderer.clear(); // Скрываем список
      const existingService = this.store
        .getState()
        .services.find(s => s.id == state.currentServiceId);
      this.detailRenderer.render(existingService); // Попытка найти в уже загруженных
      if (!existingService) {
        console.log('Услуга не найдена в кэше, загружаем отдельно');
        await this.fetchServiceDetail(state.currentServiceId); // Если нет, загружаем отдельно
      }
    } else {
      console.log('Отображаем список услуг');
      this.detailRenderer.clear(); // Скрываем детали
      this.listRenderer.render(state.services); // Показываем список
    }
  }

  async fetchServices() {
    this.store.setLoading(true);
    this.store.setError(null);
    try {
      const state = this.store.getState();
      const data = await this.apiManager.fetchServices(state.filters, state.pagination.currentPage);
      this.store.setServices(data.services, data.pagination);
      this.lastFetchedFilters = { ...state.filters }; // Обновить последний загруженный фильтр
      this.lastFetchedPage = state.pagination.currentPage;
    } catch (error) {
      this.store.setError(error.message);
    } finally {
      this.store.setLoading(false);
    }
  }

  async fetchServiceDetail(id) {
    this.store.setLoading(true);
    this.store.setError(null);
    try {
      const service = await this.apiManager.fetchServiceById(id);
      // Добавляем полученную услугу в список услуг в сторе, если её там ещё нет
      const currentServices = this.store.getState().services;
      if (!currentServices.some(s => s.id == service.id)) {
        this.store.setServices([...currentServices, service], this.store.getState().pagination);
      }
      this.detailRenderer.render(service); // Принудительная отрисовка детали после загрузки
    } catch (error) {
      this.store.setError(error.message);
    } finally {
      this.store.setLoading(false);
    }
  }
}

export default ServiceApp;
