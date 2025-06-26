// Vue 3 + Pinia - Интерактивные островки (TypeScript)
import axios from 'axios';
import { createPinia, Pinia } from 'pinia';
import { App, Component, createApp } from 'vue';

// Типы для Vue островков
interface VueIslandProps {
    [key: string]: any;
}

interface VueIslandElement extends HTMLElement {
    getAttribute(name: 'data-vue-component'): string | null;
    getAttribute(name: 'data-vue-props'): string | null;
}



// Создаем глобальный Pinia store для всех островков
let globalPinia: Pinia | null = null;

function getGlobalPinia(): Pinia {
    if (!globalPinia) {
        globalPinia = createPinia();
        window.__globalPinia = globalPinia;
    }
    return globalPinia;
}

// Настраиваем Axios для глобального использования
window.axios = axios;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Добавляем CSRF токен если есть
const token = document.head.querySelector('meta[name="csrf-token"]') as HTMLMetaElement;
if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

/**
 * Статическая карта компонентов для поддержки Vite динамических импортов
 * Vite требует статических путей для анализа зависимостей
 */
const componentMap: Record<string, () => Promise<{ default: Component }>> = {
    // Основные компоненты
        
    // Креативы
    'CreativesFiltersComponent': () => import('./vue-components/creatives/FiltersComponent.vue'),
    'CreativesTabsComponent': () => import('./vue-components/creatives/TabsComponent.vue'),
    'CreativesListComponent': () => import('./vue-components/creatives/CreativesListComponent.vue'),
    
    // UI компоненты
    'PaginationComponent': () => import('./vue-components/ui/PaginationComponent.vue'),
    
    // Добавьте здесь новые компоненты по мере создания
};

/**
 * Функция для динамической загрузки компонента
 */
function loadComponent(componentName: string): Promise<{ default: Component }> {
    const loader = componentMap[componentName];
    
    if (loader) {
        return loader();
    }
    
    // Fallback: попытка загрузки через прямой путь (может не работать с Vite)
    console.warn(`Компонент ${componentName} не найден в статической карте. Попытка прямой загрузки...`);
    return import(`./vue-components/${componentName}.vue`);
}

/**
 * Конфигурация для Vue островков
 */
interface VueIslandsConfig {
    /** Очищать ли data-vue-props после инициализации (по умолчанию true) */
    cleanupProps?: boolean;
    /** Задержка перед очисткой props в мс (по умолчанию 1000) */
    cleanupDelay?: number;
    /** Режим отладки (сохранять props в development) */
    preservePropsInDev?: boolean;
}

// Конфигурация по умолчанию
const DEFAULT_CONFIG: VueIslandsConfig = {
    cleanupProps: true,
    cleanupDelay: 300,
    preservePropsInDev: true,
};

// Текущая конфигурация
let currentConfig: VueIslandsConfig = { ...DEFAULT_CONFIG };

/**
 * Устанавливает конфигурацию для Vue островков
 */
export function configureVueIslands(config: Partial<VueIslandsConfig>): void {
    currentConfig = { ...currentConfig, ...config };
    console.log('Vue Islands конфигурация обновлена:', currentConfig);
}

/**
 * Безопасно очищает props атрибут после инициализации компонента
 */
function cleanupPropsAttribute(element: VueIslandElement, componentName: string): void {
    if (!currentConfig.cleanupProps) {
        return;
    }

    // В development режиме можем сохранять props для отладки
    if (currentConfig.preservePropsInDev && import.meta.env.DEV) {
        console.log(`[DEV] Сохранение props для ${componentName} в development режиме`);
        return;
    }

    setTimeout(() => {
        try {
            // Проверяем что элемент все еще существует и инициализирован
            if (element.isConnected && element.hasAttribute('data-vue-initialized')) {
                const propsValue = element.getAttribute('data-vue-props');
                
                if (propsValue) {
                    // Логируем размер данных для анализа
                    const dataSize = new Blob([propsValue]).size;
                    console.log(`Очистка props для ${componentName} (размер: ${dataSize} байт)`);
                    
                    // Удаляем атрибут
                    element.removeAttribute('data-vue-props');
                    
                    // Добавляем метку об очистке (опционально для отладки)
                    element.setAttribute('data-vue-props-cleaned', 'true');
                    
                    // Эмитим событие об очистке
                    const cleanupEvent = new CustomEvent('vue-component-props-cleaned', {
                        detail: {
                            componentName,
                            element,
                            dataSize,
                            timestamp: new Date().toISOString(),
                        }
                    });
                    document.dispatchEvent(cleanupEvent);
                }
            }
        } catch (error) {
            console.warn(`Ошибка при очистке props для ${componentName}:`, error);
        }
    }, currentConfig.cleanupDelay);
}

/**
 * Функция для инициализации Vue островков на странице
 */
export function initVueIslands(): void {
    // Ищем все элементы с атрибутом data-vue-component
    const vueElements = document.querySelectorAll('[data-vue-component]:not([data-vue-initialized])') as NodeListOf<VueIslandElement>;
    
    vueElements.forEach((element: VueIslandElement) => {
        const componentName = element.getAttribute('data-vue-component');
        const componentProps = element.getAttribute('data-vue-props');
        
        if (!componentName) {
            console.warn('Не указано имя компонента для элемента:', element);
            return;
        }
        
        // Помечаем элемент как обрабатываемый
        element.setAttribute('data-vue-initialized', 'true');
        
        // Парсим пропсы если есть
        let props: VueIslandProps = {};
        if (componentProps) {
            try {
                props = JSON.parse(componentProps);
            } catch (e) {
                console.warn(`Ошибка парсинга props для компонента ${componentName}:`, e);
            }
        }
        
        // Динамически импортируем и монтируем компонент
        loadComponent(componentName)
            .then((module: { default: Component }) => {
                const component = module.default;
                const app: App = createApp(component, props);
                
                // Подключаем глобальный Pinia
                app.use(getGlobalPinia());
                
                // Глобальные свойства для всех компонентов
                app.config.globalProperties.$http = axios;
                
                // Скрываем placeholder сразу при начале монтирования
                const placeholder = element.querySelector('[data-vue-placeholder]') as HTMLElement;
                if (placeholder) {
                    placeholder.style.display = 'none';
                }
                
                // Создаем контейнер для Vue компонента
                const vueContainer = document.createElement('div');
                vueContainer.classList.add('vue-component-content');
                element.appendChild(vueContainer);
                
                // Монтируем компонент в отдельный контейнер
                app.mount(vueContainer);
                
                console.log(`Vue компонент ${componentName} успешно инициализирован`);
                
                // 🚀 НОВОЕ: Очищаем props после успешной инициализации
                cleanupPropsAttribute(element, componentName);
            })
            .catch((error: Error) => {
                console.error(`Ошибка загрузки Vue компонента ${componentName}:`, error);
                // Удаляем флаг инициализации при ошибке
                element.removeAttribute('data-vue-initialized');
                // НЕ очищаем props при ошибке - они могут понадобиться для повторной инициализации
            });
    });
}

/**
 * Переинициализация островков после AJAX загрузки контента
 */
export function reinitializeVueIslands(container?: Element): void {
    const searchContainer = container || document;
    const newElements = searchContainer.querySelectorAll('[data-vue-component]:not([data-vue-initialized])') as NodeListOf<VueIslandElement>;
    
    if (newElements.length > 0) {
        console.log(`Найдено ${newElements.length} новых Vue компонентов для инициализации`);
        initVueIslands();
    }
}

// Автоматическая инициализация при загрузке DOM
document.addEventListener('DOMContentLoaded', initVueIslands);

// Экспортируем функции для ручного использования
window.initVueIslands = initVueIslands;

// Интеграция с существующей AJAX архитектурой блога
document.addEventListener('blog:content-updated', (event: Event) => {
    const customEvent = event as CustomEvent;
    reinitializeVueIslands(customEvent.detail?.container);
});

/**
 * Управление placeholder'ами компонентов
 */
function hidePlaceholder(element: HTMLElement): void {
    const placeholder = element.querySelector('[data-vue-placeholder]') as HTMLElement;
    if (placeholder) {
        // Удаляем placeholder полностью (он уже скрыт при монтировании)
        placeholder.remove();
    }
    
    // Помечаем контейнер как готовый
    element.classList.add('vue-component-ready');
}

// Обработчик событий готовности компонентов
document.addEventListener('vue-component-ready', (event: Event) => {
    const customEvent = event as CustomEvent;
    const componentName = customEvent.detail?.component;
    
    console.log(`Компонент ${componentName} готов к отображению`);
    
    // Ищем соответствующий элемент компонента
    const componentElement = document.querySelector(`[data-vue-component="${componentName}"]`) as HTMLElement;
    
    if (componentElement) {
        hidePlaceholder(componentElement);
    }
}); 