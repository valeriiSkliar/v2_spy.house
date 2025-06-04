import Alpine from 'alpinejs';

import { creativeItemComponent } from './components/creativeItemComponent.js';
import { creativesListComponent } from './components/creativesListComponent.js';
import { detailsPanelComponent } from './components/detailsPanelComponent.js';
import { filterComponent } from './components/filterComponent.js';
import { paginationComponent } from './components/paginationComponent.js';
import { tabsComponent } from './components/tabsComponent.js';
import { apiService } from './services/apiService.js';
import { routerService } from './services/routerService.js';
import { creativesStore } from './store/creativesStore.js';

// Устанавливаем Alpine.js глобально для этого модуля
window.Alpine = Alpine;

// Система отслеживания готовности модулей для creatives
const moduleLoadingTracker = {
  loadedModules: new Set(),
  expectedModules: new Set(['creatives']),

  markModuleLoaded(moduleName) {
    this.loadedModules.add(moduleName);
    console.log(`Module loaded: ${moduleName}`);
  },

  allModulesLoaded() {
    return (
      this.expectedModules.size > 0 &&
      this.loadedModules.size >= this.expectedModules.size &&
      [...this.expectedModules].every(module => this.loadedModules.has(module))
    );
  },
};

// Функции регистрации компонентов для Alpine.js
const registerAlpineComponent = function (name, component) {
  Alpine.data(name, component);
  console.log(`Alpine component registered: ${name}`);
};

const registerAlpineStore = function (name, store) {
  Alpine.store(name, store);
  console.log(`Alpine store registered: ${name}`);
};

// Регистрируем компоненты до старта Alpine.js
console.log('Registering creatives components...');

// Функция регистрации компонентов
const registerComponents = () => {
  try {
    // Регистрируем store
    console.log('Registering creatives store...');
    registerAlpineStore('creatives', creativesStore);

    // Регистрируем компоненты
    console.log('Registering components...');
    registerAlpineComponent('creativesFilter', filterComponent);
    registerAlpineComponent('creativeTabs', tabsComponent);
    registerAlpineComponent('creativesList', creativesListComponent);
    registerAlpineComponent('creativeItem', creativeItemComponent);
    registerAlpineComponent('detailsPanel', detailsPanelComponent);
    registerAlpineComponent('pagination', paginationComponent);

    console.log('Creatives components registered successfully');

    // Отмечаем модуль как загруженный
    moduleLoadingTracker.markModuleLoaded('creatives');
  } catch (error) {
    console.error('Error registering creatives components:', error);
  }
};

// Инициализация Alpine.js для creatives
document.addEventListener('DOMContentLoaded', () => {
  // Регистрируем компоненты
  registerComponents();

  console.log('Alpine.js started for creatives page');

  let startAttempts = 0;
  const maxAttempts = 30; // Максимум 3 секунды ожидания

  // Ждем готовности и запускаем Alpine.js
  const startAlpine = () => {
    startAttempts++;

    Alpine.start();

    console.log('Alpine.js started for creatives page', {
      attempt: startAttempts,
    });

    const modulesReady = moduleLoadingTracker.allModulesLoaded();

    if (modulesReady || startAttempts >= maxAttempts) {
      console.log('Alpine.js started for creatives page', {
        attempt: startAttempts,
        modulesReady,
        expectedModules: [...moduleLoadingTracker.expectedModules],
        loadedModules: [...moduleLoadingTracker.loadedModules],
      });

      // Инициализируем роутер после старта Alpine.js
      setTimeout(() => {
        console.log('Initializing router...');
        if (window.Alpine && window.Alpine.store) {
          routerService.init(window.Alpine.store('creatives'));
        }
      }, 200);
    } else {
      setTimeout(startAlpine, 100);
    }
  };

  // Даем время для регистрации компонентов
  setTimeout(startAlpine, 200);
});
export { apiService };
