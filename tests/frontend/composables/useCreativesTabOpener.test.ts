import type { TabOpenEventDetail } from '@/composables/useCreativesTabOpener';
import { useCreativesTabOpener } from '@/composables/useCreativesTabOpener';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

// Мокаем window.open
const mockWindowOpen = vi.fn();

describe('useCreativesTabOpener', () => {
  let tabOpener: ReturnType<typeof useCreativesTabOpener>;
  let cleanup: (() => void) | undefined;

  beforeEach(() => {
    tabOpener = useCreativesTabOpener();
    
    // Мокаем window.open
    Object.defineProperty(window, 'open', {
      value: mockWindowOpen,
      writable: true,
    });

    // Очищаем моки
    mockWindowOpen.mockClear();
    
    // Очищаем event listeners
    vi.clearAllMocks();
  });

  afterEach(() => {
    // Очищаем слушатели событий
    if (cleanup) {
      cleanup();
      cleanup = undefined;
    }
  });

  describe('isValidUrl', () => {
    it('должен возвращать true для валидных URL', () => {
      expect(tabOpener.isValidUrl('https://example.com')).toBe(true);
      expect(tabOpener.isValidUrl('http://test.com/path')).toBe(true);
      expect(tabOpener.isValidUrl('https://landing.page.com?param=value')).toBe(true);
    });

    it('должен возвращать false для невалидных URL', () => {
      expect(tabOpener.isValidUrl('')).toBe(false);
      expect(tabOpener.isValidUrl('not-a-url')).toBe(false);
    });
  });

  describe('openInNewTab', () => {
    it('должен открывать валидный URL в новой вкладке', () => {
      const mockWindow = { focus: vi.fn() };
      mockWindowOpen.mockReturnValue(mockWindow);

      const url = 'https://example.com/landing';
      tabOpener.openInNewTab(url);

      expect(mockWindowOpen).toHaveBeenCalledWith(url, '_blank', 'noopener,noreferrer');
    });

    it('должен выбрасывать ошибку для пустого URL', () => {
      expect(() => tabOpener.openInNewTab('')).toThrow('URL is required and must be a string');
    });

    it('должен выбрасывать ошибку для невалидного URL', () => {
      expect(() => tabOpener.openInNewTab('invalid-url')).toThrow('Invalid URL format: invalid-url');
    });

    it('должен логировать предупреждение если window.open возвращает null', () => {
      mockWindowOpen.mockReturnValue(null);
      const consoleSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});

      const url = 'https://example.com';
      
      // Не должно выбрасывать ошибку
      expect(() => tabOpener.openInNewTab(url)).not.toThrow();
      
      expect(consoleSpy).toHaveBeenCalledWith(
        `Popup may be blocked, but URL should still open: ${url}`
      );
      
      consoleSpy.mockRestore();
    });
  });

  describe('handleOpenInNewTab', () => {
    it('должен успешно обрабатывать событие с валидным URL', () => {
      const mockWindow = { focus: vi.fn() };
      mockWindowOpen.mockReturnValue(mockWindow);

      const mockDispatchEvent = vi.spyOn(document, 'dispatchEvent');
      const url = 'https://example.com/landing';

      const event = new CustomEvent<TabOpenEventDetail>('creatives:open-in-new-tab', {
        detail: { url },
      });

      tabOpener.handleOpenInNewTab(event);

      expect(mockWindowOpen).toHaveBeenCalledWith(url, '_blank', 'noopener,noreferrer');
      
      // Проверяем что диспетчилось событие успеха
      expect(mockDispatchEvent).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'creatives:open-in-new-tab-success',
          detail: expect.objectContaining({
            url,
            timestamp: expect.any(Number),
          }),
        })
      );
    });

    it('должен обрабатывать ошибку при невалидном URL', () => {
      const mockDispatchEvent = vi.spyOn(document, 'dispatchEvent');
      const invalidUrl = 'invalid-url';

      const event = new CustomEvent<TabOpenEventDetail>('creatives:open-in-new-tab', {
        detail: { url: invalidUrl },
      });

      tabOpener.handleOpenInNewTab(event);

      // Проверяем что диспетчилось событие ошибки
      expect(mockDispatchEvent).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'creatives:open-in-new-tab-error',
          detail: expect.objectContaining({
            url: invalidUrl,
            error: `Invalid URL format: ${invalidUrl}`,
            timestamp: expect.any(Number),
          }),
        })
      );
    });

    it('должен обрабатывать popup блокировку как успех', () => {
      mockWindowOpen.mockReturnValue(null);
      const mockDispatchEvent = vi.spyOn(document, 'dispatchEvent');
      const consoleSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});
      const url = 'https://example.com';

      const event = new CustomEvent<TabOpenEventDetail>('creatives:open-in-new-tab', {
        detail: { url },
      });

      tabOpener.handleOpenInNewTab(event);

      // Должно эмитировать событие успеха, даже если popup заблокирован
      expect(mockDispatchEvent).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'creatives:open-in-new-tab-success',
          detail: expect.objectContaining({
            url,
            timestamp: expect.any(Number),
          }),
        })
      );
      
      expect(consoleSpy).toHaveBeenCalledWith(
        `Popup may be blocked, but URL should still open: ${url}`
      );
      
      consoleSpy.mockRestore();
    });
  });

  describe('initializeTabOpener', () => {
    it('должен инициализировать слушатель событий', () => {
      const mockAddEventListener = vi.spyOn(document, 'addEventListener');
      
      cleanup = tabOpener.initializeTabOpener();
      
      expect(mockAddEventListener).toHaveBeenCalledWith(
        'creatives:open-in-new-tab',
        expect.any(Function)
      );
    });

    it('должен обрабатывать события через инициализированный слушатель', () => {
      const mockWindow = { focus: vi.fn() };
      mockWindowOpen.mockReturnValue(mockWindow);

      // Инициализируем слушатель
      cleanup = tabOpener.initializeTabOpener();

      const url = 'https://example.com/landing';
      
      // Диспетчим событие
      document.dispatchEvent(
        new CustomEvent<TabOpenEventDetail>('creatives:open-in-new-tab', {
          detail: { url },
        })
      );

      expect(mockWindowOpen).toHaveBeenCalledWith(url, '_blank', 'noopener,noreferrer');
    });

    it('должен возвращать функцию очистки', () => {
      const mockRemoveEventListener = vi.spyOn(document, 'removeEventListener');
      
      cleanup = tabOpener.initializeTabOpener();
      
      expect(typeof cleanup).toBe('function');
      
      // Вызываем очистку
      if (cleanup) {
        cleanup();
      }
      
      expect(mockRemoveEventListener).toHaveBeenCalledWith(
        'creatives:open-in-new-tab',
        expect.any(Function)
      );
    });
  });

  describe('интеграционные тесты', () => {
    it('должен работать end-to-end с реальными событиями', () => {
      const mockWindow = { focus: vi.fn() };
      mockWindowOpen.mockReturnValue(mockWindow);
      const mockDispatchEvent = vi.spyOn(document, 'dispatchEvent');

      // Инициализируем
      cleanup = tabOpener.initializeTabOpener();

      const url = 'https://landing.page.com';

      // Симулируем событие от компонента
      document.dispatchEvent(
        new CustomEvent<TabOpenEventDetail>('creatives:open-in-new-tab', {
          detail: { url },
        })
      );

      // Проверяем полный флоу
      expect(mockWindowOpen).toHaveBeenCalledWith(url, '_blank', 'noopener,noreferrer');
      expect(mockDispatchEvent).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'creatives:open-in-new-tab-success',
        })
      );
    });
  });
}); 