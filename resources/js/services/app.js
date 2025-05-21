/**
 * SPA-функциональность для страницы услуг
 */
document.addEventListener('DOMContentLoaded', function () {
  // Проверяем, что мы на странице сервисов
  if (!document.getElementById('services-initial-data')) return;

  // Получаем начальные данные
  const initialData = JSON.parse(document.getElementById('services-initial-data').textContent);

  // Функция для обновления URL без перезагрузки страницы
  function updateUrl(params) {
    const url = new URL(window.location.href);

    // Удаляем все существующие параметры
    for (const key of url.searchParams.keys()) {
      url.searchParams.delete(key);
    }

    // Добавляем новые параметры
    for (const [key, value] of Object.entries(params)) {
      if (value !== null && value !== undefined && value !== '') {
        url.searchParams.set(key, value);
      }
    }

    // Обновляем URL без перезагрузки страницы
    window.history.pushState({}, '', url.toString());
  }

  // Функция для загрузки данных с сервера через API
  async function loadServices(params) {
    try {
      const queryParams = new URLSearchParams(params).toString();
      const response = await fetch(`/api/services?${queryParams}`);

      if (!response.ok) {
        throw new Error('Ошибка при загрузке сервисов');
      }

      const data = await response.json();
      return data;
    } catch (error) {
      console.error('Ошибка:', error);
      return null;
    }
  }

  // Функция для обновления контента на странице
  function updateContent(data) {
    if (!data) return;

    const servicesContainer = document.getElementById('services-container');

    // Обновляем список услуг
    if (data.services.length === 0) {
      // Показываем сообщение о пустом списке
      servicesContainer.innerHTML = `
                <div class="empty-services">
                    <p>No services found. Please try another search or clear filters.</p>
                </div>
            `;
    } else {
      // Формируем HTML для списка услуг
      let servicesHtml = `<div class="services-list row">`;

      data.services.forEach(service => {
        servicesHtml += generateServiceCardHtml(service);
      });

      servicesHtml += `</div>`;

      // Добавляем пагинацию, если нужно
      if (data.pagination.total_pages > 1) {
        servicesHtml += generatePaginationHtml(data.pagination);
      }

      servicesContainer.innerHTML = servicesHtml;
    }

    // Переинициализируем обработчики событий на новых элементах
    setupEventListeners();
  }

  // Функция для генерации HTML-карточки услуги
  function generateServiceCardHtml(service) {
    // Здесь должен быть код для генерации HTML в соответствии с вашим дизайном
    return `
            <div class="col-sm-6 col-lg-4 col-xl-3 mb-4">
                <div class="service-card">
                    <div class="service-card-header">
                        <div class="service-logo">
                            <img src="${service.logo}" alt="${service.name}">
                        </div>
                        <div class="service-name">${service.name}</div>
                    </div>
                    <div class="service-card-body">
                        <div class="service-description">${service.description}</div>
                        <div class="service-category">${
                          service.category ? service.category.name : ''
                        }</div>
                        <div class="service-stats">
                            <div class="service-rating">Rating: ${service.rating}</div>
                            <div class="service-views">Views: ${service.views}</div>
                            <div class="service-transitions">Transitions: ${
                              service.transitions
                            }</div>
                        </div>
                    </div>
                    <div class="service-card-footer">
                        <a href="/services/${service.id}" class="btn btn-primary">View details</a>
                    </div>
                </div>
            </div>
        `;
  }

  // Функция для генерации HTML-пагинации
  function generatePaginationHtml(pagination) {
    let html = `<div class="pagination-wrapper">
            <ul class="pagination">`;

    // Кнопка "Предыдущая"
    if (pagination.current_page > 1) {
      html += `<li class="page-item">
                <a href="#" class="page-link pagination-link" data-page="${
                  pagination.current_page - 1
                }">Prev</a>
            </li>`;
    }

    // Номера страниц
    for (let i = 1; i <= pagination.total_pages; i++) {
      if (i === pagination.current_page) {
        html += `<li class="page-item active">
                    <span class="page-link">${i}</span>
                </li>`;
      } else {
        html += `<li class="page-item">
                    <a href="#" class="page-link pagination-link" data-page="${i}">${i}</a>
                </li>`;
      }
    }

    // Кнопка "Следующая"
    if (pagination.current_page < pagination.total_pages) {
      html += `<li class="page-item">
                <a href="#" class="page-link pagination-link" data-page="${
                  pagination.current_page + 1
                }">Next</a>
            </li>`;
    }

    html += `</ul></div>`;
    return html;
  }

  // Обработчики событий для фильтров, сортировки и пагинации
  function setupEventListeners() {
    // Сортировка
    document.querySelectorAll('.sort-select').forEach(select => {
      select.addEventListener('change', async function () {
        const [value, order] = this.value.split('|');
        const params = {
          ...getCurrentParams(),
          sortBy: value,
          sortOrder: order,
          page: 1, // Сброс на первую страницу при изменении сортировки
        };

        // Обновляем URL
        updateUrl(params);

        // Загружаем и обновляем данные
        const data = await loadServices(params);
        updateContent(data);
      });
    });

    // Пагинация
    document.querySelectorAll('.pagination-link').forEach(link => {
      link.addEventListener('click', async function (e) {
        e.preventDefault();

        const page = this.dataset.page;
        const params = {
          ...getCurrentParams(),
          page: page,
        };

        // Обновляем URL
        updateUrl(params);

        // Загружаем и обновляем данные
        const data = await loadServices(params);
        updateContent(data);

        // Прокрутка к верху списка услуг
        document.getElementById('services-container').scrollIntoView({ behavior: 'smooth' });
      });
    });

    // Фильтры (категории, бонусы)
    document.querySelectorAll('.filter-select').forEach(select => {
      select.addEventListener('change', async function () {
        const filterType = this.dataset.filterType; // 'category', 'bonuses'
        const params = {
          ...getCurrentParams(),
          [filterType]: this.value,
          page: 1, // Сброс на первую страницу при изменении фильтра
        };

        // Обновляем URL
        updateUrl(params);

        // Загружаем и обновляем данные
        const data = await loadServices(params);
        updateContent(data);
      });
    });

    // Поиск
    const searchForm = document.querySelector('.search-form');
    if (searchForm) {
      searchForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const searchInput = this.querySelector('input[name="search"]');
        const params = {
          ...getCurrentParams(),
          search: searchInput.value,
          page: 1, // Сброс на первую страницу при поиске
        };

        // Обновляем URL
        updateUrl(params);

        // Загружаем и обновляем данные
        const data = await loadServices(params);
        updateContent(data);
      });
    }

    // Элементы для настройки перPage
    document.querySelectorAll('.perpage-select').forEach(select => {
      select.addEventListener('change', async function () {
        const params = {
          ...getCurrentParams(),
          perPage: this.value,
          page: 1, // Сброс на первую страницу при изменении количества элементов на странице
        };

        // Обновляем URL
        updateUrl(params);

        // Загружаем и обновляем данные
        const data = await loadServices(params);
        updateContent(data);
      });
    });
  }

  // Функция для получения текущих параметров URL
  function getCurrentParams() {
    const params = {};
    const searchParams = new URLSearchParams(window.location.search);

    for (const [key, value] of searchParams.entries()) {
      params[key] = value;
    }

    return params;
  }

  // Инициализация
  setupEventListeners();

  // Обработка навигации по истории (кнопки Назад/Вперед)
  window.addEventListener('popstate', async function () {
    const params = getCurrentParams();
    const data = await loadServices(params);
    updateContent(data);
  });
});
