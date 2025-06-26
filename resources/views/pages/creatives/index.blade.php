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
                    <a href="#" class="btn justify-content-start _flex w-100 _medium _gray"><span
                            class="icon-favorite-empty font-16 mr-2"></span>Favorites <span
                            class="btn__count">31</span></a>
                </div>
                <div class="col-12 col-md-auto mb-15">
                    <div class="base-select-icon">
                        <div class="base-select">
                            <div class="base-select__trigger"><span class="base-select__value">On page — 12</span><span
                                    class="base-select__arrow"></span></div>
                            <ul class="base-select__dropdown" style="display: none;">
                                <li class="base-select__option is-selected">12</li>
                                <li class="base-select__option">24</li>
                                <li class="base-select__option">48</li>
                                <li class="base-select__option">96</li>
                            </ul>
                        </div>
                        <span class="icon-list"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- @dd($selectOptions) --}}

    <x-creatives.vue.filters :filters="$filters" :selectOptions="$selectOptions"
        :filtersTranslations="$filtersTranslations" :tabOptions="$tabOptions" />

    {{-- Компонент списка креативов с новой системой композаблов --}}

    <x-creatives.vue.list :listTranslations="$listTranslations" :perPage="$selectOptions['perPage']" />

    {{-- Компонент пагинации --}}
    <x-creatives.vue.pagination :translations="$listTranslations" :showInfo="true" :maxVisiblePages="5"
        :alwaysShowFirstLast="true" />


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

</div>
@endsection