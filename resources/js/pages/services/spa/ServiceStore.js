class ServiceStore {
  constructor(initialData) {
    this.state = {
      services: initialData.services || [],
      categories: initialData.categories || [],
      filters: initialData.filters || {}, // Текущие фильтры
      pagination: initialData.pagination || {
        current_page: 1,
        total_pages: 1,
        total: 0,
        per_page: 12,
      },
      currentServiceId: null, // ID текущего просматриваемого сервиса
      loading: false,
      error: null,
      // Дополнительные состояния по мере необходимости
    };
    this.subscribers = {}; // Объект для хранения подписчиков
  }

  // Метод для получения состояния
  getState() {
    return { ...this.state }; // Возвращаем копию, чтобы избежать прямого изменения извне
  }

  // Метод для обновления состояния и оповещения подписчиков
  setState(newState, triggerSubscribers = true) {
    // Оптимизация: Обновляем только измененные поля
    let hasChanged = false;
    for (const key in newState) {
      if (JSON.stringify(this.state[key]) !== JSON.stringify(newState[key])) {
        this.state[key] = newState[key];
        hasChanged = true;
      }
    }

    if (triggerSubscribers && hasChanged) {
      this._notifySubscribers();
    }
  }

  // Метод для подписки на изменения
  subscribe(callback) {
    const id = Symbol('subscriberId'); // Уникальный идентификатор для подписчика
    this.subscribers[id] = callback;
    return () => delete this.subscribers[id]; // Функция для отписки
  }

  // Приватный метод для оповещения всех подписчиков
  _notifySubscribers() {
    for (const id in this.subscribers) {
      this.subscribers[id](this.getState());
    }
  }

  // Методы-акторы для изменения состояния (опционально, можно напрямую через setState)
  setServices(services, pagination) {
    this.setState({ services, pagination });
  }

  setFilters(filters) {
    this.setState({ filters });
  }

  // Метод для обновления пагинации
  setPagination(pagination) {
    const currentPagination = this.state.pagination;
    this.setState({
      pagination: {
        ...currentPagination,
        ...pagination,
      },
    });
  }

  // Метод для обновления настроек сортировки
  setSorting(sorting) {
    const newFilters = { ...this.state.filters };

    if (sorting.field) {
      newFilters.sort_by = sorting.field;
    }

    if (sorting.order) {
      newFilters.sort_order = sorting.order;
    }

    this.setState({ filters: newFilters });
  }

  // Метод для установки количества элементов на странице
  setPerPage(perPage) {
    const pagination = { ...this.state.pagination, per_page: perPage };
    this.setState({ pagination });
  }

  // Метод для установки текущей страницы
  setCurrentPage(page) {
    const pagination = { ...this.state.pagination, current_page: page };
    this.setState({ pagination });
  }

  setCurrentServiceId(id) {
    this.setState({ currentServiceId: id });
  }

  setLoading(isLoading) {
    this.setState({ loading: isLoading });
  }

  setError(error) {
    this.setState({ error });
  }
}

export default ServiceStore;
