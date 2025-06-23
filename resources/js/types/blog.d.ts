export interface BlogArticle {
  id: number;
  title: string;
  content: string;
  slug: string;
  excerpt?: string;
  published_at: string;
  author?: string;
  category?: BlogCategory;
  categories?: BlogCategory[];
  tags?: string[];
  meta_title?: string;
  meta_description?: string;
  // Дополнительные поля для рендеринга
  featured_image?: string;
  thumbnail?: string;
  comments_count?: number;
  views_count?: number;
  average_rating?: number;
}

export interface BlogCategory {
  id: number;
  name: string;
  slug: string;
  description?: string;
  posts_count?: number;
  color?: string;
}

// API Response Types - match exact server structure
export interface BlogArticleAPI {
  id: number;
  title: string;
  excerpt: string;
  content: string;
  slug: string;
  views_count: number;
  published_at: string;
  thumbnail: string;
  category: {
    id: number;
    name: string;
    slug: string;
  } | null;
  author: {
    id: number;
    name: string;
    avatar: string | null;
  } | null;
  average_rating: string;
}

export interface BlogCategoryAPI {
  id: number;
  name: string;
  slug: string;
  posts_count: number;
}

export interface BlogPaginationAPI {
  total: number;
  perPage: number;
  currentPage: number;
  lastPage: number;
}

export interface BlogCurrentCategoryAPI {
  id: number;
  name: string;
  slug: string;
}

export interface BlogFiltersAPI {
  page: number;
  category: string;
  search: string;
  sort: string;
  direction: string;
}

export interface BlogArticlesResponse {
  success: boolean;
  mode: string;
  articles: BlogArticleAPI[];
  heroArticle: BlogArticleAPI | null;
  categories: BlogCategoryAPI[];
  popularPosts: BlogArticleAPI[];
  pagination: BlogPaginationAPI;
  currentCategory: BlogCurrentCategoryAPI | null;
  filters: BlogFiltersAPI;
  hasPagination: boolean;
  currentPage: number;
  totalPages: number;
  count: number;
  totalCount: number;
}

// Type conversion utilities
export interface BlogTypeConverter {
  apiArticleToStoreArticle(apiArticle: BlogArticleAPI): BlogArticle;
  apiCategoryToStoreCategory(apiCategory: BlogCategoryAPI): BlogCategory;
  apiPaginationToStorePagination(apiPagination: BlogPaginationAPI): BlogPagination;
  apiFiltersToStoreFilters(apiFilters: BlogFiltersAPI): BlogFilters;
  apiCurrentCategoryToStoreCategory(apiCategory: BlogCurrentCategoryAPI | null): BlogCategory | null;
}

// Type guards for runtime validation
export interface BlogTypeGuards {
  isBlogArticlesResponse(data: unknown): data is BlogArticlesResponse;
  isBlogArticleAPI(data: unknown): data is BlogArticleAPI;
  isBlogCategoryAPI(data: unknown): data is BlogCategoryAPI;
  validateApiResponse(data: unknown): BlogArticlesResponse;
}

// Новые интерфейсы для фильтров и пагинации
export interface BlogFilters {
  page: number;           // integer|min:1|max:1000
  category: string;       // string|max:255|alpha_dash
  search: string;         // string|max:255
  sort: 'latest' | 'popular' | 'views';  // string|in:latest,popular,views
  direction: 'asc' | 'desc';             // string|in:asc,desc
}

export interface BlogPagination {
  total: number;
  perPage: number;
  currentPage: number;
  lastPage: number;
}

export interface BlogState {
  articles: BlogArticle[];
  currentArticle: BlogArticle | null;
  categories: BlogCategory[];
  currentCategory: BlogCategory | null;
  appMode: 'list' | 'single';
  loading: boolean;
  error: string | null;
  // Новые поля согласно архитектуре
  filters: BlogFilters;
  pagination: BlogPagination;
}

export interface BlogStoreAPI {
  // Состояния
  getArticles(): BlogArticle[];
  getCurrentArticle(): BlogArticle | null;
  getCategories(): BlogCategory[];
  getCurrentCategory(): BlogCategory | null;
  getAppMode(): 'list' | 'single';
  isLoading(): boolean;
  getError(): string | nulпl;
  // Новые геттеры для фильтров и пагинации
  getFilters(): BlogFilters;
  getPagination(): BlogPagination;
  
  // Мутации
  setArticles(articles: BlogArticle[]): void;
  setCurrentArticle(article: BlogArticle | null): void;
  setCategories(categories: BlogCategory[]): void;
  setCurrentCategory(category: Partial<BlogCategory> | null): void;
  setAppMode(mode: 'list' | 'single'): void;
  setLoading(loading: boolean): void;
  setError(error: string | null): void;
  // Новые сеттеры для фильтров и пагинации
  setFilters(filters: Partial<BlogFilters>): void;
  setPagination(pagination: Partial<BlogPagination>): void;
  setPopularPosts(popularPosts: BlogArticle[]): void;
  setTotalCount(totalCount: number): void;
  setCurrentPage(currentPage: number): void;
  setTotalPages(totalPages: number): void;
  setHasPagination(hasPagination: boolean): void;
  
  // Computed properties
  isFirstPage(): boolean;
  isLastPage(): boolean;
  hasResults(): boolean;
  hasActiveSearch(): boolean;
  hasActiveCategory(): boolean;
  
  // Новые computed properties для компонентов
  isArticleMode(): boolean;
  isListMode(): boolean;
  hasCurrentArticle(): boolean;
  
  // Hero article utilities
  getHeroArticle(): BlogArticle | null;
  hasHeroArticle(): boolean;
  
  // Regular articles (без hero)
  getRegularArticles(): BlogArticle[];
  
  // Utility methods для компонентов
  getArticleById(id: number): BlogArticle | null;
  getCategoryBySlug(slug: string): BlogCategory | null;
}

// URL API согласно архитектуре
export interface BlogURLAPI {
  getCurrentUrl(): string;
  isStateSynced(): boolean;
  updateUrl(pushState?: boolean): void;
  restoreFromUrl(): void;
  forceSync(): void;
}

export interface BlogOperationsAPI {
  loadArticles(params?: { category?: string; page?: number }): Promise<void>;
  loadArticle(slug: string): Promise<void>;
  loadCategories(): Promise<void>;
  returnToList(): void;
  loadRelatedArticle(slug: string): Promise<void>;
  // Новые операции согласно архитектуре
  goToPage(page: number): Promise<void>;
  goToNextPage(): Promise<void>;
  setCategory(category: string): Promise<void>;
  setSearch(search: string): Promise<void>;
  clearFilters(): Promise<void>;
  refreshContent(): Promise<void>;
  validateAndNavigate(params: Partial<BlogFilters>): Promise<void>;
}

// Типы для серверных данных
export interface BlogServerData {
  mode: 'list' | 'single';
  articles: BlogArticle[];
  heroArticle?: BlogArticle;
  categories: BlogCategory[];
  popularPosts: BlogArticle[];
  currentCategory?: BlogCategory;
  filters: {
    search: string;
    category: string;
    sort: string;
    direction: string;
    page: number;
  };
  pagination: {
    currentPage: number;
    totalPages: number;
    hasPagination: boolean;
    hasNext: boolean;
    hasPrev: boolean;
  };
  totalCount: number;
  currentPage: number;
  totalPages: number;
  hasPagination: boolean;
}

// Новые типы для событийной системы навигации
export interface BlogNavigationEventDetail {
  slug?: string;
  anchor?: string;
  categorySlug?: string;
  query?: string;
  page?: number;
}

export interface BlogCustomEvent extends CustomEvent {
  detail: BlogNavigationEventDetail;
}

// Типы для BlogNavigationManager
export interface BlogNavigationEvent {
  type: 'article' | 'category' | 'pagination' | 'search' | 'filter';
  action: string;
  data: any;
  element?: HTMLElement;
  originalEvent?: Event;
}

export interface BlogNavigationHandlers {
  [key: string]: (event: BlogNavigationEvent) => Promise<void>;
}

export interface BlogNavigationManagerAPI {
  addHandler(key: string, handler: (event: BlogNavigationEvent) => Promise<void>): void;
  removeHandler(key: string): void;
  getQueueLength(): number;
  isNavigating(): boolean;
}
