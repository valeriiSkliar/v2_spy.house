import { beforeEach, describe, expect, it, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { ref } from 'vue';

let creativesMock: any;
let urlSyncMock: any;
let filtersSyncMock: any;

vi.mock('@/composables/useCreatives', () => ({
  useCreatives: vi.fn(() => {
    creativesMock = {
      creatives: ref([]),
      pagination: ref({ currentPage: 1, lastPage: 1 }),
      meta: ref({}),
      isLoading: ref(false),
      error: ref(null),
      loadCreativesWithFilters: vi.fn(),
      loadNextPage: vi.fn(),
      refreshCreatives: vi.fn(),
      mapFiltersToCreativesFilters: vi.fn((filters, tab, page) => ({ filters, tab, page })),
    };
    return creativesMock;
  }),
}));

vi.mock('@/composables/useCreativesUrlSync', () => ({
  useCreativesUrlSync: vi.fn(() => {
    urlSyncMock = {
      state: ref({}),
      isEnabled: ref(true),
      syncFiltersToUrl: vi.fn(),
      syncUrlToFilters: vi.fn(() => ({ filters: {}, activeTab: 'push' })),
      hasUrlParams: vi.fn(() => false),
    };
    return urlSyncMock;
  }),
}));

vi.mock('@/composables/useFiltersSynchronization', () => ({
  useFiltersSynchronization: vi.fn(() => {
    filtersSyncMock = {
      isEnabled: ref(false),
      initialize: vi.fn(() => Promise.resolve()),
      syncToUrl: vi.fn(),
      syncFromUrl: vi.fn(),
      disable: vi.fn(),
      enable: vi.fn(),
    };
    return filtersSyncMock;
  }),
}));

import { useCreativesFiltersStore } from '@/stores/useFiltersStore';

describe('useCreativesFiltersStore edge cases', () => {
  let store: ReturnType<typeof useCreativesFiltersStore>;

  beforeEach(() => {
    const pinia = createPinia();
    setActivePinia(pinia);
    vi.clearAllMocks();
    store = useCreativesFiltersStore();
  });

  it('initializes with defaults and calls filterSync.initialize', async () => {
    expect(store.isInitialized).toBe(false);
    await store.initializeFilters();
    expect(filtersSyncMock.initialize).toHaveBeenCalled();
    expect(store.isInitialized).toBe(true);
  });

  it('converts object options in setSelectOptions', () => {
    store.setSelectOptions({
      advertisingNetworks: { google: 'Google' },
      languages: { en: 'English', fr: 'French' },
      devices: [{ value: 'desktop', label: 'Desktop' }],
    });

    expect(store.advertisingNetworksOptions).toEqual([
      { value: 'google', label: 'Google' },
    ]);
    expect(store.languagesOptions).toEqual([
      { value: 'en', label: 'English' },
      { value: 'fr', label: 'French' },
    ]);
    expect(store.devicesOptions).toEqual([
      { value: 'desktop', label: 'Desktop' },
    ]);
  });

  it('updateFilter does not mutate when value is unchanged', () => {
    const original = store.filters.country;
    store.updateFilter('country', original);
    expect(store.filters.country).toBe(original);
  });

  it('addToMultiSelect avoids duplicates', () => {
    store.addToMultiSelect('languages', 'en');
    store.addToMultiSelect('languages', 'en');
    expect(store.filters.languages).toEqual(['en']);
  });

  it('removeFromMultiSelect ignores missing values', () => {
    store.addToMultiSelect('languages', 'en');
    store.removeFromMultiSelect('languages', 'fr');
    expect(store.filters.languages).toEqual(['en']);
  });

  it('getTranslation supports nested keys with fallback', () => {
    store.setTranslations({
      tabs: { push: { title: 'Push Tab' } },
      filters: { advanced: { title: 'Advanced' } },
    } as any);

    expect(store.getTranslation('tabs.push.title')).toBe('Push Tab');
    expect(store.getTranslation('filters.advanced.title')).toBe('Advanced');
    expect(store.getTranslation('tabs.unknown', 'Fallback')).toBe('Fallback');
  });

  it('setActiveTab changes tab only when valid and different', () => {
    const dispatchSpy = vi.spyOn(document, 'dispatchEvent');
    store.setActiveTab('push');
    expect(dispatchSpy).not.toHaveBeenCalled();

    store.setActiveTab('facebook');
    expect(store.tabs.activeTab).toBe('facebook');
    expect(dispatchSpy).toHaveBeenCalled();

    dispatchSpy.mockRestore();
  });

  it('loadCreatives proxies to composables and url sync', async () => {
    await store.loadCreatives(2);
    expect(creativesMock.mapFiltersToCreativesFilters).toHaveBeenCalledWith(
      store.filters,
      store.tabs.activeTab,
      2,
    );
    expect(urlSyncMock.syncFiltersToUrl).toHaveBeenCalled();
    expect(creativesMock.loadCreativesWithFilters).toHaveBeenCalled();
  });
});
