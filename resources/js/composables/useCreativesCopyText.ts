/**
 * Тип контента для копирования
 */
export type CopyContentType = 'title' | 'description' | 'landing_url' | 'custom';

/**
 * Композабл для централизованной обработки копирования текста креативов
 * Stateless утилита без внутреннего состояния, только методы
 * 
 * 🎯 НАЗНАЧЕНИЕ:
 * - Централизованная обработка событий creatives:copy-text
 * - Поддержка копирования заголовков, описаний, URL и произвольного текста
 * - Обработка ошибок и уведомления пользователя
 * - Fallback для старых браузеров
 * 
 * 📋 ПОДДЕРЖИВАЕМЫЕ ТИПЫ:
 * - title → заголовок креатива
 * - description → описание креатива  
 * - landing_url → ссылка на лендинг
 * - custom → произвольный текст
 * 
 * 🔧 ИНТЕГРАЦИЯ:
 * - Используется в Store через setupEventListeners()
 * - Слушает событие creatives:copy-text с полями text, type, creativeId
 * - Эмитирует события об успехе/ошибке копирования
 */
export function useCreativesCopyText() {
  
  /**
   * Проверяет поддерживает ли браузер современный clipboard API
   */
  function isClipboardSupported(): boolean {
    return navigator && navigator.clipboard && typeof navigator.clipboard.writeText === 'function';
  }
  
  /**
   * Современный метод копирования через clipboard API
   */
  async function copyWithClipboardAPI(text: string): Promise<void> {
    try {
      await navigator.clipboard.writeText(text);
    } catch (error) {
      // Если clipboard API не сработал, используем fallback
      throw new Error(`Clipboard API failed: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  }
  
  /**
   * Fallback метод копирования для старых браузеров
   */
  function copyWithFallback(text: string): void {
    try {
      // Создаем временный textarea элемент
      const textarea = document.createElement('textarea');
      textarea.value = text;
      textarea.style.position = 'fixed';
      textarea.style.left = '-9999px';
      textarea.style.top = '-9999px';
      textarea.style.opacity = '0';
      textarea.setAttribute('readonly', '');
      textarea.setAttribute('tabindex', '-1');
      
      // Добавляем в DOM
      document.body.appendChild(textarea);
      
      // Выделяем текст
      textarea.select();
      textarea.setSelectionRange(0, textarea.value.length);
      
      // Копируем через execCommand
      const successful = document.execCommand('copy');
      
      // Удаляем из DOM
      document.body.removeChild(textarea);
      
      if (!successful) {
        throw new Error('execCommand copy failed');
      }
      
    } catch (error) {
      throw new Error(`Fallback copy failed: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  }
  
  /**
   * Универсальная функция копирования с автоматическим fallback
   */
  async function copyToClipboard(text: string): Promise<{ method: string; fallback: boolean }> {
    if (!text || text.trim() === '') {
      throw new Error('Текст для копирования не может быть пустым');
    }
    
    // Сначала пытаемся использовать современный clipboard API
    if (isClipboardSupported()) {
      try {
        await copyWithClipboardAPI(text);
        return { method: 'clipboard-api', fallback: false };
      } catch (clipboardError) {
        console.warn('Clipboard API не сработал, используем fallback:', clipboardError);
        
        // Если clipboard API не сработал, используем fallback
        try {
          copyWithFallback(text);
          return { method: 'execCommand', fallback: true };
        } catch (fallbackError) {
          // Если и fallback не сработал, выбрасываем ошибку
          throw new Error(`Все методы копирования не удались. Clipboard API: ${clipboardError}. Fallback: ${fallbackError}`);
        }
      }
    } else {
      // Если clipboard API не поддерживается, сразу используем fallback
      try {
        copyWithFallback(text);
        return { method: 'execCommand', fallback: true };
      } catch (fallbackError) {
        throw new Error(`Fallback копирование не удалось: ${fallbackError}`);
      }
    }
  }
  
  /**
   * Валидация типа контента
   */
  function validateContentType(type: string): type is CopyContentType {
    const validTypes: CopyContentType[] = ['title', 'description', 'landing_url', 'custom'];
    return validTypes.includes(type as CopyContentType);
  }
  
  /**
   * Главный обработчик копирования креативов
   * @param text - текст для копирования
   * @param type - тип контента (title, description, landing_url, custom)
   * @param creativeId - ID креатива (опционально)
   */
  async function handleCopyText(
    text: string, 
    type: CopyContentType = 'custom', 
    creativeId?: number
  ): Promise<void> {
    
    // Валидация входных данных
    if (!text || text.trim() === '') {
      throw new Error('Текст для копирования не может быть пустым');
    }
    
    if (!validateContentType(type)) {
      throw new Error(`Неподдерживаемый тип контента: ${type}`);
    }
    
    console.log(`📋 Начинаем копирование:`, {
      text: text.substring(0, 50) + (text.length > 50 ? '...' : ''),
      type,
      creativeId,
      textLength: text.length
    });
    
    try {
      // Эмитируем событие начала копирования
      document.dispatchEvent(new CustomEvent('creatives:copy-started', {
        detail: {
          text,
          type,
          creativeId,
          timestamp: new Date().toISOString()
        }
      }));
      
      // Выполняем копирование
      const result = await copyToClipboard(text);
      
      // Эмитируем событие успешного копирования
      document.dispatchEvent(new CustomEvent('creatives:copy-success', {
        detail: {
          text,
          type,
          creativeId,
          method: result.method,
          fallback: result.fallback,
          timestamp: new Date().toISOString()
        }
      }));
      
      console.log(`✅ Копирование завершено успешно (${result.method}${result.fallback ? ', fallback' : ''})`);
      
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : 'Unknown error';
      
      console.error(`❌ Ошибка копирования:`, error);
      
      // Эмитируем событие ошибки копирования
      document.dispatchEvent(new CustomEvent('creatives:copy-error', {
        detail: {
          text,
          type,
          creativeId,
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
  function setupCopyEventListener(): () => void {
    const handleCopyEvent = async (event: CustomEvent) => {
      const { text, type = 'custom', creativeId } = event.detail;
      
      try {
        await handleCopyText(text, type, creativeId);
      } catch (error) {
        console.error('Ошибка в обработчике события копирования:', error);
        // Ошибка уже эмитирована в handleCopyText
      }
    };
    
    // Регистрируем слушатель
    document.addEventListener('creatives:copy-text', handleCopyEvent as unknown as EventListener);
    
    console.log('📋 Слушатель событий копирования креативов зарегистрирован');
    
    // Возвращаем функцию очистки
    return () => {
      document.removeEventListener('creatives:copy-text', handleCopyEvent as unknown as EventListener);
      console.log('🧹 Слушатель событий копирования креативов удален');
    };
  }
  
  /**
   * Вспомогательные функции для прямого использования в компонентах
   * (если нужно обойти событийную систему)
   */
  
  /**
   * Прямое копирование заголовка креатива
   */
  async function copyCreativeTitle(title: string, creativeId?: number): Promise<void> {
    await handleCopyText(title, 'title', creativeId);
  }
  
  /**
   * Прямое копирование описания креатива
   */
  async function copyCreativeDescription(description: string, creativeId?: number): Promise<void> {
    await handleCopyText(description, 'description', creativeId);
  }
  
  /**
   * Прямое копирование URL креатива
   */
  async function copyCreativeLandingUrl(landingUrl: string, creativeId?: number): Promise<void> {
    await handleCopyText(landingUrl, 'landing_url', creativeId);
  }
  
  /**
   * Прямое копирование произвольного текста
   */
  async function copyCustomText(text: string): Promise<void> {
    await handleCopyText(text, 'custom');
  }
  
  return {
    // Основные методы
    handleCopyText,
    setupCopyEventListener,
    
    // Прямые методы для компонентов
    copyCreativeTitle,
    copyCreativeDescription, 
    copyCreativeLandingUrl,
    copyCustomText,
    
    // Утилитарные методы (для использования в других местах)
    copyToClipboard,
    
    // Вспомогательные методы для диагностики
    isClipboardSupported,
    validateContentType
  };
} 