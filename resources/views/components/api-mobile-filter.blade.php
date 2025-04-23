@props(['navItems' => []])

<div class="filter d-xl-none">
    <div class="filter__trigger-mobile d-flex">
        <span class="btn-icon _green _big _filter">
            <span class="icon-list"></span>
            <span class="icon-up font-24"></span>
        </span>
        Navigation
    </div>
    <div class="filter__content _blog">
        <x-api-nav :navItems="$navItems" :showTitle="false" />
    </div>
</div>