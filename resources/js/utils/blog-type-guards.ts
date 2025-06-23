/**
 * Blog Type Guards
 * Runtime validation utilities for blog types
 */

import type {
    BlogArticleAPI,
    BlogArticlesResponse,
    BlogCategoryAPI,
    BlogCurrentCategoryAPI,
    BlogFiltersAPI,
    BlogPaginationAPI,
    BlogTypeGuards
} from '../types/blog.d';

export class BlogTypeGuardsImpl implements BlogTypeGuards {
    /**
     * Type guard for BlogArticleAPI
     */
    isBlogArticleAPI(data: unknown): data is BlogArticleAPI {
        if (!data || typeof data !== 'object') return false;
        
        const article = data as Record<string, unknown>;
        
        return (
            typeof article.id === 'number' &&
            typeof article.title === 'string' &&
            typeof article.excerpt === 'string' &&
            typeof article.content === 'string' &&
            typeof article.slug === 'string' &&
            typeof article.views_count === 'number' &&
            typeof article.published_at === 'string' &&
            typeof article.thumbnail === 'string' &&
            typeof article.average_rating === 'string' &&
            (article.category === null || this.isBlogCategoryReference(article.category)) &&
            (article.author === null || this.isBlogAuthorReference(article.author))
        );
    }

    /**
     * Type guard for BlogCategoryAPI
     */
    isBlogCategoryAPI(data: unknown): data is BlogCategoryAPI {
        if (!data || typeof data !== 'object') return false;
        
        const category = data as Record<string, unknown>;
        
        return (
            typeof category.id === 'number' &&
            typeof category.name === 'string' &&
            typeof category.slug === 'string' &&
            typeof category.posts_count === 'number'
        );
    }

    /**
     * Type guard for BlogPaginationAPI
     */
    isBlogPaginationAPI(data: unknown): data is BlogPaginationAPI {
        if (!data || typeof data !== 'object') return false;
        
        const pagination = data as Record<string, unknown>;
        
        return (
            typeof pagination.total === 'number' &&
            typeof pagination.perPage === 'number' &&
            typeof pagination.currentPage === 'number' &&
            typeof pagination.lastPage === 'number'
        );
    }

    /**
     * Type guard for BlogCurrentCategoryAPI
     */
    isBlogCurrentCategoryAPI(data: unknown): data is BlogCurrentCategoryAPI {
        if (!data || typeof data !== 'object') return false;
        
        const category = data as Record<string, unknown>;
        
        return (
            typeof category.id === 'number' &&
            typeof category.name === 'string' &&
            typeof category.slug === 'string'
        );
    }

    /**
     * Type guard for BlogFiltersAPI
     */
    isBlogFiltersAPI(data: unknown): data is BlogFiltersAPI {
        if (!data || typeof data !== 'object') return false;
        
        const filters = data as Record<string, unknown>;
        
        return (
            typeof filters.page === 'number' &&
            typeof filters.category === 'string' &&
            typeof filters.search === 'string' &&
            typeof filters.sort === 'string' &&
            typeof filters.direction === 'string'
        );
    }

    /**
     * Type guard for full BlogArticlesResponse
     */
    isBlogArticlesResponse(data: unknown): data is BlogArticlesResponse {
        if (!data || typeof data !== 'object') return false;
        
        const response = data as Record<string, unknown>;
        
        return (
            typeof response.success === 'boolean' &&
            typeof response.mode === 'string' &&
            Array.isArray(response.articles) &&
            response.articles.every(article => this.isBlogArticleAPI(article)) &&
            (response.heroArticle === null || this.isBlogArticleAPI(response.heroArticle)) &&
            Array.isArray(response.categories) &&
            response.categories.every(category => this.isBlogCategoryAPI(category)) &&
            Array.isArray(response.popularPosts) &&
            response.popularPosts.every(post => this.isBlogArticleAPI(post)) &&
            this.isBlogPaginationAPI(response.pagination) &&
            (response.currentCategory === null || this.isBlogCurrentCategoryAPI(response.currentCategory)) &&
            this.isBlogFiltersAPI(response.filters) &&
            typeof response.hasPagination === 'boolean' &&
            typeof response.currentPage === 'number' &&
            typeof response.totalPages === 'number' &&
            typeof response.count === 'number' &&
            typeof response.totalCount === 'number'
        );
    }

    /**
     * Validate and ensure BlogArticlesResponse with detailed error messages
     */
    validateApiResponse(data: unknown): BlogArticlesResponse {
        if (!this.isBlogArticlesResponse(data)) {
            const issues = this.getValidationIssues(data);
            throw new Error(`Invalid API response structure: ${issues.join(', ')}`);
        }
        return data;
    }

    /**
     * Helper method to check category reference structure in articles
     */
    private isBlogCategoryReference(data: unknown): boolean {
        if (!data || typeof data !== 'object') return false;
        
        const category = data as Record<string, unknown>;
        return (
            typeof category.id === 'number' &&
            typeof category.name === 'string' &&
            typeof category.slug === 'string'
        );
    }

    /**
     * Helper method to check author reference structure in articles
     */
    private isBlogAuthorReference(data: unknown): boolean {
        if (!data || typeof data !== 'object') return false;
        
        const author = data as Record<string, unknown>;
        return (
            typeof author.id === 'number' &&
            typeof author.name === 'string' &&
            (author.avatar === null || typeof author.avatar === 'string')
        );
    }

    /**
     * Get detailed validation issues for debugging
     */
    private getValidationIssues(data: unknown): string[] {
        const issues: string[] = [];
        
        if (!data || typeof data !== 'object') {
            issues.push('data is not an object');
            return issues;
        }
        
        const response = data as Record<string, unknown>;
        
        if (typeof response.success !== 'boolean') {
            issues.push('success field is missing or not boolean');
        }
        
        if (typeof response.mode !== 'string') {
            issues.push('mode field is missing or not string');
        }
        
        if (!Array.isArray(response.articles)) {
            issues.push('articles field is missing or not array');
        } else if (!response.articles.every(article => this.isBlogArticleAPI(article))) {
            issues.push('articles array contains invalid article objects');
        }
        
        if (!Array.isArray(response.categories)) {
            issues.push('categories field is missing or not array');
        } else if (!response.categories.every(category => this.isBlogCategoryAPI(category))) {
            issues.push('categories array contains invalid category objects');
        }
        
        if (!this.isBlogPaginationAPI(response.pagination)) {
            issues.push('pagination field is missing or invalid');
        }
        
        if (!this.isBlogFiltersAPI(response.filters)) {
            issues.push('filters field is missing or invalid');
        }
        
        return issues;
    }
}

// Create and export singleton instance
export const blogTypeGuards = new BlogTypeGuardsImpl();