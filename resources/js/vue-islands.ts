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

// Глобальные объекты
declare global {
    interface Window {
        initVueIslands: () => void;
        __globalPinia?: Pinia;
    }
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
    'ExampleCounter': () => import('./vue-components/ExampleCounter.vue'),
    
    // Креативы
    'CreativesFiltersComponent': () => import('./vue-components/creatives/FiltersComponent.vue'),
    
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
                
                // Монтируем компонент
                app.mount(element);
                
                console.log(`Vue компонент ${componentName} успешно инициализирован`);
            })
            .catch((error: Error) => {
                console.error(`Ошибка загрузки Vue компонента ${componentName}:`, error);
                // Удаляем флаг инициализации при ошибке
                element.removeAttribute('data-vue-initialized');
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