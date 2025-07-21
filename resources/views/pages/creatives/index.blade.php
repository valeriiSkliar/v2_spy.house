@extends('layouts.main-app')

@section('page-content')
<div>
    <h1>{{ __('creatives.title') }}</h1>
    {{-- Example of using Vue 3 islands in Blade templates --}}

    <div class="row align-items-center">
        <div class="col-12 col-md-auto mb-20 flex-grow-1">
            <x-creatives.vue.tabs :initialTabs="$tabs" :tabOptions="$tabOptions"
                :tabsTranslations="$tabsTranslations" />
        </div>
        <div class="col-12 col-md-auto mb-2">
            <div class="row">
                <div class="col-12 col-md-auto mb-15">
                    <a href="#" class="btn justify-content-start _flex w-100 _medium _gray">
                        <span class="icon-favorite-empty font-16 mr-2"></span>
                        {{ __('creatives.favorites') }}
                        <x-creatives.vue.favorites-counter :initialCount="$favoritesCount" :translations="[
                                'favoritesCountTooltip' => 'Количество избранных креативов. Нажмите для обновления.'
                            ]" />
                    </a>
                </div>
                <div class="col-12 col-md-auto mb-15">
                    {{-- Component for selecting the number of creatives per page --}}
                    <x-creatives.vue.per-page-select :options="$perPage['perPageOptions']"
                        :activePerPage="$perPage['activePerPage']" :translations="[
                            'onPage' => $listTranslations['onPage'] ?? 'На странице',
                            'perPage' => $listTranslations['perPage'] ?? 'Элементов на странице'
                        ]" />
                </div>
            </div>
        </div>
    </div>
    {{-- @dd($selectOptions) --}}

    {{-- DEBUG: Check what is passed to filtersTranslations --}}
    {{-- @dump($filtersTranslations) --}}

    <x-creatives.vue.filters :filters="$filters" :selectOptions="$selectOptions"
        :filtersTranslations="$filtersTranslations" :tabOptions="$tabOptions" />

    {{-- Search count component - Vue Island --}}
    <x-creatives.vue.search-count :initialCount="$searchCount" />

    {{-- Creatives list component with new composables system --}}

    <x-creatives.vue.list :listTranslations="$listTranslations" :perPage="12" :activeTab="$activeTab"
        :detailsTranslations="$allTranslations ?? []"
        :showSimilarCreatives="$userData['show_similar_creatives'] ?? false" :cardTranslations="$cardTranslations ?? []"
        :userData="$userData" />


    {{-- Pagination component --}}
    <x-creatives.vue.pagination :translations="$listTranslations" :showInfo="false" :maxVisiblePages="5"
        :alwaysShowFirstLast="true" />

</div>


{{-- Vue islands script connection --}}
@vite(['resources/js/vue-islands.ts'])

{{--
Пример конфигурации Vue Islands (опционально):

<script type="module">
    import { configureVueIslands } from '@/vue-islands';
    
    // Development конфигурация (по умолчанию)
    configureVueIslands({
        cleanupProps: true,           // Очищать props после инициализации
        cleanupDelay: 1000,          // Задержка перед очисткой
        preservePropsInDev: true,    // Сохранять props в development режиме
    });
    
    // Продакшн конфигурация
    configureVueIslands({
        cleanupProps: true,
        cleanupDelay: 500,           // Быстрая очистка
        preservePropsInDev: false,   // Очищать даже в dev
    });
</script>
--}}

@endsection