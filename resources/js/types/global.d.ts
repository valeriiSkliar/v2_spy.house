// import type { BlogNavigationManagerAPI, BlogServerData } from './blog.d';
import type { AxiosInstance } from 'axios';

declare global {
  
  interface Window {
    axios: AxiosInstance;
    $: JQueryStatic;
    jQuery: JQueryStatic;
    initVueIslands: () => void;

    // blogServerData?: BlogServerData;
    // blogNavigationManager?: BlogNavigationManagerAPI;
  }

  interface WindowEventMap {
    // 'blog:loadArticle': CustomEvent<{ slug: string; anchor?: string }>;
    // 'blog:setCategory': CustomEvent<{ categorySlug: string }>;
    // 'blog:search': CustomEvent<{ query: string }>;
    // 'blog:paginate': CustomEvent<{ page: number }>;
  }
} 

export { };
