@props([
'sortOptions' => [],
'perPageOptions' => [],
'selectedSort' => [],
'selectedPerPage' => [],
'filters' => [],
'sortOptionsPlaceholder' => '',
'perPageOptionsPlaceholder' => '',
])

<div class="row align-items-center">
    <div class="col-12 col-md-6 col-lg-auto mr-auto">
        <h1>{{ __('landings.index.title') }}</h1>
    </div>
    <form id="landings-sort-form"
        class="d-flex col-12 col-md-6 col-lg-auto align-items-center flex-wrap justify-content-end"
        data-form-type="ajax" data-update-method="ajax">
        <div class="col-12 col-md-6 col-lg-auto mb-15">
            <div class="base-select-icon">
                <x-common.base-select id="sort-by" :selected="$selectedSort" :options="$sortOptions"
                    :placeholder="$sortOptionsPlaceholder" :icon="'sort'" />
                <span class="icon-sort"></span>
                <input type="hidden" name="sort" value="{{ $selectedSort['value'] ?? '' }}">
                <input type="hidden" name="direction" value="{{ $selectedSort['order'] ?? '' }}">
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-auto ms-2 mb-15">
            <div class="base-select-icon">
                <x-common.base-select :selected="$selectedPerPage" :options="$perPageOptions"
                    :placeholder="$perPageOptionsPlaceholder" id="items-per-page" :icon="'list'" />
                <span class="icon-list"></span>
                <input type="hidden" name="per_page" value="{{ $selectedPerPage['value'] ?? '' }}">
            </div>
        </div>
    </form>
</div>