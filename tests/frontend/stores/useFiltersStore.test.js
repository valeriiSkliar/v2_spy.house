describe('Блокировка повторных запросов избранного', () => {
  let store;
  let axiosPostSpy;
  let axiosDeleteSpy;

  beforeEach(async () => {
    store = useCreativesFiltersStore();

    // Мокаем axios
    axiosPostSpy = vi.spyOn(window.axios, 'post').mockResolvedValue({
      data: { data: { totalFavorites: 1 } },
    });
    axiosDeleteSpy = vi.spyOn(window.axios, 'delete').mockResolvedValue({
      data: { data: { totalFavorites: 0 } },
    });

    await store.initializeFilters();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  test('должен блокировать повторные запросы добавления в избранное для одного креатива', async () => {
    const creativeId = 123;

    // Запускаем два запроса одновременно
    const promise1 = store.addToFavorites(creativeId);
    const promise2 = store.addToFavorites(creativeId);

    await Promise.all([promise1, promise2]);

    // Должен быть выполнен только один API запрос
    expect(axiosPostSpy).toHaveBeenCalledTimes(1);
    expect(axiosPostSpy).toHaveBeenCalledWith(`/api/creatives/${creativeId}/favorite`);
  });

  test('должен блокировать повторные запросы удаления из избранного для одного креатива', async () => {
    const creativeId = 123;

    // Сначала добавляем в избранное
    store.favoritesItems.push(creativeId);

    // Запускаем два запроса удаления одновременно
    const promise1 = store.removeFromFavorites(creativeId);
    const promise2 = store.removeFromFavorites(creativeId);

    await Promise.all([promise1, promise2]);

    // Должен быть выполнен только один API запрос
    expect(axiosDeleteSpy).toHaveBeenCalledTimes(1);
    expect(axiosDeleteSpy).toHaveBeenCalledWith(`/api/creatives/${creativeId}/favorite`);
  });

  test('должен разрешать параллельные запросы для разных креативов', async () => {
    const creativeId1 = 123;
    const creativeId2 = 456;

    // Запускаем запросы для разных креативов одновременно
    const promise1 = store.addToFavorites(creativeId1);
    const promise2 = store.addToFavorites(creativeId2);

    await Promise.all([promise1, promise2]);

    // Должно быть выполнено два API запроса
    expect(axiosPostSpy).toHaveBeenCalledTimes(2);
    expect(axiosPostSpy).toHaveBeenCalledWith(`/api/creatives/${creativeId1}/favorite`);
    expect(axiosPostSpy).toHaveBeenCalledWith(`/api/creatives/${creativeId2}/favorite`);
  });

  test('должен проверять состояние загрузки конкретного креатива', () => {
    const creativeId = 123;

    // Изначально не должно быть состояния загрузки
    expect(store.isFavoriteLoading(creativeId)).toBe(false);

    // Устанавливаем состояние загрузки
    store.favoritesLoadingMap.set(creativeId, true);
    expect(store.isFavoriteLoading(creativeId)).toBe(true);

    // Убираем состояние загрузки
    store.favoritesLoadingMap.delete(creativeId);
    expect(store.isFavoriteLoading(creativeId)).toBe(false);
  });

  test('должен очищать состояние загрузки после завершения запроса', async () => {
    const creativeId = 123;

    const promise = store.addToFavorites(creativeId);

    // Во время выполнения запроса должно быть состояние загрузки
    expect(store.isFavoriteLoading(creativeId)).toBe(true);

    await promise;

    // После завершения запроса состояние загрузки должно быть очищено
    expect(store.isFavoriteLoading(creativeId)).toBe(false);
  });

  test('должен очищать состояние загрузки даже при ошибке', async () => {
    const creativeId = 123;

    // Мокаем ошибку API
    axiosPostSpy.mockRejectedValueOnce(new Error('API Error'));

    try {
      await store.addToFavorites(creativeId);
    } catch (error) {
      // Ожидаем ошибку
    }

    // После ошибки состояние загрузки должно быть очищено
    expect(store.isFavoriteLoading(creativeId)).toBe(false);
  });

  test('должен логировать предупреждения при попытке повторного запроса', async () => {
    const creativeId = 123;
    const consoleSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});

    // Запускаем первый запрос
    const promise1 = store.addToFavorites(creativeId);

    // Пытаемся запустить второй запрос
    await store.addToFavorites(creativeId);

    // Должно быть предупреждение
    expect(consoleSpy).toHaveBeenCalledWith(
      `Добавление в избранное для креатива ${creativeId} уже выполняется`
    );

    await promise1;
    consoleSpy.mockRestore();
  });
});
