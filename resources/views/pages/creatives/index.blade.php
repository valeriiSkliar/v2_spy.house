@extends('layouts.main')

@section('page-content')
<div>
    <h1>{{ __('creatives.title') }}</h1>
    {{-- Пример использования Vue 3 островков в Blade шаблонах --}}

    <div class="row align-items-center">
        <div class="col-12 col-md-auto mb-20 flex-grow-1">
            <div class="filter-push">
                <button class="filter-push__item active" data-tub="Push" data-group="push">Push <span
                        class="filter-push__count">170k</span></button>
                <button class="filter-push__item" data-tub="In Page" data-group="push">In Page <span
                        class="filter-push__count">3.1k</span></button>
                <button class="filter-push__item" data-tub="Facebook" data-group="push">Facebook <span
                        class="filter-push__count">65.1k</span></button>
                <button class="filter-push__item" data-tub="TikTok" data-group="push">TikTok <span
                        class="filter-push__count">45.2m</span></button>
            </div>
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

    <div data-vue-component="CreativesFiltersComponent" data-vue-props='{
        "initialFilters": {{ json_encode($filters) }}
    }'></div>
    {{-- Подключение скрипта Vue островков --}}
    @vite(['resources/js/vue-islands.ts'])

</div>
@endsection