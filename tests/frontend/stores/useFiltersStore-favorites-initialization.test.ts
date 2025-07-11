/**
 * Тесты для инициализации избранного в useFiltersStore
 * Проверяет корректность загрузки списка избранных креативов при инициализации
 */

import { useCreativesFiltersStore } from '@/stores/useFiltersStore';
import { createPinia, setActivePinia } from 'pinia';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

// Мокаем axios
const mockAxios = {
  get: vi.fn(),
  post: vi.fn(),
  delete: vi.fn(),
};

// Устанавливаем mock axios в window
Object.defineProperty(window, 'axios', {
  value: mockAxios,
  writable: true,
});

describe('useFiltersStore - Favorites Initialization', () => {
  let store: ReturnType<typeof useCreativesFiltersStore>;

  beforeEach(() => {
    setActivePinia(createPinia());
    store = useCreativesFiltersStore();
    
    // Сбрасываем моки
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.clearAllMocks();
  });

  describe('loadFavoritesIds', () => {
    it('должен загружать список избранных ID при успешном ответе API', async () => {
      // Arrange
      const mockFavoritesResponse = {
        data: {
          data: {
            ids: [123, 456, 789],
            count: 3,
            lastUpdated: '2024-01-01T00:00:00.000Z'
          }
        }
      };

      mockAxios.get.mockResolvedValueOnce(mockFavoritesResponse);

      // Act
      await store.loadFavoritesIds();

      // Assert
      expect(mockAxios.get).toHaveBeenCalledWith('/api/creatives/favorites/ids');
      expect(store.favoritesItems).toEqual([123, 456, 789]);
      expect(store.favoritesCount).toBe(3);
      expect(store.isFavoritesLoading).toBe(false);
    });

    it('должен обрабатывать пустой список избранного', async () => {
      // Arrange
      const mockEmptyResponse = {
        data: {
          data: {
            ids: [],
            count: 0,
            lastUpdated: '2024-01-01T00:00:00.000Z'
          }
        }
      };

      mockAxios.get.mockResolvedValueOnce(mockEmptyResponse);

      // Act
      await store.loadFavoritesIds();

      // Assert
      expect(store.favoritesItems).toEqual([]);
      expect(store.favoritesCount).toBe(0);
    });

    it('должен сбрасывать состояние при ошибке API', async () => {
      // Arrange
      const mockError = new Error('API Error');
      mockAxios.get.mockRejectedValueOnce(mockError);

      // Act & Assert
      await expect(store.loadFavoritesIds()).rejects.toThrow('API Error');
      expect(store.favoritesItems).toEqual([]);
      expect(store.favoritesCount).toBe(0);
      expect(store.isFavoritesLoading).toBe(false);
    });

    it('не должен делать запрос если уже идет загрузка', async () => {
      // Arrange
      store.isFavoritesLoading = true;

      // Act
      await store.loadFavoritesIds();

      // Assert
      expect(mockAxios.get).not.toHaveBeenCalled();
    });
  });

  describe('initializeFilters с избранным', () => {
    it('должен загружать избранное при инициализации', async () => {
      // Arrange
      const mockFavoritesResponse = {
        data: {
          data: {
            ids: [111, 222],
            count: 2,
            lastUpdated: '2024-01-01T00:00:00.000Z'
          }
        }
      };

      mockAxios.get.mockResolvedValueOnce(mockFavoritesResponse);

      // Act
      await store.initializeFilters();

      // Assert
      expect(mockAxios.get).toHaveBeenCalledWith('/api/creatives/favorites/ids');
      expect(store.favoritesItems).toEqual([111, 222]);
      expect(store.favoritesCount).toBe(2);
      expect(store.isInitialized).toBe(true);
    });

    it('должен продолжать инициализацию даже если избранное не загрузилось', async () => {
      // Arrange
      const mockError = new Error('Unauthorized');
      mockAxios.get.mockRejectedValueOnce(mockError);

      // Act
      await store.initializeFilters();

      // Assert
      expect(store.isInitialized).toBe(true);
      expect(store.favoritesItems).toEqual([]);
      expect(store.favoritesCount).toBe(0);
    });
  });

  describe('isFavoriteCreative computed', () => {
    it('должен корректно определять избранные креативы после загрузки', async () => {
      // Arrange
      const mockFavoritesResponse = {
        data: {
          data: {
            ids: [100, 200, 300],
            count: 3,
            lastUpdated: '2024-01-01T00:00:00.000Z'
          }
        }
      };

      mockAxios.get.mockResolvedValueOnce(mockFavoritesResponse);
      await store.loadFavoritesIds();

      // Act & Assert
      expect(store.isFavoriteCreative(100)).toBe(true);
      expect(store.isFavoriteCreative(200)).toBe(true);
      expect(store.isFavoriteCreative(300)).toBe(true);
      expect(store.isFavoriteCreative(400)).toBe(false);
      expect(store.isFavoriteCreative(999)).toBe(false);
    });

    it('должен возвращать false для всех креативов если избранное не загружено', () => {
      // Arrange - избранное не загружено (пустой массив)
      
      // Act & Assert
      expect(store.isFavoriteCreative(123)).toBe(false);
      expect(store.isFavoriteCreative(456)).toBe(false);
    });
  });

  describe('События избранного', () => {
    it('должен эмитировать событие при успешной загрузке избранного', async () => {
      // Arrange
      const mockFavoritesResponse = {
        data: {
          data: {
            ids: [1, 2, 3],
            count: 3,
            lastUpdated: '2024-01-01T00:00:00.000Z'
          }
        }
      };

      mockAxios.get.mockResolvedValueOnce(mockFavoritesResponse);

      const eventSpy = vi.fn();
      document.addEventListener('creatives:favorites-loaded', eventSpy);

      // Act
      await store.loadFavoritesIds();

      // Assert
      expect(eventSpy).toHaveBeenCalledWith(
        expect.objectContaining({
          detail: expect.objectContaining({
            count: 3,
            ids: [1, 2, 3],
            timestamp: expect.any(String)
          })
        })
      );

      // Cleanup
      document.removeEventListener('creatives:favorites-loaded', eventSpy);
    });
  });

  describe('Интеграция с существующими методами избранного', () => {
    it('должен обновлять локальное состояние при добавлении в избранное', async () => {
      // Arrange
      const mockAddResponse = {
        data: {
          data: {
            creativeId: 999,
            isFavorite: true,
            totalFavorites: 4
          }
        }
      };

      mockAxios.post.mockResolvedValueOnce(mockAddResponse);

      // Предварительно загружаем избранное
      store.favoritesItems = [100, 200, 300];
      store.favoritesCount = 3;

      // Act
      await store.addToFavorites(999);

      // Assert
      expect(store.favoritesItems).toContain(999);
      expect(store.favoritesCount).toBe(4);
      expect(store.isFavoriteCreative(999)).toBe(true);
    });

    it('должен обновлять локальное состояние при удалении из избранного', async () => {
      // Arrange
      const mockRemoveResponse = {
        data: {
          data: {
            creativeId: 200,
            isFavorite: false,
            totalFavorites: 2
          }
        }
      };

      mockAxios.delete.mockResolvedValueOnce(mockRemoveResponse);

      // Предварительно загружаем избранное
      store.favoritesItems = [100, 200, 300];
      store.favoritesCount = 3;

      // Act
      await store.removeFromFavorites(200);

      // Assert
      expect(store.favoritesItems).not.toContain(200);
      expect(store.favoritesCount).toBe(2);
      expect(store.isFavoriteCreative(200)).toBe(false);
    });
  });
}); 