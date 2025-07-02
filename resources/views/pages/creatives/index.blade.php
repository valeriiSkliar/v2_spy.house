@extends('layouts.main')

@section('page-content')
<div>
    <h1>{{ __('creatives.title') }}</h1>
    {{-- Пример использования Vue 3 островков в Blade шаблонах --}}

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
                        <x-creatives.vue.favorites-counter :initialCount="43" :translations="[
                                'favoritesCountTooltip' => 'Количество избранных креативов. Нажмите для обновления.'
                            ]" />
                    </a>
                </div>
                <div class="col-12 col-md-auto mb-15">
                    {{-- Компонент выбора количества креативов на странице --}}
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

    {{-- DEBUG: Проверим что передается в filtersTranslations --}}
    {{-- @dump($filtersTranslations) --}}

    <x-creatives.vue.filters :filters="$filters" :selectOptions="$selectOptions"
        :filtersTranslations="$filtersTranslations" :tabOptions="$tabOptions" />

    {{-- Компонент списка креативов с новой системой композаблов --}}

    <x-creatives.vue.list :listTranslations="$listTranslations" :perPage="12" :activeTab="$activeTab"
        :detailsTranslations="$detailsTranslations ?? []" :showSimilarCreatives="false"
        :cardTranslations="$cardTranslations ?? []" />


    {{-- Компонент пагинации --}}
    <x-creatives.vue.pagination :translations="$listTranslations" :showInfo="false" :maxVisiblePages="5"
        :alwaysShowFirstLast="true" />

</div>


{{-- Подключение скрипта Vue островков --}}
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