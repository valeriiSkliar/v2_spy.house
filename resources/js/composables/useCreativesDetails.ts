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
   * Загружает дополнительные данные креатива с сервера
   * @param creative - базовые данные креатива
   * @returns Promise с полными данными креатива
   */
  async function loadCreativeDetails(creative: Creative): Promise<Creative> {
    try {
      // Если креатив уже содержит все необходимые данные, возвращаем как есть
      if (creative && typeof creative === 'object' && creative.id) {
        // TODO: Здесь можно добавить проверку на наличие всех необходимых полей
        // и делать дополнительный запрос только если данных недостаточно
        
        // Пока возвращаем существующие данные
        return creative;
      }
      
      // Если креатив неполный, загружаем дополнительные данные
      const response = await window.axios.get(`/api/creatives/${creative.id}/details`);
      console.log(response);
      return response.data.data;
      
    } catch (error) {
      console.error('Ошибка загрузки дополнительных данных креатива:', error);
      
      // В случае ошибки возвращаем исходные данные
      return creative;
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
   * Главный обработчик показа деталей креатива
   * @param creative - данные креатива или ID креатива
   */
  async function handleShowCreativeDetails(id: number): Promise<Creative | null> {
    try {
      // Определяем данные креатива
      if (typeof id === 'number') {
        // Если передан только ID, нужно получить данные
        throw new Error('Передан только ID креатива. Необходимы полные данные креатива.');
      } else if (validateCreativeData(id)) {
        const creativeData = await loadCreativeDetails(id);
        console.log('🔍 handleShowCreativeDetails', creativeData);
      } else {
        throw new Error('Некорректные данные креатива');
      }
    
      
      // Показываем placeholder для мгновенного отклика
      showDetailsPlaceholder();
      
      // Эмитируем событие начала показа деталей
      document.dispatchEvent(new CustomEvent('creatives:details-show-started', {
        detail: {
          id: id,
          timestamp: new Date().toISOString()
        }
      }));
      
      // Загружаем дополнительные данные если необходимо
      const fullCreativeData = await loadCreativeDetails(id);
      
      // Скрываем placeholder
      hideDetailsPlaceholder();
      
      // Эмитируем событие успешного показа деталей
      document.dispatchEvent(new CustomEvent('creatives:details-shown', {
        detail: {
          creative: fullCreativeData,
          timestamp: new Date().toISOString()
        }
      }));
      
      console.log(`✅ Детали креатива показаны успешно`);
      
      return fullCreativeData;
      
    } catch (error) {
      const errorMessage = error instanceof Error ? error.message : 'Unknown error';
      
      console.error(`❌ Ошибка показа деталей креатива:`, error);
      
      // Скрываем placeholder в случае ошибки
      hideDetailsPlaceholder();
      
      // Эмитируем событие ошибки показа деталей
      document.dispatchEvent(new CustomEvent('creatives:details-show-error', {
        detail: {
          id: id,
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
   * @param creative - данные креатива (для показа) или null (для скрытия)
   * @param isCurrentlyVisible - текущее состояние видимости
   */
  async function handleToggleCreativeDetails(
    creative: Creative | null, 
    isCurrentlyVisible: boolean
  ): Promise<Creative | null> {
    try {
      if (isCurrentlyVisible) {
        handleHideCreativeDetails();
        return null;
      } else if (creative) {
        return await handleShowCreativeDetails(creative.id);
      } else {
        throw new Error('Нет данных креатива для показа деталей');
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
          handleShowCreativeDetails(id);
          console.log(`Получен только ID креатива (${id}). Запрошены полные данные.`);
          return;        
      } catch (error) {
        console.error('Ошибка в обработчике события показа деталей:', error);
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
      const { creative, isCurrentlyVisible } = event.detail;
      
      try {
        await handleToggleCreativeDetails(creative, isCurrentlyVisible);
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
    loadCreativeDetails,
    validateCreativeData,
    
    // Методы работы с placeholder
    showDetailsPlaceholder,
    hideDetailsPlaceholder,
  };
} 