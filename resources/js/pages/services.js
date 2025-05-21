import { initializeSelectComponent } from '@/helpers';
import { initializeServiceComponents } from '../components';

document.addEventListener('DOMContentLoaded', function () {
  // --- Initialize Components ---

  // Service Components
  initializeServiceComponents();
  // Sort By
  initializeSelectComponent('#sort-by', {
    selectors: {
      select: '.base-select__dropdown',
      options: '.base-select__option',
      trigger: '.base-select__trigger',
      valueElement: '[data-value]',
      orderElement: '[data-order]',
    },
    params: {
      valueParam: 'sortBy',
      orderParam: 'sortOrder',
    },
    resetPage: true,
  });

  // Per Page
  initializeSelectComponent('#services-per-page', {
    selectors: {
      select: '.base-select__dropdown',
      options: '.base-select__option',
      trigger: '.base-select__trigger',
      valueElement: '[data-value]',
      // No order element for perPage
    },
    params: {
      valueParam: 'perPage',
      // No order param
    },
    resetPage: true,
  });

  // Category Filter
  initializeSelectComponent('#category-filter', {
    selectors: {
      select: '.base-select__dropdown',
      options: '.base-select__option',
      trigger: '.base-select__trigger',
      valueElement: '[data-value]',
    },
    params: {
      valueParam: 'category',
    },
    resetPage: false, // Do not reset page for filters
  });

  // Bonuses Filter
  initializeSelectComponent('#bonuses-filter', {
    selectors: {
      select: '.base-select__dropdown',
      options: '.base-select__option',
      trigger: '.base-select__trigger',
      valueElement: '[data-value]',
    },
    params: {
      valueParam: 'bonuses',
    },
    resetPage: false, // Do not reset page for filters
  });
});
