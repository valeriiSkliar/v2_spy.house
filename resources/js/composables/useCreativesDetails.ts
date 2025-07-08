import type { Creative } from '@/types/creatives.d';

/**
 * Композабл для централизованной обработки деталей креативов
 * Stateless утилита без внутреннего состояния, только методы
 * 
 * 🎯 НАЗНАЧЕНИЕ:
 * - Централизованная обработка событий creatives:show-details
 * - Управление отображением панели деталей
 * - Загрузка дополнительных данных креатива
 * - Обработка placeholder для отображения деталей
 * 
 * 📋 ПОДДЕРЖИВАЕМЫЕ ОПЕРАЦИИ:
 * - show-details → показать детали креатива
 * - hide-details → скрыть панель деталей  
 * - toggle-details → переключить видимость
 * 
 * 🔧 ИНТЕГРАЦИЯ:
 * - Используется в Store через setupEventListeners()
 * - Слушает событие creatives:show-details
 * - Эмитирует события об успехе/ошибке отображения
 * - Интегрируется с placeholder системой
 */
export function useCreativesDetails() {
  
  /**
   * Показать placeholder для деталей креатива
   * Используется для мгновенного отклика UI перед загрузкой данных
   */
  function showDetailsPlaceholder(): void {
    // Эмитируем событие показа placeholder
    document.dispatchEvent(new CustomEvent('creatives:details-placeholder-shown', {
      detail: {
        timestamp: new Date().toISOString()
      }
    }));
    
    console.log('📋 Показан placeholder для деталей креатива');
  }
  
  /**
   * Скрыть placeholder для деталей креатива
   */
  function hideDetailsPlaceholder(): void {
    // Эмитируем событие скрытия placeholder
    document.dispatchEvent(new CustomEvent('creatives:details-placeholder-hidden', {
      detail: {
        timestamp: new Date().toISOString()
      }
    }));
    
    console.log('📋 Скрыт placeholder для деталей креатива');
  }
  
  /**
   * Загружает данные креатива по ID с сервера
   * @param creativeId - ID креатива
   * @returns Promise с данными креатива
   */
  async function loadCreativeById(creativeId: number): Promise<Creative> {
    try {
      console.log(`📋 Загружаем данные креатива по ID: ${creativeId}`);
      
      // Запрос к API для получения деталей креатива
      const response = await window.axios.get(`/api/creatives/${creativeId}/details`);
      
      if (!response.data || !response.data.data) {
        throw new Error('Некорректный ответ API');
      }
      
      const creativeData = response.data.data;
      
      // Валидируем полученные данные
      if (!validateCreativeData(creativeData)) {
        throw new Error('Полученные данные креатива некорректны');
      }
      
      console.log(`✅ Данные креатива ${creativeId} успешно загружены`);
      return creativeData;
      
    } catch (error) {
      console.error(`❌ Ошибка загрузки данных креатива ${creativeId}:`, error);
      throw error;
    }
  }
  
  /**
   * Проверяет валидность данных креатива
   */
  function validateCreativeData(creative: any): creative is Creative {
    return creative && 
           typeof creative === 'object' && 
           typeof creative.id === 'number' &&
           creative.id > 0;
  }
  
  /**
   * Главный обработчик показа деталей креатива по ID
   * @param creativeId - ID креатива
   */
  async function handleShowCreativeDetails(creativeId: number): Promise<Creative | null> {
    
    try {
      // Валидируем ID
      if (!creativeId || typeof creativeId !== 'number' || creativeId <= 0) {
        throw new Error('Некорректный ID креатива');
      }
      
      console.log(`📋 Начинаем показ деталей креатива по ID: ${creativeId}`);
      
      // Показываем placeholder для мгновенного отклика
      showDetailsPlaceholder();
      
      // Эмитируем событие начала показа деталей
      document.dispatchEvent(new CustomEvent('creatives:details-show-started', {
        detail: {
          creativeId,
          timestamp: new Date().toISOString()
        }
      }));
      
      // Загружаем данные креатива по ID
      const creativeData = await loadCreativeById(creativeId);
      
      // Скрываем placeholder
      hideDetailsPlaceholder();
      
      // Эмитируем событие успешного показа деталей
      document.dispatchEvent(new CustomEvent('creatives:details-shown', {
        detail: {
          creative: creativeData,
          creativeId,
          timestamp: new Date().toISOString()
        }
      }));
      
      console.log(`✅ Детали креатива ${creativeId} показаны успешно`);
      
      return creativeData;
      
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : 'Unknown error';
      
      console.error(`❌ Ошибка показа деталей креатива ${creativeId}:`, error);
      
      // Скрываем placeholder в случае ошибки
      hideDetailsPlaceholder();
      
      // Эмитируем событие ошибки показа деталей
      document.dispatchEvent(new CustomEvent('creatives:details-show-error', {
        detail: {
          creativeId,
          error: errorMessage,
          timestamp: new Date().toISOString()
        }
      }));
      
      throw error; // Пробрасываем ошибку дальше для обработки в Store
    }
  }
  
  /**
   * Обработчик скрытия деталей креатива
   */
  function handleHideCreativeDetails(): void {
    try {
      console.log('📋 Скрываем детали креатива');
      
      // Скрываем placeholder если он показан
      hideDetailsPlaceholder();
      
      // Эмитируем событие скрытия деталей
      document.dispatchEvent(new CustomEvent('creatives:details-hidden', {
        detail: {
          timestamp: new Date().toISOString()
        }
      }));
      
      console.log('✅ Детали креатива скрыты');
      
    } catch (error) {
      console.error('❌ Ошибка скрытия деталей креатива:', error);
      
      // Эмитируем событие ошибки скрытия
      document.dispatchEvent(new CustomEvent('creatives:details-hide-error', {
        detail: {
          error: error instanceof Error ? error.message : 'Unknown error',
          timestamp: new Date().toISOString()
        }
      }));
    }
  }
  
  /**
   * Обработчик переключения видимости деталей
   * @param creativeId - ID креатива (для показа) или null (для скрытия)
   * @param isCurrentlyVisible - текущее состояние видимости
   */
  async function handleToggleCreativeDetails(
    creativeId: number | null, 
    isCurrentlyVisible: boolean
  ): Promise<Creative | null> {
    try {
      if (isCurrentlyVisible) {
        handleHideCreativeDetails();
        return null;
      } else if (creativeId) {
        return await handleShowCreativeDetails(creativeId);
      } else {
        throw new Error('Нет ID креатива для показа деталей');
      }
    } catch (error) {
      console.error('Ошибка переключения деталей креатива:', error);
      throw error;
    }
  }
  
  /**
   * Настройка слушателя событий для автоматической обработки
   * Должна вызываться один раз при инициализации Store
   */
  function setupDetailsEventListener(): () => void {
    const handleShowDetailsEvent = async (event: CustomEvent) => {
      const { id } = event.detail;
      
      try {
        // Проверяем что передан ID
        if (typeof id !== 'number' || id <= 0) {
          throw new Error('Некорректный ID креатива в событии show-details');
        }
        
        await handleShowCreativeDetails(id);
      } catch (error) {
        console.error('Ошибка в обработчике события показа деталей:', error);
        // Ошибка уже эмитирована в handleShowCreativeDetails
      }
    };
    
    const handleHideDetailsEvent = () => {
      try {
        handleHideCreativeDetails();
      } catch (error) {
        console.error('Ошибка в обработчике события скрытия деталей:', error);
      }
    };
    
    const handleToggleDetailsEvent = async (event: CustomEvent) => {
      const { creativeId, isCurrentlyVisible } = event.detail;
      
      try {
        await handleToggleCreativeDetails(creativeId, isCurrentlyVisible);
      } catch (error) {
        console.error('Ошибка в обработчике события переключения деталей:', error);
      }
    };
    
    // Регистрируем слушатели
    document.addEventListener('creatives:show-details', handleShowDetailsEvent as unknown as EventListener);
    document.addEventListener('creatives:hide-details', handleHideDetailsEvent as unknown as EventListener);
    document.addEventListener('creatives:toggle-details', handleToggleDetailsEvent as unknown as EventListener);
    
    console.log('📋 Слушатели событий деталей креативов зарегистрированы');
    
    // Возвращаем функцию очистки
    return () => {
      document.removeEventListener('creatives:show-details', handleShowDetailsEvent as unknown as EventListener);
      document.removeEventListener('creatives:hide-details', handleHideDetailsEvent as unknown as EventListener);  
      document.removeEventListener('creatives:toggle-details', handleToggleDetailsEvent as unknown as EventListener);
      console.log('🧹 Слушатели событий деталей креативов удалены');
    };
  }
  
  return {
    // Основные методы
    handleShowCreativeDetails,
    handleHideCreativeDetails,
    handleToggleCreativeDetails,
    setupDetailsEventListener,
    
    // Утилитарные методы (для использования в других местах)
    loadCreativeById,
    validateCreativeData,
    
    // Методы работы с placeholder
    showDetailsPlaceholder,
    hideDetailsPlaceholder,
  };
} 