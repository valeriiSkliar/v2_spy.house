@props([
'sortOptions' => [],
'perPageOptions' => [],
'selectedSort' => [],
'selectedPerPage' => [],
'filters' => [],
'sortOptionsPlaceholder' => '',
'perPageOptionsPlaceholder' => '',
])

<form id="landings-sort-form" data-form-type="ajax" data-update-method="ajax">
    <div class="row align-items-center">
        <div class="col-12 col-md-6 col-lg-auto mr-auto">
            <h1>{{ __('landings.index.title') }}</h1>
        </div>
        <div class="col-12 col-md-6 col-lg-auto mb-15">
            <div class="base-select-icon">
                <x-common.base-select id="sort-by" :selected="$selectedSort" :options="$sortOptions"
                    :placeholder="$sortOptionsPlaceholder" :icon="'sort'" />
                <span class="icon-sort remore_margin"></span>
                <input type="hidden" name="sort" value="{{ $selectedSort['value'] ?? '' }}">
                <input type="hidden" name="direction" value="{{ $selectedSort['order'] ?? '' }}">
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-auto mb-15">
            <div class="base-select-icon">
                <x-common.base-select :selected="$selectedPerPage" :options="$perPageOptions"
                    :placeholder="$perPageOptionsPlaceholder" id="items-per-page" :icon="'list'" />
                <span class="icon-list remore_margin"></span>
                <input type="hidden" name="per_page" value="{{ $selectedPerPage['value'] ?? '' }}">
            </div>
        </div>
    </div>
</form>