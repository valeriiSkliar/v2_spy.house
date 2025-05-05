@props([
    'sortOptions' => [],
    'perPageOptions' => [],
    'selectedSort' => ['value' => '', 'order' => '', 'label' => ''],
    'selectedPerPage' => ['value' => '', 'order' => '', 'label' => ''],
    'filters' => [],
    'sortOptionsPlaceholder' => '',
    'perPageOptionsPlaceholder' => '',
])

<div class="col-12 col-md-6 col-lg-auto mb-15">
    <div class="base-select-icon">
        <x-common.base-select
                id="sort-by"
                {{-- :selected="[
                    'value' => $filters['sortBy'] ?? 'default',
                    'order' => $filters['sortOrder'] ?? 'asc',
                    'label' => 'Sort by — ' . ($filters['sortBy'] ?? 'Transitions High to Low')
                ]" --}}
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
            id="per-page"
            {{-- :selected="[
                'value' => $filters['perPage'] ?? '12',
                'order' => '',
                'label' => 'On page — ' . ($filters['perPage'] ?? '12')
            ]" --}}
            :selected="$selectedPerPage"
            :options="$perPageOptions" 
            :placeholder="$perPageOptionsPlaceholder"
            :icon="'list'"
        />
        <span class="icon-list"></span>
    </div>
</div>