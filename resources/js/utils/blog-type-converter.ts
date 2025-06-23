/**
 * Blog Type Converter
 * Utilities for converting between API types and store types
 */

import type {
    BlogArticle,
    BlogCategory,
    BlogPagination,
    BlogFilters,
    BlogArticleAPI,
    BlogCategoryAPI,
    BlogPaginationAPI,
    BlogFiltersAPI,
    BlogCurrentCategoryAPI,
    BlogTypeConverter
} from '../types/blog.d';

export class BlogTypeConverterImpl implements BlogTypeConverter {
    /**
     * Convert API article to store article format
     */
    apiArticleToStoreArticle(apiArticle: BlogArticleAPI): BlogArticle {
        return {
            id: apiArticle.id,
            title: apiArticle.title,
            content: apiArticle.content,
            slug: apiArticle.slug,
            excerpt: apiArticle.excerpt,
            published_at: apiArticle.published_at,
            author: apiArticle.author?.name || '',
            category: apiArticle.category ? {
                id: apiArticle.category.id,
                name: apiArticle.category.name,
                slug: apiArticle.category.slug
            } : undefined
        };
    }

    /**
     * Convert API category to store category format
     */
    apiCategoryToStoreCategory(apiCategory: BlogCategoryAPI): BlogCategory {
        return {
            id: apiCategory.id,
            name: apiCategory.name,
            slug: apiCategory.slug
        };
    }

    /**
     * Convert API pagination to store pagination format
     */
    apiPaginationToStorePagination(apiPagination: BlogPaginationAPI): BlogPagination {
        return {
            total: apiPagination.total,
            perPage: apiPagination.perPage,
            currentPage: apiPagination.currentPage,
            lastPage: apiPagination.lastPage
        };
    }

    /**
     * Convert API filters to store filters format
     */
    apiFiltersToStoreFilters(apiFilters: BlogFiltersAPI): BlogFilters {
        return {
            page: apiFilters.page,
            category: apiFilters.category,
            search: apiFilters.search,
            sort: apiFilters.sort as 'latest' | 'popular' | 'views',
            direction: apiFilters.direction as 'asc' | 'desc'
        };
    }

    /**
     * Convert API current category to store category format
     */
    apiCurrentCategoryToStoreCategory(apiCategory: BlogCurrentCategoryAPI | null): BlogCategory | null {
        if (!apiCategory) return null;
        
        return {
            id: apiCategory.id,
            name: apiCategory.name,
            slug: apiCategory.slug
        };
    }

    /**
     * Convert multiple API articles to store articles
     */
    apiArticlesToStoreArticles(apiArticles: BlogArticleAPI[]): BlogArticle[] {
        return apiArticles.map(article => this.apiArticleToStoreArticle(article));
    }

    /**
     * Convert multiple API categories to store categories
     */
    apiCategoriesToStoreCategories(apiCategories: BlogCategoryAPI[]): BlogCategory[] {
        return apiCategories.map(category => this.apiCategoryToStoreCategory(category));
    }
}

// Create and export singleton instance
export const blogTypeConverter = new BlogTypeConverterImpl();