// 2. REMOTE VALIDATOR - Утилита для удаленной валидации
// ============================================================================
const RemoteValidator = {
  cache: new Map(),
  pendingRequests: new Map(),

  // Настройки по умолчанию
  defaultOptions: {
    timeout: 5000, // 5 секунд
    cacheTime: 30000, // 30 секунд
    maxCacheSize: 100, // Максимум записей в кэше
    retryAttempts: 1, // Количество повторных попыток
    enableLogging: false, // Отладочное логирование
  },

  async validate(url, data, options = {}) {
    const config = { ...this.defaultOptions, ...options };
    const cacheKey = this.generateCacheKey(url, data);

    this.log('Validating:', { url, data, cacheKey }, config);

    // Проверяем кэш
    if (this.cache.has(cacheKey)) {
      this.log('Cache hit:', cacheKey, config);
      return this.cache.get(cacheKey);
    }

    // Предотвращаем дублирование запросов
    if (this.pendingRequests.has(cacheKey)) {
      this.log('Request already pending, waiting...', cacheKey, config);
      return this.pendingRequests.get(cacheKey);
    }

    // Очищаем кэш если он переполнен
    this.cleanupCache(config);

    const requestPromise = this.makeRequest(url, data, config);
    this.pendingRequests.set(cacheKey, requestPromise);

    try {
      const result = await requestPromise;

      // Кэшируем только успешные результаты
      if (result.networkSuccess) {
        this.cache.set(cacheKey, result);
        this.log('Result cached:', { cacheKey, result }, config);

        // Очищаем кэш через время
        setTimeout(() => {
          this.cache.delete(cacheKey);
          this.log('Cache expired:', cacheKey, config);
        }, config.cacheTime);
      }

      return result;
    } finally {
      this.pendingRequests.delete(cacheKey);
    }
  },

  async makeRequest(url, data, config) {
    let attempt = 0;
    let lastError = null;

    while (attempt <= config.retryAttempts) {
      try {
        this.log(`Request attempt ${attempt + 1}:`, { url, data }, config);

        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), config.timeout);

        const response = await fetch(url, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': this.getCSRFToken(),
            Accept: 'application/json',
          },
          body: JSON.stringify(data),
          signal: controller.signal,
        });

        clearTimeout(timeoutId);

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const result = await response.json();

        this.log('Request successful:', result, config);

        return {
          isValid: result.valid || result.success || false,
          errors: this.formatErrors(result),
          networkSuccess: true,
          response: result,
        };
      } catch (error) {
        lastError = error;
        attempt++;

        this.log(`Request failed (attempt ${attempt}):`, error.message, config);

        // Если это timeout или сетевая ошибка, пробуем еще раз
        if (attempt <= config.retryAttempts && this.isRetryableError(error)) {
          await this.delay(Math.pow(2, attempt) * 1000); // Exponential backoff
          continue;
        }

        break;
      }
    }

    // Все попытки неудачны
    this.log('All attempts failed:', lastError.message, config);

    return {
      isValid: true, // Fail gracefully - не блокируем форму
      errors: [],
      networkSuccess: false,
      error: lastError.message,
    };
  },

  // Вспомогательные методы
  generateCacheKey(url, data) {
    return `${url}_${JSON.stringify(data)}`;
  },

  formatErrors(result) {
    if (result.valid || result.success) return [];

    // Поддержка разных форматов ответов
    if (result.errors) {
      return Array.isArray(result.errors)
        ? result.errors.map(err => ({ message: err.message || err }))
        : [{ message: result.errors }];
    }

    if (result.message) {
      return [{ message: result.message }];
    }

    return [{ message: 'Validation failed' }];
  },

  isRetryableError(error) {
    return (
      error.name === 'AbortError' ||
      error.message.includes('fetch') ||
      error.message.includes('NetworkError') ||
      error.message.includes('Failed to fetch')
    );
  },

  getCSRFToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
  },

  cleanupCache(config) {
    if (this.cache.size >= config.maxCacheSize) {
      // Удаляем старейшие записи (простая FIFO)
      const keysToDelete = Array.from(this.cache.keys()).slice(
        0,
        Math.floor(config.maxCacheSize / 2)
      );
      keysToDelete.forEach(key => this.cache.delete(key));
      this.log(`Cache cleanup: removed ${keysToDelete.length} entries`, '', config);
    }
  },

  delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
  },

  log(message, data, config) {
    if (config.enableLogging) {
      console.log(`[RemoteValidator] ${message}`, data);
    }
  },

  // Публичные методы для управления кэшем
  clearCache() {
    this.cache.clear();
    this.log('Cache cleared manually');
  },

  getCacheStats() {
    return {
      cacheSize: this.cache.size,
      pendingRequests: this.pendingRequests.size,
      cacheKeys: Array.from(this.cache.keys()),
    };
  },
};

export default RemoteValidator;
