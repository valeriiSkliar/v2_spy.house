<div class="filter-date-select">
    <div data-v-c57906f2="" class="date-picker-container" name="{{ $name ?? 'dateCreation' }}">
        <div data-v-c57906f2="" id="{{ $id ?? '' }}" class="date-select-field " role="button" aria-expanded="true">
            <span data-v-c57906f2="">{{ $placeholder ?? 'Date of creation' }}</span>
            <span data-v-c57906f2="" class="dropdown-arrow is-open"></span>
        </div>
        <div data-v-c57906f2="" class="date-options-dropdown" style="display: none;">
            <div data-v-c57906f2="" class="preset-ranges">
                <button data-v-c57906f2="" class="range-option">Today</button>
                <button data-v-c57906f2="" class="range-option">Yesterday</button>
                <button data-v-c57906f2="" class="range-option">Last 7 days</button>
                <button data-v-c57906f2="" class="range-option">Last 30 days</button>
                <button data-v-c57906f2="" class="range-option">This month</button>
                <button data-v-c57906f2="" class="range-option">Last month</button>
                <button data-v-c57906f2="" class="range-option active">Custom Range</button>
            </div>
        </div>
    </div>
    <span class="icon-date"></span>
</div>