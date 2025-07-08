/**
 * Композабл для централизованного управления открытием креативов в новых вкладках
 * 
 * Принцип работы:
 * 1. Слушает DOM событие 'creatives:open-in-new-tab'
 * 2. Извлекает URL из detail события
 * 3. Валидирует URL и открывает в новой вкладке
 * 4. Эмитирует события успеха/ошибки для отслеживания
 */

export interface TabOpenEventDetail {
  url: string;
}

export interface TabOpenSuccessEventDetail extends TabOpenEventDetail {
  timestamp: number;
}

export interface TabOpenErrorEventDetail extends TabOpenEventDetail {
  error: string;
  timestamp: number;
}

/**
 * Композабл для управления открытием в новых вкладках
 */
export function useCreativesTabOpener() {
  /**
   * Проверяет валидность URL
   */
  const isValidUrl = (url: string): boolean => {
    try {
      new URL(url);
      return true;
    } catch {
      return false;
    }
  };

  /**
   * Открывает URL в новой вкладке
   */
  const openInNewTab = (url: string): void => {
    if (!url || typeof url !== 'string') {
      throw new Error('URL is required and must be a string');
    }

    if (!isValidUrl(url)) {
      throw new Error(`Invalid URL format: ${url}`);
    }

    // Проверяем доступность window
    if (typeof window === 'undefined') {
      throw new Error('Window object is not available (SSR environment)');
    }

    const newWindow = window.open(url, '_blank', 'noopener,noreferrer');
    
    // Современные браузеры могут вернуть null при блокировке popup,
    // но фактически открыть ссылку в новой вкладке
    if (!newWindow) {
      console.warn(`Popup may be blocked, but URL should still open: ${url}`);
      // НЕ выбрасываем ошибку, так как операция может быть успешной
    }
  };

  /**
   * Обрабатывает событие открытия в новой вкладке
   */
  const handleOpenInNewTab = (event: CustomEvent<TabOpenEventDetail>): void => {
    const { url } = event.detail;
    const timestamp = Date.now();

    try {
      openInNewTab(url);

      // Всегда эмитируем событие успеха, так как openInNewTab больше не выбрасывает исключения
      // при popup блокировке (операция может быть успешной)
      document.dispatchEvent(
        new CustomEvent<TabOpenSuccessEventDetail>('creatives:open-in-new-tab-success', {
          detail: {
            url,
            timestamp,
          },
        })
      );

      console.log(`Successfully opened URL in new tab: ${url}`);
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : 'Unknown error occurred';

      // Эмитируем событие ошибки только для реальных ошибок (невалидный URL, недоступность window)
      document.dispatchEvent(
        new CustomEvent<TabOpenErrorEventDetail>('creatives:open-in-new-tab-error', {
          detail: {
            url,
            error: errorMessage,
            timestamp,
          },
        })
      );

      console.error(`Failed to open URL in new tab: ${url}`, error);
    }
  };

  /**
   * Инициализирует слушатель событий
   */
  const initializeTabOpener = (): (() => void) => {
    const eventListener = (event: Event) => {
      handleOpenInNewTab(event as CustomEvent<TabOpenEventDetail>);
    };

    document.addEventListener('creatives:open-in-new-tab', eventListener);

    // Возвращаем функцию очистки
    return () => {
      document.removeEventListener('creatives:open-in-new-tab', eventListener);
    };
  };

  return {
    openInNewTab,
    handleOpenInNewTab,
    initializeTabOpener,
    isValidUrl,
  };
} 