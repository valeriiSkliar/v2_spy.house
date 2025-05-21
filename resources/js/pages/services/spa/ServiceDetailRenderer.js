class ServiceDetailRenderer {
  constructor(containerSelector = '#services-container', store) {
    this.container = document.querySelector(containerSelector);
    if (!this.container) {
      console.error(`Container not found for selector: ${containerSelector}`);
      return;
    }
    this.store = store;
    this.currentService = null;
    this.unsubscribeStore = null;
  }

  init() {
    if (this.unsubscribeStore) {
      this.unsubscribeStore();
    }
    this.unsubscribeStore = this.store.subscribe(state => {
      const currentServiceId = state.currentServiceId;
      if (currentServiceId) {
        // В идеале, здесь нужно получить детальные данные
        // либо из уже загруженных services, либо новым API-запросом
        this.render(state.services.find(s => s.id == currentServiceId));
      } else {
        this.clear();
      }
    });
  }

  render(service) {
    if (!service) {
      this.clear();
      return;
    }
    this.currentService = service;
    this.container.innerHTML = `
            <div class="service-detail">
                <h1>${service.name}</h1>
                <p>${service.description}</p>
                <p><strong>Price:</strong> ${service.price || 'N/A'}</p>
                <button id="back-to-services">Back to Services</button>
            </div>
        `;
    // Навешиваем слушатель на кнопку "назад"
    document.getElementById('back-to-services')?.addEventListener('click', () => {
      this.store.setCurrentServiceId(null); // Сброс, чтобы роутер вернулся к списку
    });
  }

  clear() {
    this.container.innerHTML = '';
    this.currentService = null;
    if (this.unsubscribeStore) {
      this.unsubscribeStore();
      this.unsubscribeStore = null;
    }
  }
}

export default ServiceDetailRenderer;
