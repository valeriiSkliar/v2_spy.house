import type { Creative } from '@/types/creatives.d';

/**
 * Тип изображения для скачивания
 */
export type CreativeImageType = 'main_image_url' | 'icon_url' | 'video_url' | 'landing_url' | 'auto';

/**
 * Композабл для централизованной обработки скачивания креативов
 * Stateless утилита без внутреннего состояния, только методы
 * 
 * 🎯 НАЗНАЧЕНИЕ:
 * - Централизованная обработка событий creatives:download
 * - Поддержка скачивания изображений, видео и zip архивов
 * - Генерация имен файлов на основе данных креатива
 * - Обработка ошибок и уведомления пользователя
 * 
 * 📋 ПОДДЕРЖИВАЕМЫЕ ТИПЫ:
 * - main_image_url → основное изображение креатива
 * - icon_url → иконка креатива  
 * - video_url → видео креатив
 * - landing_url → fallback для некоторых случаев
 * - auto → автоматический выбор по приоритету
 * 
 * 🔧 ИНТЕГРАЦИЯ:
 * - Используется в Store через setupEventListeners()
 * - Слушает событие creatives:download с полем type
 * - Эмитирует события об успехе/ошибке скачивания
 */
export function useCreativesDownloader() {
  
  /**
   * Извлекает URL для скачивания из креатива
   * @param creative - данные креатива
   * @param type - тип изображения для скачивания или 'auto' для автоматического выбора
   */
  function getDownloadUrl(creative: Creative, type: CreativeImageType = 'auto'): string | null {
    // Если указан конкретный тип, используем его
    if (type !== 'auto') {
      const url = creative[type];
      if (url) {
        return url;
      }
      // Если запрошенный тип недоступен, возвращаем null (не fallback)
      return null;
    }
    
    // Автоматический выбор по приоритету
    if (creative.main_image_url) {
      return creative.main_image_url;
    }
    
    if (creative.icon_url) {
      return creative.icon_url;
    }
    
    if (creative.video_url) {
      return creative.video_url;
    }
    
    if (creative.landing_url) {
      return creative.landing_url;
    }
    
    return null;
  }
  
  /**
   * Генерирует имя файла для скачивания на основе данных креатива
   * @param creative - данные креатива
   * @param url - URL файла для определения расширения
   * @param type - тип изображения для добавления в имя файла
   */
  function generateFileName(url: string): string {
    let extension = 'jpg'; // По умолчанию
    
    // Генерируем случайный хеш для уникальности имени файла
    const generateRandomHash = (): string => {
      const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
      let hash = '';
      for (let i = 0; i < 8; i++) {
        hash += chars.charAt(Math.floor(Math.random() * chars.length));
      }
      return hash;
    };
    
    // Получаем расширение из URL
    const urlParts = url.split('.');
    if (urlParts.length > 1) {
      const lastPart = urlParts[urlParts.length - 1].split('?')[0]; // Убираем query параметры
      const possibleExtension = lastPart.toLowerCase();
      
      // Проверяем валидность расширения
      const validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'mp4', 'webm', 'avi', 'mov', 'mkv'];
      if (validExtensions.includes(possibleExtension)) {
        extension = possibleExtension;
      }
    }
    
    // Генерируем случайный хеш для уникальности
    const randomHash = generateRandomHash();
    
    return `${randomHash}.${extension}`;
  }
  
  /**
   * Проверяет поддерживает ли браузер download API
   */
  function isDownloadSupported(): boolean {
    const a = document.createElement('a');
    return 'download' in a;
  }
  
  /**
   * Проверяет возможные CORS ограничения для URL
   */
  function isCorsRestricted(url: string): boolean {
    try {
      const urlObj = new URL(url);
      const currentOrigin = window.location.origin;
      return urlObj.origin !== currentOrigin;
    } catch {
      return false;
    }
  }
  
  /**
   * Определяет тип контента на основе URL
   */
  function getContentType(url: string): 'image' | 'video' | 'archive' | 'other' {
    const extension = url.split('.').pop()?.toLowerCase() || '';
    
    const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];
    const videoExtensions = ['mp4', 'webm', 'avi', 'mov', 'mkv'];
    const archiveExtensions = ['zip', 'rar', '7z', 'tar', 'gz'];
    
    if (imageExtensions.includes(extension)) {
      return 'image';
    }
    
    if (videoExtensions.includes(extension)) {
      return 'video';
    }
    
    if (archiveExtensions.includes(extension)) {
      return 'archive';
    }
    
    return 'other';
  }
  
  /**
   * Основная функция скачивания файла
   * ВСЕГДА использует blob для гарантированного показа диалога сохранения
   */
  async function downloadFile(url: string, filename: string): Promise<void> {
    // Проверяем поддержку download API
    if (!isDownloadSupported()) {
      console.warn('Браузер не поддерживает download API, используем fallback');
      window.open(url, '_blank');
      return;
    }
    
    try {
      // Пытаемся загрузить файл через fetch для получения blob
      const response = await fetch(url, {
        method: 'GET',
        // Добавляем headers для обхода некоторых CORS ограничений
        headers: {
          'Accept': '*/*',
        },
        // Указываем режим для CORS запросов
        mode: 'cors',
        cache: 'no-cache'
      });
      
      if (!response.ok) {
        throw new Error(`Ошибка загрузки файла: ${response.status} ${response.statusText}`);
      }
      
      // Получаем blob данные
      const blob = await response.blob();
      
      // Создаем blob URL
      const blobUrl = window.URL.createObjectURL(blob);
      
      // Создаем скрытую ссылку для принудительного скачивания
      const link = document.createElement('a');
      link.href = blobUrl;
      link.download = filename;
      link.style.display = 'none';
      
      // Устанавливаем дополнительные атрибуты для принудительного скачивания
      link.setAttribute('download', filename);
      link.setAttribute('target', '_self');
      
      // Добавляем в DOM, кликаем и немедленно удаляем
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      
      // Освобождаем память через небольшую задержку
      // Задержка нужна для того чтобы браузер успел инициировать скачивание
      setTimeout(() => {
        if (typeof window !== 'undefined' && window.URL && window.URL.revokeObjectURL) {
          window.URL.revokeObjectURL(blobUrl);
        }
      }, 100);
      
      console.log(`✅ Файл ${filename} успешно скачан через blob`);
      
    } catch (error) {
      console.error('Ошибка blob скачивания:', error);
      
      // Fallback 1: Прямая ссылка с download атрибутом (для файлов с того же домена)
      if (!isCorsRestricted(url)) {
        try {
          const link = document.createElement('a');
          link.href = url;
          link.download = filename;
          link.style.display = 'none';
          
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
          
          console.log(`✅ Файл ${filename} скачан через прямую ссылку`);
          return;
          
        } catch (directError) {
          console.error('Ошибка прямого скачивания:', directError);
        }
      }
      
      // Fallback 2: Открытие в новой вкладке (последний resort)
      try {
        const link = document.createElement('a');
        link.href = url;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.style.display = 'none';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        console.warn(`⚠️ Файл ${filename} открыт в новой вкладке (fallback метод)`);
        
      } catch (fallbackError) {
        console.error('Все методы скачивания не удались:', fallbackError);
        throw new Error(`Не удалось скачать файл: ${error instanceof Error ? error.message : 'Unknown error'}`);
      }
    }
  }
  
  /**
   * Главный обработчик скачивания креатива
   * @param url - URL для скачивания
   */
  async function handleCreativeDownload(url: string): Promise<void> {
    
    // Получаем URL для скачивания с учетом типа
    const downloadUrl = url;
    
    if (!downloadUrl) {
      throw new Error(`URL для скачивания не найден`);
    }
    
    // Генерируем имя файла с учетом типа
    const filename = generateFileName(downloadUrl);
    
    console.log(`🔽 Начинаем скачивание креатива:`, {
      url,
      downloadUrl,
      filename,
    });
    
    try {
      // Эмитируем событие начала скачивания
      document.dispatchEvent(new CustomEvent('creatives:download-started', {
        detail: {
          url,
          downloadUrl,
          filename,
          timestamp: new Date().toISOString()
        }
      }));
      
      // Выполняем скачивание
      await downloadFile(downloadUrl, filename);
      
      // Эмитируем событие успешного скачивания
      document.dispatchEvent(new CustomEvent('creatives:download-success', {
        detail: {
          url,
          downloadUrl,
          filename,
          timestamp: new Date().toISOString()
        }
      }));
      
      console.log(`✅ Скачивание креатива завершено успешно`);
      
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : 'Unknown error';
      
      console.error(`❌ Ошибка скачивания креатива:`, error);
      
      // Эмитируем событие ошибки скачивания
      document.dispatchEvent(new CustomEvent('creatives:download-error', {
        detail: {
          url,
          downloadUrl,
          filename,
          error: errorMessage,
          timestamp: new Date().toISOString()
        }
      }));
      
      throw error; // Пробрасываем ошибку дальше для обработки в Store
    }
  }
  
  /**
   * Настройка слушателя событий для автоматической обработки
   * Должна вызываться один раз при инициализации Store
   */
  function setupDownloadEventListener(): () => void {
    const handleDownloadEvent = async (event: CustomEvent) => {
      const { url } = event.detail;
      
      try {
        await handleCreativeDownload(url);
      } catch (error) {
        console.error('Ошибка в обработчике события скачивания:', error);
        // Ошибка уже эмитирована в handleCreativeDownload
      }
    };
    
    // Регистрируем слушатель
    document.addEventListener('creatives:download', handleDownloadEvent as unknown as EventListener);
    
    console.log('📥 Слушатель событий скачивания креативов зарегистрирован');
    
    // Возвращаем функцию очистки
    return () => {
      document.removeEventListener('creatives:download', handleDownloadEvent as unknown as EventListener);
      console.log('🧹 Слушатель событий скачивания креативов удален');
    };
  }
  
  return {
    // Основные методы
    handleCreativeDownload,
    setupDownloadEventListener,
    
    // Утилитарные методы (для использования в других местах)
    getDownloadUrl,
    generateFileName,
    downloadFile,
    
    // Вспомогательные методы для диагностики
    isDownloadSupported,
    isCorsRestricted
  };
} 