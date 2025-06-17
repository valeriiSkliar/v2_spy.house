/**
 * Обновляет URL браузера без перезагрузки страницы
 * @param {Object} params - Объект с параметрами для обновления URL
 */
export function updateUrlWithoutReload(params) {
  const url = new URL(window.location.href);
  const searchParams = new URLSearchParams(url.search);

  // Обновляем параметры
  Object.keys(params).forEach(key => {
    if (params[key] !== null && params[key] !== '') {
      searchParams.set(key, params[key]);
    } else {
      searchParams.delete(key);
    }
  });

  // Удаляем page=1 для чистоты URL
  if (searchParams.get('page') === '1') {
    searchParams.delete('page');
  }

  const newUrl = `${url.pathname}${searchParams.toString() ? '?' + searchParams.toString() : ''}`;
  window.history.pushState({}, '', newUrl);
}

/**
 * Обновляет URL браузера полным URL
 * @param {string} newUrl - Новый URL
 */
export function updateBrowserUrl(newUrl) {
  window.history.pushState({}, '', newUrl);
}
