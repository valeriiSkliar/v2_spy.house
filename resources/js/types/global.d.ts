// import type { BlogNavigationManagerAPI, BlogServerData } from './blog.d';

declare global {
  
  interface Window {
    axios: any;
    $: JQueryStatic;
    jQuery: JQueryStatic;
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
