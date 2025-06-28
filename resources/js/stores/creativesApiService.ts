import axios, { type AxiosInstance } from 'axios';
import { setupCache, type CacheRequestConfig } from 'axios-cache-interceptor';

// Интерфейсы для типизации
interface ApiServiceConfig {
  baseUrl?: string;
  timeout?: number;
  debug?: boolean;
}

interface ApiResponse<T = any> {
  data: T;
  message?: string;
  success: boolean;
}

// Кастомный класс ошибок API
class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public data: any = {}
  ) {
    super(message);
    this.name = 'ApiError';
  }

  get isNetworkError(): boolean {
    return this.status === 0;
  }

  get isServerError(): boolean {
    return this.status >= 500;
  }

  get isClientError(): boolean {
    return this.status >= 400 && this.status < 500;
  }

  get isValidationError(): boolean {
    return this.status === 422;
  }

  get isUnauthorized(): boolean {
    return this.status === 401;
  }

  get isForbidden(): boolean {
    return this.status === 403;
  }

  get isNotFound(): boolean {
    return this.status === 404;
  }
}

// Основной класс API сервиса
class CreativesApiService {
  private axiosInstance: AxiosInstance;

  constructor(config: ApiServiceConfig = {}) {
    // Создаем базовый Axios instance
    const baseInstance = axios.create({
      baseURL: config.baseUrl || '/api',
      timeout: config.timeout || 30000,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    // Применяем cache interceptor
    this.axiosInstance = setupCache(baseInstance, {
      // Кэшируем только GET запросы по умолчанию
      methods: ['get'],
      // Время жизни кэша - 5 минут
      ttl: 5 * 60 * 1000,
      // Включаем отладку если нужно
      debug: config.debug ? console.log : undefined
    });

    this.setupInterceptors();
  }

  private setupInterceptors(): void {
    // Request interceptor для добавления CSRF токена
    this.axiosInstance.interceptors.request.use(
      (config: any) => {
        const token = this.getCsrfToken();
        if (token) {
          config.headers['X-CSRF-TOKEN'] = token;
        }
        return config;
      },
      (error: any) => Promise.reject(error)
    );

    // Response interceptor для обработки ответов
    this.axiosInstance.interceptors.response.use(
      (response: any) => response,
      (error: any) => {
        if (error.response) {
          // Есть ответ от сервера с ошибкой
          throw new ApiError(
            error.response.data?.message || `HTTP ${error.response.status}: ${error.response.statusText}`,
            error.response.status,
            error.response.data
          );
        } else if (error.request) {
          // Запрос был отправлен но ответа нет
          throw new ApiError('Ошибка сети или сервер недоступен', 0, { originalError: error.message });
        } else {
          // Ошибка при настройке запроса
          throw new ApiError('Ошибка конфигурации запроса', 0, { originalError: error.message });
        }
      }
    );
  }

  private getCsrfToken(): string | null {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || null;
  }

  // Базовые HTTP методы
  async get<T = any>(url: string, config?: CacheRequestConfig): Promise<ApiResponse<T>> {
    const response = await this.axiosInstance.get<ApiResponse<T>>(url, config);
    return response.data;
  }

  async post<T = any>(url: string, data?: any, config?: CacheRequestConfig): Promise<ApiResponse<T>> {
    const response = await this.axiosInstance.post<ApiResponse<T>>(url, data, config);
    return response.data;
  }

  async put<T = any>(url: string, data?: any, config?: CacheRequestConfig): Promise<ApiResponse<T>> {
    const response = await this.axiosInstance.put<ApiResponse<T>>(url, data, config);
    return response.data;
  }

  async delete<T = any>(url: string, config?: CacheRequestConfig): Promise<ApiResponse<T>> {
    const response = await this.axiosInstance.delete<ApiResponse<T>>(url, config);
    return response.data;
  }

  // Метод для очистки кэша
  clearCache(): void {
    (this.axiosInstance as any).storage?.clear();
  }

  // Метод для удаления конкретной записи из кэша
  async removeCacheEntry(key: string): Promise<void> {
    await (this.axiosInstance as any).storage?.remove(key);
  }
}

// Экспортируем синглтон сервиса
export const creativesApiService = new CreativesApiService({
  debug: import.meta.env.DEV // Включаем отладку только в dev режиме
});

export { ApiError, type ApiResponse, type ApiServiceConfig };
export default CreativesApiService; 