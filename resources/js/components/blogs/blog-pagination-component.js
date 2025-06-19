/**
 * Blog Pagination Component
 * Alpine.js компонент для динамической пагинации блога
 * Интегрируется с blog-store и blog-ajax-manager
 */

import { debagConfig } from '../../config.js';

import Alpine from 'alpinejs';

export function initBlogPaginationComponent() {
  Alpine.data('blogPagination', () => ({
    // Локальное состояние компонента
    loading: false,

    // Computed properties из store
    get currentPage() {
      return this.$store.blog?.pagination?.currentPage || 1;
    },

    get totalPages() {
      return this.$store.blog?.pagination?.totalPages || 1;
    },

    get hasPagination() {
      return this.$store.blog?.pagination?.hasPagination || false;
    },

    get hasNext() {
      return this.$store.blog?.pagination?.hasNext || false;
    },

    get hasPrev() {
      return this.$store.blog?.pagination?.hasPrev || false;
    },

    get isFirstPage() {
      return this.currentPage === 1;
    },

    get isLastPage() {
      return this.currentPage === this.totalPages;
    },

    // Навигационные методы
    goToPage(page) {
      if (this.loading || page === this.currentPage) return;

      const targetPage = parseInt(page);
      if (targetPage < 1 || targetPage > this.totalPages) return;

      console.log(`Navigating to page ${targetPage}`);

      this.loading = true;

      // Используем magic method $blog для навигации
      this.$blog.goToPage(targetPage);

      // Сбрасываем loading после небольшой задержки
      const self = this;
      setTimeout(function () {
        self.loading = false;
      }, 100);
    },

    goToNext() {
      if (this.hasNext && !this.loading) {
        this.goToPage(this.currentPage + 1);
      }
    },

    goToPrev() {
      if (this.hasPrev && !this.loading) {
        this.goToPage(this.currentPage - 1);
      }
    },

    goToFirst() {
      if (!this.isFirstPage && !this.loading) {
        this.goToPage(1);
      }
    },

    goToLast() {
      if (!this.isLastPage && !this.loading) {
        this.goToPage(this.totalPages);
      }
    },

    // Методы для генерации списка страниц
    getVisiblePages() {
      const current = this.currentPage;
      const total = this.totalPages;
      const delta = 2; // Количество страниц до и после текущей

      if (total <= 7) {
        // Если страниц мало, показываем все
        return Array.from({ length: total }, function (_, i) {
          return i + 1;
        });
      }

      const left = Math.max(1, current - delta);
      const right = Math.min(total, current + delta);
      const pages = [];

      // Добавляем первую страницу
      if (left > 1) {
        pages.push(1);
        if (left > 2) {
          pages.push('...');
        }
      }

      // Добавляем видимые страницы
      for (let i = left; i <= right; i++) {
        pages.push(i);
      }

      // Добавляем последнюю страницу
      if (right < total) {
        if (right < total - 1) {
          pages.push('...');
        }
        pages.push(total);
      }

      return pages;
    },

    // Обработчики событий
    handlePageClick(event, page) {
      event.preventDefault();
      event.stopPropagation();

      if (typeof page === 'number') {
        this.goToPage(page);
      }
    },

    handleKeydown(event, page) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        this.handlePageClick(event, page);
      }
    },

    // Методы для стилизации
    getPageClasses(page) {
      const baseClasses = 'pagination-link';

      if (page === '...') {
        return baseClasses + ' ellipsis';
      }

      if (page === this.currentPage) {
        return baseClasses + ' active';
      }

      return baseClasses;
    },

    getNavClasses(direction) {
      const baseClasses = 'pagination-link ' + direction;

      if (direction === 'prev' && this.isFirstPage) {
        return baseClasses + ' disabled';
      }

      if (direction === 'next' && this.isLastPage) {
        return baseClasses + ' disabled';
      }

      return baseClasses;
    },

    // Инициализация компонента
    init() {
      console.log('Blog pagination component initialized');

      const self = this;

      // Слушаем изменения в store для обновления состояния
      this.$watch('$store.blog.pagination', function () {
        console.log('Pagination state updated:', self.$store.blog.pagination);
      });

      // Слушаем изменения loading состояния
      this.$watch('$store.blog.loading', function (loading) {
        if (!loading) {
          self.loading = false;
        }
      });
    },

    // Debug методы (только для разработки)
    debug() {
      if (debagConfig.debug) {
        console.log('Pagination Debug Info:', {
          currentPage: this.currentPage,
          totalPages: this.totalPages,
          hasPagination: this.hasPagination,
          hasNext: this.hasNext,
          hasPrev: this.hasPrev,
          loading: this.loading,
          visiblePages: this.getVisiblePages(),
        });
      }
    },
  }));
}
