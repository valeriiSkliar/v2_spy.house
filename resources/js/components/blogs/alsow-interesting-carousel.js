import $ from 'jquery';

// Объект для отслеживания инициализированных каруселей
const initializedCarousels = new Set();

/**
 * Базовый класс для управления каруселями
 * Зачем: унификация логики инициализации и уничтожения каруселей
 */
class CarouselManager {
  constructor(containerId, config) {
    this.containerId = containerId;
    this.config = {
      dots: false,
      infinite: true,
      speed: 300,
      slidesToShow: 1,
      slidesToScroll: 1,
      ...config,
    };
  }

  /**
   * Безопасная инициализация карусели
   * Зачем: предотвращение повторной инициализации и ошибок
   */
  init() {
    try {
      const $container = $(this.containerId);

      if ($container.length === 0) {
        console.log(`Carousel container ${this.containerId} not found`);
        return false;
      }

      // Проверяем, не инициализирована ли уже карусель
      if (this.isInitialized()) {
        console.log(`Carousel ${this.containerId} already initialized`);
        return true;
      }

      // Проверяем наличие обязательных элементов
      if (!this.validateRequiredElements()) {
        console.warn(`Required elements missing for carousel ${this.containerId}`);
        return false;
      }

      // Уничтожаем существующую карусель если есть
      this.destroy();

      // Инициализируем новую карусель
      $container.slick(this.config);

      // Отмечаем как инициализированную
      initializedCarousels.add(this.containerId);

      console.log(`Carousel ${this.containerId} initialized successfully`);
      return true;
    } catch (error) {
      console.error(`Error initializing carousel ${this.containerId}:`, error);
      return false;
    }
  }

  /**
   * Безопасное уничтожение карусели
   * Зачем: предотвращение конфликтов при переинициализации
   */
  destroy() {
    try {
      const $container = $(this.containerId);

      if ($container.length > 0 && $container.hasClass('slick-initialized')) {
        $container.slick('destroy');
        console.log(`Carousel ${this.containerId} destroyed`);
      }

      initializedCarousels.delete(this.containerId);
    } catch (error) {
      console.warn(`Error destroying carousel ${this.containerId}:`, error);
    }
  }

  /**
   * Проверка инициализации карусели
   */
  isInitialized() {
    const $container = $(this.containerId);
    return $container.length > 0 && $container.hasClass('slick-initialized');
  }

  /**
   * Валидация обязательных элементов (переопределяется в наследниках)
   */
  validateRequiredElements() {
    return true;
  }

  /**
   * Принудительная переинициализация
   * Зачем: обновление карусели после изменения контента
   */
  reinit() {
    this.destroy();
    // Небольшая задержка для корректного удаления DOM элементов
    setTimeout(() => {
      this.init();
    }, 50);
  }
}

/**
 * Карусель "Также интересно"
 * Зачем: специализированная логика для конкретной карусели
 */
class AlsowInterestingCarousel extends CarouselManager {
  constructor() {
    super('#alsow-interesting-articles-carousel-container', {
      variableWidth: true,
      prevArrow: '#alsow-interesting-articles-carousel-prev',
      nextArrow: '#alsow-interesting-articles-carousel-next',
      responsive: [
        {
          breakpoint: 768,
          settings: {
            variableWidth: false,
            slidesToShow: 1,
          },
        },
      ],
    });
  }

  validateRequiredElements() {
    const prevArrow = $('#alsow-interesting-articles-carousel-prev');
    const nextArrow = $('#alsow-interesting-articles-carousel-next');

    if (prevArrow.length === 0 || nextArrow.length === 0) {
      console.warn('Alsow interesting carousel: navigation arrows not found');
      return false;
    }

    return true;
  }
}

/**
 * Карусель "Часто читают"
 * Зачем: специализированная логика для вертикальной карусели
 */
class ReadOftenCarousel extends CarouselManager {
  constructor() {
    super('#read-often-articles-carousel-container', {
      vertical: true,
      slidesToShow: 4,
      slidesToScroll: 1,
      verticalSwiping: true,
      prevArrow: '#read-often-articles-carousel-prev',
      nextArrow: '#read-often-articles-carousel-next',
      responsive: [
        {
          breakpoint: 1024,
          settings: {
            slidesToShow: 3,
            slidesToScroll: 1,
          },
        },
        {
          breakpoint: 768,
          settings: {
            vertical: false,
            verticalSwiping: false,
            slidesToShow: 1,
            slidesToScroll: 1,
          },
        },
      ],
    });
  }

  validateRequiredElements() {
    const prevArrow = $('#read-often-articles-carousel-prev');
    const nextArrow = $('#read-often-articles-carousel-next');

    if (prevArrow.length === 0 || nextArrow.length === 0) {
      console.warn('Read often carousel: navigation arrows not found');
      return false;
    }

    return true;
  }
}

// Создаем экземпляры каруселей
const alsowCarousel = new AlsowInterestingCarousel();
const readOftenCarousel = new ReadOftenCarousel();

/**
 * Инициализация карусели "Также интересно"
 * Зачем: публичный API для инициализации
 */
const initAlsowInterestingArticlesCarousel = async () => {
  const dependenciesOk = await checkDependencies();
  if (!dependenciesOk) {
    return false;
  }
  return alsowCarousel.init();
};

/**
 * Инициализация карусели "Часто читают"
 * Зачем: публичный API для инициализации
 */
const initReadOftenArticlesCarousel = async () => {
  const dependenciesOk = await checkDependencies();
  if (!dependenciesOk) {
    return false;
  }
  return readOftenCarousel.init();
};

/**
 * Уничтожение всех каруселей
 * Зачем: очистка перед переинициализацией контента
 */
const destroyAllCarousels = () => {
  alsowCarousel.destroy();
  readOftenCarousel.destroy();
};

/**
 * Переинициализация всех каруселей
 * Зачем: обновление после AJAX загрузки контента
 */
const reinitAllCarousels = () => {
  destroyAllCarousels();

  // Задержка для корректного удаления DOM
  setTimeout(() => {
    initAlsowInterestingArticlesCarousel();
    initReadOftenArticlesCarousel();
  }, 100);
};

/**
 * Проверка совместимости с jQuery и Slick
 * Зачем: предотвращение ошибок при отсутствии зависимостей
 */
const checkDependencies = async () => {
  console.log('Checking dependencies...');
  console.log('jQuery available:', typeof $ !== 'undefined');
  console.log('jQuery version:', typeof $ !== 'undefined' ? $.fn.jquery : 'N/A');
  console.log('Slick available:', typeof $ !== 'undefined' && typeof $.fn.slick !== 'undefined');
  
  if (typeof $ === 'undefined') {
    console.error('jQuery is required for carousels');
    return false;
  }

  // Если Slick не найден, попробуем загрузить его динамически
  if (typeof $.fn.slick === 'undefined') {
    console.log('Slick not found, trying to load dynamically...');
    try {
      await import('slick-carousel');
      console.log('Slick loaded dynamically');
      
      // Проверим еще раз
      if (typeof $.fn.slick === 'undefined') {
        console.error('Slick carousel plugin is required');
        return false;
      }
    } catch (error) {
      console.error('Failed to load slick carousel:', error);
      return false;
    }
  }

  return true;
};

/**
 * Инициализация с проверкой зависимостей
 * Зачем: безопасная инициализация при готовности DOM
 */
const initCarouselsWhenReady = async () => {
  const dependenciesOk = await checkDependencies();
  if (!dependenciesOk) {
    return;
  }

  // Инициализируем карусели после полной загрузки DOM
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      initAlsowInterestingArticlesCarousel();
      initReadOftenArticlesCarousel();
    });
  } else {
    // DOM уже загружен
    initAlsowInterestingArticlesCarousel();
    initReadOftenArticlesCarousel();
  }
};

// Автоматическая инициализация при импорте модуля
initCarouselsWhenReady();

export {
  AlsowInterestingCarousel,
  CarouselManager,
  destroyAllCarousels,
  initAlsowInterestingArticlesCarousel,
  initReadOftenArticlesCarousel,
  ReadOftenCarousel,
  reinitAllCarousels,
};
