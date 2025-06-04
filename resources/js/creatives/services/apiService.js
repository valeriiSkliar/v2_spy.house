class ApiService {
    constructor() {
        this.baseUrl = '/api';
        this.cache = new Map();
        this.cacheTimeout = 5 * 60 * 1000; // 5 минут
        this.requestQueue = new Map();
    }
    
    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }
    
    getDefaultHeaders() {
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': this.getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest'
        };
    }
    
    getCacheKey(url, method, body) {
        return `${method}:${url}:${JSON.stringify(body || {})}`;
    }
    
    getCached(cacheKey) {
        const cached = this.cache.get(cacheKey);
        if (!cached) return null;
        
        const { data, timestamp } = cached;
        if (Date.now() - timestamp > this.cacheTimeout) {
            this.cache.delete(cacheKey);
            return null;
        }
        
        return data;
    }
    
    setCache(cacheKey, data) {
        if (this.cache.size > 100) {
            const firstKey = this.cache.keys().next().value;
            this.cache.delete(firstKey);
        }
        
        this.cache.set(cacheKey, {
            data,
            timestamp: Date.now()
        });
    }
    
    clearCache() {
        this.cache.clear();
    }
    
    async request(url, options = {}) {
        const {
            method = 'GET',
            body,
            headers = {},
            cache = true,
            deduplicate = true
        } = options;
        
        const fullUrl = url.startsWith('http') ? url : `${this.baseUrl}${url}`;
        const requestHeaders = { ...this.getDefaultHeaders(), ...headers };
        
        // Проверяем кэш для GET запросов
        if (method === 'GET' && cache) {
            const cacheKey = this.getCacheKey(fullUrl, method, body);
            const cached = this.getCached(cacheKey);
            if (cached) {
                return Promise.resolve(cached);
            }
        }
        
        // Дедупликация запросов
        if (deduplicate) {
            const requestKey = this.getCacheKey(fullUrl, method, body);
            if (this.requestQueue.has(requestKey)) {
                return this.requestQueue.get(requestKey);
            }
        }
        
        const requestOptions = {
            method,
            headers: requestHeaders,
            credentials: 'same-origin'
        };
        
        if (body && method !== 'GET') {
            requestOptions.body = typeof body === 'string' ? body : JSON.stringify(body);
        }
        
        const requestPromise = fetch(fullUrl, requestOptions)
            .then(async response => {
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new ApiError(
                        errorData.message || `HTTP ${response.status}: ${response.statusText}`,
                        response.status,
                        errorData
                    );
                }
                
                return response.json();
            })
            .then(data => {
                // Кэшируем GET запросы
                if (method === 'GET' && cache) {
                    const cacheKey = this.getCacheKey(fullUrl, method, body);
                    this.setCache(cacheKey, data);
                }
                
                return data;
            })
            .catch(error => {
                if (error instanceof ApiError) {
                    throw error;
                }
                
                throw new ApiError(
                    'Ошибка сети или сервера',
                    0,
                    { originalError: error.message }
                );
            })
            .finally(() => {
                if (deduplicate) {
                    const requestKey = this.getCacheKey(fullUrl, method, body);
                    this.requestQueue.delete(requestKey);
                }
            });
        
        if (deduplicate) {
            const requestKey = this.getCacheKey(fullUrl, method, body);
            this.requestQueue.set(requestKey, requestPromise);
        }
        
        return requestPromise;
    }
    
    get(url, options = {}) {
        return this.request(url, { ...options, method: 'GET' });
    }
    
    post(url, body, options = {}) {
        return this.request(url, { ...options, method: 'POST', body });
    }
    
    put(url, body, options = {}) {
        return this.request(url, { ...options, method: 'PUT', body });
    }
    
    patch(url, body, options = {}) {
        return this.request(url, { ...options, method: 'PATCH', body });
    }
    
    delete(url, options = {}) {
        return this.request(url, { ...options, method: 'DELETE' });
    }
    
    // Специфичные методы для креативов
    async getCreatives(params = {}) {
        return this.post('/creatives', params, { cache: true });
    }
    
    async getCreative(id) {
        return this.get(`/creatives/${id}`, { cache: true });
    }
    
    async updateCreative(id, data) {
        const result = await this.put(`/creatives/${id}`, data, { cache: false });
        this.clearCache(); // Очищаем кэш после обновления
        return result;
    }
    
    async deleteCreative(id) {
        const result = await this.delete(`/creatives/${id}`, { cache: false });
        this.clearCache(); // Очищаем кэш после удаления
        return result;
    }
    
    async deleteCreatives(ids) {
        const result = await this.delete('/creatives', { 
            body: { ids },
            cache: false 
        });
        this.clearCache(); // Очищаем кэш после удаления
        return result;
    }
    
    async downloadCreatives(ids) {
        const response = await fetch(`${this.baseUrl}/creatives/download`, {
            method: 'POST',
            headers: this.getDefaultHeaders(),
            credentials: 'same-origin',
            body: JSON.stringify({ ids })
        });
        
        if (!response.ok) {
            throw new ApiError(
                'Ошибка при скачивании файлов',
                response.status
            );
        }
        
        return response.blob();
    }
    
    async uploadCreative(formData) {
        const headers = {
            'X-CSRF-TOKEN': this.getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest'
        };
        
        const response = await fetch(`${this.baseUrl}/creatives`, {
            method: 'POST',
            headers,
            credentials: 'same-origin',
            body: formData
        });
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new ApiError(
                errorData.message || 'Ошибка при загрузке файла',
                response.status,
                errorData
            );
        }
        
        const result = await response.json();
        this.clearCache(); // Очищаем кэш после загрузки
        return result;
    }
    
    // Методы для работы с категориями и тегами
    async getCategories() {
        return this.get('/creatives/categories', { cache: true });
    }
    
    async getTags() {
        return this.get('/creatives/tags', { cache: true });
    }
    
    async getStats() {
        return this.get('/creatives/stats', { cache: true });
    }
}

class ApiError extends Error {
    constructor(message, status, data = {}) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.data = data;
    }
    
    get isNetworkError() {
        return this.status === 0;
    }
    
    get isServerError() {
        return this.status >= 500;
    }
    
    get isClientError() {
        return this.status >= 400 && this.status < 500;
    }
    
    get isValidationError() {
        return this.status === 422;
    }
    
    get isUnauthorized() {
        return this.status === 401;
    }
    
    get isForbidden() {
        return this.status === 403;
    }
    
    get isNotFound() {
        return this.status === 404;
    }
}

// Экспортируем синглтон
export const apiService = new ApiService();
export { ApiError };