// import type { BlogNavigationManagerAPI, BlogServerData } from './blog.d';
import type { AxiosInstance } from 'axios';


declare global {
  
  interface Window {
    axios: AxiosInstance;
    $: JQueryStatic;
    jQuery: JQueryStatic;
    initVueIslands: () => void;
    __globalPinia?: any;

    // blogServerData?: BlogServerData;
    // blogNavigationManager?: BlogNavigationManagerAPI;
  }

  interface WindowEventMap {
    // 'blog:loadArticle': CustomEvent<{ slug: string; anchor?: string }>;
    // 'blog:setCategory': CustomEvent<{ categorySlug: string }>;
    // 'blog:search': CustomEvent<{ query: string }>;
    // 'blog:paginate': CustomEvent<{ page: number }>;
    
    // Creatives events
    'tabs:changed': CustomEvent<{ activeTab: string; previousTab: string; tabOption: any }>;
    'creatives:tab-changed': CustomEvent<{ previousTab: string; currentTab: string; tabOption: any }>;
    'vue-component-ready': CustomEvent<{ component: string; props: any; timestamp: string }>;
    
    // Vue Islands events
    'vue-component-props-cleaned': CustomEvent<{ componentName: string; element: HTMLElement; dataSize: number; timestamp: string }>;
  }
} 

export { };
