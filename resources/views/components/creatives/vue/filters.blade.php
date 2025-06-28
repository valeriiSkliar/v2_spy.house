@props(['filters' => [], 'selectOptions' => [], 'filtersTranslations' => [], 'tabOptions' => []])
<div class="vue-component-wrapper" data-vue-component="CreativesFiltersComponent" data-vue-props='{
        "initialFilters": {{ json_encode($filters) }},
        "selectOptions": {{ json_encode($selectOptions) }},
        "translations": {{ json_encode($filtersTranslations) }},
        "tabOptions": {{ json_encode($tabOptions) }}
    }'>
    <div class="filters-placeholder" data-vue-placeholder>
        <div class="filter">
            <!-- Мобильный триггер placeholder -->
            <div class="filter__trigger-mobile d-md-none">
                <span class="btn-icon _dark _big _filter placeholder-shimmer">
                    <span class="icon-filter"></span>
                    <span class="icon-up font-24"></span>
                </span>
                Filter
            </div>

            <!-- Основной контент placeholder -->
            <div class="filter__content">
                <div class="row align-items-end">
                    <!-- Кнопка фильтров placeholder -->
                    <div class="col-12 col-md-auto mb-10 d-none d-md-block">
                        <div class="btn-icon _dark _big _filter placeholder-shimmer">
                            <span class="icon-filter"></span>
                            <span class="icon-up font-24"></span>
                        </div>
                    </div>

                    <div class="col-12 col-md-auto flex-grow-1 w-md-1">
                        <div class="row">
                            <!-- Поиск placeholder -->
                            <div class="col-12 col-lg-4 mb-10 placeholder-shimmer">
                                <div class="form-search">
                                    {{-- <span class="icon-search"></span> --}}
                                    <div class="placeholder-input"></div>
                                </div>
                            </div>

                            <!-- Селекты placeholder -->
                            <div class="col-12 col-md-6 col-lg-3 mb-10 w-lg-1 flex-grow-1 ml-2 placeholder-shimmer">
                                <div class="base-select placeholder-shimmer">
                                </div>
                            </div>

                            <div class="col-12 col-md-6 col-lg-3 mb-10 w-lg-1 flex-grow-1 ml-2 placeholder-shimmer">
                                <div class="base-select placeholder-shimmer">
                                </div>
                            </div>

                            <div class="col-12 col-md-12 col-lg-3 mb-10 w-lg-1 flex-grow-1 ml-2 placeholder-shimmer">
                                <div class="base-select placeholder-shimmer">

                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Кнопка сброса placeholder -->
                    <div class="col-12 col-md-auto mb-10 d-none d-md-block">
                        <div class="reset-btn">
                            <div class="btn-icon placeholder-shimmer">
                                {{-- <span class="icon-clear"></span> --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>