class ServiceRouter {
  constructor(store) {
    this.store = store;
    this.routes = {
      '/services': this.showServiceList.bind(this),
      '/services/:id': this.showServiceDetail.bind(this),
    };
    this.init();
  }

  init() {
    window.addEventListener('popstate', this.handlePopState.bind(this));
    this.store.subscribe(state => this.handleStoreNavigation(state));
    this.navigate(window.location.pathname + window.location.search, false); // Инициализация при загрузке страницы
  }

  handleStoreNavigation(state) {
    const currentPath = window.location.pathname;
    const currentServiceId = state.currentServiceId;

    if (currentServiceId && currentPath !== `/services/${currentServiceId}`) {
      this.pushState(`/services/${currentServiceId}`, state);
    } else if (!currentServiceId && currentPath !== '/services') {
      this.pushState('/services', state);
    }
  }

  // Обработка перехода по URL
  navigate(path, push = true) {
    const pathParts = path.split('?');
    const cleanPath = pathParts[0];
    const queryParams = pathParts[1] ? `?${pathParts[1]}` : '';

    let matched = false;
    for (const routePath in this.routes) {
      const routeRegex = new RegExp(`^${routePath.replace(/:(\w+)/g, '([\\w-]+)')}$`);
      const match = cleanPath.match(routeRegex);

      if (match) {
        const params = match.slice(1);
        const paramNames = (routePath.match(/:(\w+)/g) || []).map(p => p.slice(1));
        const routeParams = paramNames.reduce((acc, name, index) => {
          acc[name] = params[index];
          return acc;
        }, {});

        if (push) {
          this.pushState(path, this.store.getState());
        }
        this.routes[routePath](routeParams, queryParams);
        matched = true;
        break;
      }
    }

    if (!matched) {
      console.warn('No route matched:', cleanPath);
      // Возможно, перенаправить на страницу 404 или список услуг
      if (push) {
        this.pushState('/services', this.store.getState());
      }
      this.showServiceList();
    }
  }

  pushState(path, state) {
    history.pushState(state, '', path);
  }

  replaceState(path, state) {
    history.replaceState(state, '', path);
  }

  handlePopState(event) {
    // Восстанавливаем состояние из history.state, если оно есть
    if (event.state) {
      this.store.setState(event.state, false); // Не оповещать подписчиков, так как роутер сам инициировал изменение
    }
    // Независимо от state, определяем путь и вызываем соответствующий метод
    const currentPath = window.location.pathname + window.location.search;
    this.navigate(currentPath, false); // Не пушим в историю
  }

  showServiceList(params = {}, queryParams = '') {
    console.log('showServiceList вызван', { params, queryParams });
    const urlParams = new URLSearchParams(queryParams);
    const filters = Object.fromEntries(urlParams.entries());
    console.log('Установка фильтров:', filters);
    this.store.setFilters(filters);
    console.log('Сброс ID сервиса на null');
    this.store.setCurrentServiceId(null);
    // ServiceApp будет слушать store и вызывать fetchServices
  }

  showServiceDetail(params) {
    console.log('showServiceDetail вызван', params);
    const serviceId = params.id;
    console.log('Установка ID сервиса:', serviceId);
    this.store.setCurrentServiceId(serviceId);
    // ServiceApp будет слушать store и вызывать fetchServiceById
  }
}

export default ServiceRouter;
