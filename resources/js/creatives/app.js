import Alpine from 'alpinejs';
import { creativesStore } from './store/creativesStore.js';
import { filterComponent } from './components/filterComponent.js';
import { tabsComponent } from './components/tabsComponent.js';
import { creativesListComponent } from './components/creativesListComponent.js';
import { creativeItemComponent } from './components/creativeItemComponent.js';
import { detailsPanelComponent } from './components/detailsPanelComponent.js';
import { paginationComponent } from './components/paginationComponent.js';
import { apiService } from './services/apiService.js';
import { routerService } from './services/routerService.js';

window.Alpine = Alpine;

Alpine.store('creatives', creativesStore);

Alpine.data('creativesFilter', filterComponent);
Alpine.data('creativeTabs', tabsComponent);
Alpine.data('creativesList', creativesListComponent);
Alpine.data('creativeItem', creativeItemComponent);
Alpine.data('detailsPanel', detailsPanelComponent);
Alpine.data('pagination', paginationComponent);

document.addEventListener('DOMContentLoaded', () => {
    routerService.init();
    Alpine.start();
});

export { apiService };