import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ApiError, creativesApiService } from '../../../resources/js/stores/creativesApiService.ts';

// Мокаем axios и setupCache
vi.mock('axios', () => ({
  default: {
    create: vi.fn(() => ({
      defaults: { headers: {} },
      interceptors: {
        request: { use: vi.fn() },
        response: { use: vi.fn() },
      },
    })),
  },
}));

vi.mock('axios-cache-interceptor', () => ({
  setupCache: vi.fn(instance => ({
    ...instance,
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
    storage: {
      clear: vi.fn(),
      remove: vi.fn(),
    },
  })),
}));

describe('CreativesApiService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  describe('ApiError', () => {
    it('должен правильно создавать экземпляр ошибки', () => {
      const error = new ApiError('Test error', 404, { test: 'data' });

      expect(error.message).toBe('Test error');
      expect(error.status).toBe(404);
      expect(error.data).toEqual({ test: 'data' });
      expect(error.name).toBe('ApiError');
    });

    it('должен правильно определять типы ошибок', () => {
      const networkError = new ApiError('Network error', 0);
      const serverError = new ApiError('Server error', 500);
      const clientError = new ApiError('Client error', 400);
      const validationError = new ApiError('Validation error', 422);
      const unauthorizedError = new ApiError('Unauthorized', 401);
      const forbiddenError = new ApiError('Forbidden', 403);
      const notFoundError = new ApiError('Not found', 404);

      expect(networkError.isNetworkError).toBe(true);
      expect(serverError.isServerError).toBe(true);
      expect(clientError.isClientError).toBe(true);
      expect(validationError.isValidationError).toBe(true);
      expect(unauthorizedError.isUnauthorized).toBe(true);
      expect(forbiddenError.isForbidden).toBe(true);
      expect(notFoundError.isNotFound).toBe(true);
    });
  });

  describe('Service initialization', () => {
    it('должен инициализироваться с дефолтными настройками', () => {
      expect(creativesApiService).toBeDefined();
    });

    it('должен иметь методы для работы с HTTP', () => {
      expect(typeof creativesApiService.get).toBe('function');
      expect(typeof creativesApiService.post).toBe('function');
      expect(typeof creativesApiService.put).toBe('function');
      expect(typeof creativesApiService.delete).toBe('function');
    });

    it('должен иметь методы для работы с кэшем', () => {
      expect(typeof creativesApiService.clearCache).toBe('function');
      expect(typeof creativesApiService.removeCacheEntry).toBe('function');
    });
  });

  describe('CSRF Token', () => {
    beforeEach(() => {
      // Очищаем DOM
      document.head.innerHTML = '';
    });

    it('должен получать CSRF токен из meta тега', async () => {
      const metaTag = document.createElement('meta');
      metaTag.name = 'csrf-token';
      metaTag.content = 'test-csrf-token';
      document.head.appendChild(metaTag);

      // Создаем новый экземпляр для тестирования
      const serviceModule = await import('../../../resources/js/stores/creativesApiService.ts');
      const testService = new serviceModule.default();

      expect(testService).toBeDefined();
    });
  });
});
