import { useCreativesDownloader } from '@/composables/useCreativesDownloader';
import type { Creative } from '@/types/creatives.d';
import { beforeEach, describe, expect, it, vi } from 'vitest';

// Мокаем глобальные функции
const mockCreateElement = vi.fn();
const mockAppendChild = vi.fn();
const mockRemoveChild = vi.fn();
const mockClick = vi.fn();
const mockCreateObjectURL = vi.fn();
const mockRevokeObjectURL = vi.fn();
const mockFetch = vi.fn();

// Настройка моков
Object.defineProperty(global, 'document', {
  value: {
    createElement: mockCreateElement,
    body: {
      appendChild: mockAppendChild,
      removeChild: mockRemoveChild,
    },
    addEventListener: vi.fn(),
    removeEventListener: vi.fn(),
    dispatchEvent: vi.fn(),
  },
  writable: true,
});

Object.defineProperty(global, 'window', {
  value: {
    URL: {
      createObjectURL: mockCreateObjectURL,
      revokeObjectURL: mockRevokeObjectURL,
    },
    location: {
      origin: 'https://example.com',
    },
  },
  writable: true,
});

Object.defineProperty(global, 'fetch', {
  value: mockFetch,
  writable: true,
});

// Тестовые данные
const mockCreative: Creative = {
  id: 123,
  title: 'Test Creative Title',
  description: 'Test Description',
  category: 'test',
  country: 'US',
  file_size: '1.5MB',
  icon_url: 'https://example.com/icon.png',
  main_image_url: 'https://example.com/image.jpg',
  video_url: 'https://example.com/video.mp4',
  landing_page_url: 'https://example.com/landing',
  created_at: '2024-01-01',
};

describe('useCreativesDownloader', () => {
  let downloader: ReturnType<typeof useCreativesDownloader>;

  beforeEach(() => {
    // Сбрасываем все моки
    vi.clearAllMocks();
    
    // Инициализируем композабл
    downloader = useCreativesDownloader();
    
    // Настраиваем базовые моки
    mockCreateElement.mockReturnValue({
      href: '',
      download: '',
      style: { display: '' },
      setAttribute: vi.fn(),
      click: mockClick,
    });
  });

  describe('getDownloadUrl', () => {
    it('should return main_image_url with auto type', () => {
      const result = downloader.getDownloadUrl(mockCreative);
      expect(result).toBe('https://example.com/image.jpg');
    });

    it('should return main_image_url with auto type explicitly', () => {
      const result = downloader.getDownloadUrl(mockCreative, 'auto');
      expect(result).toBe('https://example.com/image.jpg');
    });

    it('should return specific URL when type is specified', () => {
      expect(downloader.getDownloadUrl(mockCreative, 'icon_url')).toBe('https://example.com/icon.png');
      expect(downloader.getDownloadUrl(mockCreative, 'main_image_url')).toBe('https://example.com/image.jpg');
      expect(downloader.getDownloadUrl(mockCreative, 'video_url')).toBe('https://example.com/video.mp4');
      expect(downloader.getDownloadUrl(mockCreative, 'landing_page_url')).toBe('https://example.com/landing');
    });

    it('should fallback to auto selection when specified type is not available', () => {
      const creative = { ...mockCreative, icon_url: undefined };
      
      // При запросе недоступного типа должен использоваться автоматический выбор
      const result = downloader.getDownloadUrl(creative, 'icon_url');
      expect(result).toBe('https://example.com/image.jpg'); // fallback to main_image_url
    });

    it('should fallback to icon_url if main_image_url is not available with auto type', () => {
      const creative = { ...mockCreative, main_image_url: undefined };
      const result = downloader.getDownloadUrl(creative, 'auto');
      expect(result).toBe('https://example.com/icon.png');
    });

    it('should fallback to video_url if images are not available', () => {
      const creative = { 
        ...mockCreative, 
        main_image_url: undefined, 
        icon_url: undefined 
      };
      const result = downloader.getDownloadUrl(creative, 'auto');
      expect(result).toBe('https://example.com/video.mp4');
    });

    it('should fallback to landing_page_url if other urls are not available', () => {
      const creative = { 
        ...mockCreative, 
        main_image_url: undefined, 
        icon_url: undefined,
        video_url: undefined
      };
      const result = downloader.getDownloadUrl(creative, 'auto');
      expect(result).toBe('https://example.com/landing');
    });

    it('should return null if no urls are available', () => {
      const creative = { 
        ...mockCreative, 
        main_image_url: undefined, 
        icon_url: undefined,
        video_url: undefined,
        landing_page_url: undefined
      };
      const result = downloader.getDownloadUrl(creative, 'auto');
      expect(result).toBe(null);
    });
  });

  describe('generateFileName', () => {
    it('should generate proper filename from creative data with auto type', () => {
      const url = 'https://example.com/image.jpg';
      const result = downloader.generateFileName(mockCreative, url, 'auto');
      expect(result).toBe('Test_Creative_Title_123.jpg');
    });

    it('should generate filename with type prefix when specific type is used', () => {
      const url = 'https://example.com/icon.png';
      const result = downloader.generateFileName(mockCreative, url, 'icon_url');
      expect(result).toBe('Test_Creative_Title_icon_123.png');
    });

    it('should generate filename with different type prefixes', () => {
      expect(downloader.generateFileName(mockCreative, 'test.jpg', 'main_image_url')).toBe('Test_Creative_Title_main_image_123.jpg');
      expect(downloader.generateFileName(mockCreative, 'test.png', 'icon_url')).toBe('Test_Creative_Title_icon_123.png');
      expect(downloader.generateFileName(mockCreative, 'test.mp4', 'video_url')).toBe('Test_Creative_Title_video_123.mp4');
      expect(downloader.generateFileName(mockCreative, 'test.html', 'landing_page_url')).toBe('Test_Creative_Title_landing_page_123.html');
    });

    it('should clean invalid characters from title', () => {
      const creative = { ...mockCreative, title: 'Test<>:"/\\|?*Title' };
      const url = 'https://example.com/image.png';
      const result = downloader.generateFileName(creative, url, 'auto');
      expect(result).toBe('TestTitle_123.png');
    });

    it('should limit title length', () => {
      const longTitle = 'A'.repeat(100);
      const creative = { ...mockCreative, title: longTitle };
      const url = 'https://example.com/image.gif';
      const result = downloader.generateFileName(creative, url, 'auto');
      expect(result.length).toBeLessThanOrEqual(60); // 50 chars + _123.gif
      expect(result).toMatch(/^A+_123\.gif$/);
    });

    it('should use jpg as default extension', () => {
      const url = 'https://example.com/image';
      const result = downloader.generateFileName(mockCreative, url, 'auto');
      expect(result).toBe('Test_Creative_Title_123.jpg');
    });
  });

  describe('getContentType', () => {
    it('should detect image files correctly', () => {
      expect(downloader.getContentType('test.jpg')).toBe('image');
      expect(downloader.getContentType('test.png')).toBe('image');
      expect(downloader.getContentType('test.gif')).toBe('image');
      expect(downloader.getContentType('test.webp')).toBe('image');
    });

    it('should detect video files correctly', () => {
      expect(downloader.getContentType('test.mp4')).toBe('video');
      expect(downloader.getContentType('test.webm')).toBe('video');
      expect(downloader.getContentType('test.avi')).toBe('video');
    });

    it('should detect archive files correctly', () => {
      expect(downloader.getContentType('test.zip')).toBe('archive');
      expect(downloader.getContentType('test.rar')).toBe('archive');
      expect(downloader.getContentType('test.7z')).toBe('archive');
    });

    it('should return other for unknown extensions', () => {
      expect(downloader.getContentType('test.unknown')).toBe('other');
      expect(downloader.getContentType('test')).toBe('other');
    });
  });

  describe('downloadFile', () => {
    it('should create blob download with successful fetch', async () => {
      const mockBlob = new Blob(['test content']);
      const mockResponse = { 
        ok: true, 
        blob: vi.fn().mockResolvedValue(mockBlob) 
      };
      mockFetch.mockResolvedValueOnce(mockResponse);
      
      mockCreateObjectURL.mockReturnValue('blob:test-url');

      const mockLink = {
        href: '',
        download: '',
        style: { display: '' },
        setAttribute: vi.fn(),
        click: mockClick,
      };
      mockCreateElement.mockReturnValueOnce(mockLink);

      await downloader.downloadFile('https://example.com/file.jpg', 'test.jpg');

      expect(mockFetch).toHaveBeenCalledWith('https://example.com/file.jpg', expect.objectContaining({
        method: 'GET',
        mode: 'cors',
        cache: 'no-cache'
      }));
      expect(mockCreateObjectURL).toHaveBeenCalledWith(mockBlob);
      expect(mockLink.href).toBe('blob:test-url');
      expect(mockLink.download).toBe('test.jpg');
      expect(mockAppendChild).toHaveBeenCalledWith(mockLink);
      expect(mockClick).toHaveBeenCalled();
      expect(mockRemoveChild).toHaveBeenCalledWith(mockLink);
    });

    it('should handle fetch errors with fallback to new tab', async () => {
      // Fetch неудачен
      mockFetch.mockRejectedValueOnce(new Error('Network error'));

      const mockLink = {
        href: '',
        download: '',
        target: '',
        rel: '',
        style: { display: '' },
        setAttribute: vi.fn(),
        click: mockClick,
      };
      mockCreateElement.mockReturnValueOnce(mockLink);

      await downloader.downloadFile('https://example.com/file.jpg', 'test.jpg');

      expect(mockFetch).toHaveBeenCalledTimes(1);
      expect(mockLink.href).toBe('https://example.com/file.jpg');
      expect(mockLink.target).toBe('_blank');
      expect(mockLink.rel).toBe('noopener noreferrer');
      expect(mockClick).toHaveBeenCalled();
    });
  });

  describe('handleCreativeDownload', () => {
    it('should successfully download creative with auto type', async () => {
      const mockBlob = new Blob(['test content']);
      const mockResponse = { 
        ok: true, 
        blob: vi.fn().mockResolvedValue(mockBlob) 
      };
      mockFetch.mockResolvedValueOnce(mockResponse);
      mockCreateObjectURL.mockReturnValue('blob:test-url');

      const mockLink = {
        href: '',
        download: '',
        style: { display: '' },
        setAttribute: vi.fn(),
        click: mockClick,
      };
      mockCreateElement.mockReturnValueOnce(mockLink);

      await downloader.handleCreativeDownload(mockCreative, 'auto');

      expect(document.dispatchEvent).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'creatives:download-started',
          detail: expect.objectContaining({
            creative: mockCreative,
            type: 'auto'
          })
        })
      );
      
      expect(document.dispatchEvent).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'creatives:download-success',
          detail: expect.objectContaining({
            creative: mockCreative,
            type: 'auto'
          })
        })
      );
    });

    it('should successfully download creative with specific type', async () => {
      const mockBlob = new Blob(['test content']);
      const mockResponse = { 
        ok: true, 
        blob: vi.fn().mockResolvedValue(mockBlob) 
      };
      mockFetch.mockResolvedValueOnce(mockResponse);
      mockCreateObjectURL.mockReturnValue('blob:test-url');

      const mockLink = {
        href: '',
        download: '',
        style: { display: '' },
        setAttribute: vi.fn(),
        click: mockClick,
      };
      mockCreateElement.mockReturnValueOnce(mockLink);

      await downloader.handleCreativeDownload(mockCreative, 'icon_url');

      expect(document.dispatchEvent).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'creatives:download-started',
          detail: expect.objectContaining({
            creative: mockCreative,
            type: 'icon_url',
            downloadUrl: 'https://example.com/icon.png'
          })
        })
      );
      
      expect(document.dispatchEvent).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'creatives:download-success',
          detail: expect.objectContaining({
            creative: mockCreative,
            type: 'icon_url'
          })
        })
      );
    });

    it('should throw error if creative is null', async () => {
      await expect(downloader.handleCreativeDownload(null as any, 'auto'))
        .rejects.toThrow('Креатив не найден');
    });

    it('should throw error if no download URL available with auto type', async () => {
      const creative = { 
        ...mockCreative, 
        main_image_url: undefined, 
        icon_url: undefined,
        landing_page_url: undefined,
        video_url: undefined
      };
      
      await expect(downloader.handleCreativeDownload(creative, 'auto'))
        .rejects.toThrow('URL для скачивания не найден в данных креатива');
    });

    it('should throw error if specific type URL is not available', async () => {
      const creative = { 
        ...mockCreative, 
        icon_url: undefined
      };
      
      await expect(downloader.handleCreativeDownload(creative, 'icon_url'))
        .rejects.toThrow('URL для скачивания не найден в данных креатива (тип: icon_url)');
    });

    it('should emit error event on download failure', async () => {
      mockFetch.mockRejectedValue(new Error('Network error'));

      try {
        await downloader.handleCreativeDownload(mockCreative, 'auto');
      } catch (error) {
        // Ожидаем ошибку
      }

      expect(document.dispatchEvent).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'creatives:download-error',
          detail: expect.objectContaining({
            creative: mockCreative,
            type: 'auto'
          })
        })
      );
    });
  });

  describe('setupDownloadEventListener', () => {
    it('should setup event listener and return cleanup function', () => {
      const cleanup = downloader.setupDownloadEventListener();

      expect(document.addEventListener).toHaveBeenCalledWith(
        'creatives:download',
        expect.any(Function)
      );

      expect(typeof cleanup).toBe('function');

      // Вызываем cleanup
      cleanup();

      expect(document.removeEventListener).toHaveBeenCalledWith(
        'creatives:download',
        expect.any(Function)
      );
    });
  });
}); 