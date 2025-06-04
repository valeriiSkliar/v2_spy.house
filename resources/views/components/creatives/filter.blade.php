<div class="filter">
    <div class="filter__trigger-mobile">
        <span class="btn-icon _dark _big _filter">
            <span class="icon-filter remore_margin"></span>
            <span class="icon-up remore_margin font-24"></span>
        </span>
        {{ __('creatives.filter.title') }}
    </div>
    <div class="filter__content">
        <div class="row align-items-end">
            <div class="col-12 col-md-auto mb-10 d-none d-md-block">
                <button class="btn-icon _dark _big _filter js-toggle-detailed-filtering">
                    <span class="icon-filter remore_margin"></span>
                    <span class="icon-up remore_margin font-24"></span>
                </button>
            </div>
            <div class="col-12 col-md-auto flex-grow-1 w-md-1">
                <div class="row">
                    <div class="col-12 col-lg-4 mb-10">
                        <x-ui.base-search-form :placeholder="__('creatives.filter.search.placeholder')" />
                    </div>
                    <div class="col-12 col-md-6 col-lg-3 mb-10 w-lg-1 flex-grow-1">
                        <x-common.base-select :placeholder="__('creatives.filter.country.placeholder')" {{--
                            :selected="['value' => 'all', 'order' => '1', 'label' => __('creatives.filter.country.all')]"
                            --}} :options="[
                        ['value' => 'all', 'order' => '1', 'label' => __('creatives.filter.country.all')],
                        ['value' => 'united-states', 'order' => '2', 'label' => 'United States'],
                        ['value' => 'canada', 'order' => '3', 'label' => 'Canada'],
                        ['value' => 'united-kingdom', 'order' => '4', 'label' => 'United Kingdom'],
                        ['value' => 'australia', 'order' => '5', 'label' => 'Australia'],
                        ['value' => 'new-zealand', 'order' => '6', 'label' => 'New Zealand'],
                        ['value' => 'other-countries', 'order' => '7', 'label' => 'Other Countries']
                        ]" />
                    </div>
                    <div class="col-12 col-md-6 col-lg-3 mb-10 w-lg-1 flex-grow-1">
                        {{-- TODO: refactor to x-ui.date-picker component syntax --}}
                        @include('components.ui.date-picker', [
                        'name' => 'dateCreation',
                        'id' => 'dateCreation',
                        'placeholder' => __('creatives.filter.date.date-of-creation')
                        ])
                    </div>
                    <div class="col-12 col-md-12 col-lg-3 mb-10 w-lg-1 flex-grow-1">
                        <x-common.base-select :placeholder="__('creatives.filter.sort.placeholder')" :options="[
                        ['value' => 'by-creation-date', 'order' => '1', 'label' => __('creatives.filter.sort.by-creation-date')],
                        ['value' => 'by-days-of-activity', 'order' => '2', 'label' => __('creatives.filter.sort.by-days-of-activity')]
                        ]" />
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-auto mb-10 d-none d-md-block">
                <div class="reset-btn">
                    <button class="btn-icon"><span class="icon-clear remore_margin"></span> <span
                            class="ml-2 d-md-none">{{
                            __('creatives.filter.reset') }}</span></button>
                </div>
            </div>
        </div>
        <div class="filter__detailed" style="display: none;">
            <div class="filter__title">{{ __('creatives.filter.detailed-filtering') }}</div>
            <div class="row">
                <div class="col-12 col-md-6 col-lg-3 mb-15">
                    {{-- TODO: refactor to x-ui.date-picker component syntax --}}
                    @include('components.ui.date-picker', [
                    'name' => 'dateCreation',
                    'placeholder' => __('creatives.filter.date.period-of-display')
                    ])
                </div>
                <div class="col-12 col-md-6 col-lg-3 mb-15">
                    {{-- TODO: refactor to x-ui.date-picker component syntax --}}
                    @include('components.ui.multi-select', [
                    'placeholder' => __('creatives.filter.advertising-networks')
                    ])
                </div>
                <div class="col-12 col-md-6 col-lg-3 mb-15">
                    {{-- TODO: refactor to x-ui.multi-select component syntax --}}
                    @include('components.ui.multi-select', [
                    'placeholder' => __('creatives.filter.languages')
                    ])
                </div>
                <div class="col-12 col-md-6 col-lg-3 mb-15">
                    {{-- TODO: refactor to x-ui.multi-select component syntax --}}
                    @include('components.ui.multi-select', [
                    'placeholder' => __('creatives.filter.operation-systems')
                    ])
                </div>
                <div class="col-12 col-md-6 col-lg-3 mb-15">
                    {{-- TODO: refactor to x-ui.multi-select component syntax --}}
                    @include('components.ui.multi-select', [
                    'placeholder' => __('creatives.filter.browsers')
                    ])
                </div>
                <div class="col-12 col-md-6 col-lg-3 mb-15">
                    {{-- TODO: refactor to x-ui.multi-select component syntax --}}
                    @include('components.ui.multi-select', [
                    'placeholder' => __('creatives.filter.devices')
                    ])
                </div>
                <div class="col-12 col-md-6 col-lg-3 mb-15">
                    {{-- TODO: refactor to x-ui.multi-select component syntax --}}
                    @include('components.ui.multi-select', [
                    'placeholder' => __('creatives.filter.image-sizes')
                    ])
                </div>
                <div class="col-12 col-md-6 col-lg-3 mb-15">
                    <label class="checkbox-toggle _with-background">
                        <span class="icon-18 remore_margin font-20"></span>
                        <span class="mr-auto">{{ __('creatives.filter.only-adult') }}</span>
                        <input type="checkbox" id="adult">
                        <span class="checkbox-toggle-visible"></span>
                    </label>
                </div>
                <div class="col-12 col-md-6 col-lg-3 mb-15">
                    {{-- TODO: this is not a multi-select, it's a base-select --}}
                    @include('components.ui.multi-select', [
                    'placeholder' => __('creatives.filter.saved-settings')
                    ])`
                </div>
                <div class="col-12 col-md-auto mb-10">
                    <button class="btn _flex _dark _medium w-100"><span class="icon-save mr-2 font-16"></span>
                        {{ __('creatives.filter.save-settings') }}</button>
                </div>
            </div>
            <div class="reset-btn d-md-none">
                <button class="btn-icon"><span class="icon-clear"></span> <span class="ml-2 d-md-none">{{
                        __('creatives.filter.reset') }}</span></button>
            </div>
        </div>
    </div>
</div>