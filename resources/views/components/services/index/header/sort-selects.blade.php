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
        <x-common.base-select id="sort-by" :selected="$selectedSort" :options="$sortOptions"
            :placeholder="$sortOptionsPlaceholder" :icon="'sort'" />
        <span class="icon-sort remore_margin"></span>
    </div>
</div>
<div class="col-12 col-md-6 col-lg-auto mb-15">
    <div class="base-select-icon">
        <x-common.base-select id="services-per-page" :selected="$selectedPerPage" :options="$perPageOptions"
            :placeholder="$perPageOptionsPlaceholder" :icon="'list'" />
        <span class="icon-list remore_margin"></span>
    </div>
</div>