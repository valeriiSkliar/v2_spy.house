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
    <div class="col-12 col-lg-auto mr-auto">
        <h1>{{ __('landings.index.title') }}</h1>
    </div>
    <div class="col-12 col-md-6 col-lg-auto mb-15">
        <div class="base-select-icon">
            <x-common.base-select
                id="sort-select"
                :selected="$selectedSort" 
                :options="$sortOptions"
                :placeholder="$sortOptionsPlaceholder"
                :icon="'sort'"
             />
             <span class="icon-sort"></span>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-auto mb-15">
        <div class="base-select-icon">
            <x-common.base-select
                :selected="$selectedPerPage"
                :options="$perPageOptions"
                :placeholder="$perPageOptionsPlaceholder"
                id="per-page-select"
                :icon="'list'"
             />
             <span class="icon-list"></span>
        </div>
    </div>
</div>