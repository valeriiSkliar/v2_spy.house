@props([
    'sortOptions' => [],
    'perPageOptions' => [],
    'selectedSort' => [],
    'selectedPerPage' => [],
    'filters' => [],
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
                 />
             <span class="icon-sort"></span>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-auto mb-15">
        <div class="base-select-icon">
            <x-common.base-select
                id="per-page-select"
                :selected="$selectedPerPage"
                :options="$perPageOptions"
                 />
             <span class="icon-list"></span>
        </div>
    </div>
</div>