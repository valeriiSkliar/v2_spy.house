<div class="filter-date-select">
    <div class="date-picker-container" name="{{ $name ?? 'dateCreation' }}">
        <div class="date-select-field " role="button" aria-expanded="true">
            <x-common.date-piker.date-flatpicker-form-field :id="$id" :type="'date'" :label="$label" :name="$name"
                :placeholder="$placeholder" />
        </div>
        <div class="date-options-dropdown" style="display: none;">
            <div class="preset-ranges">
                <button class="range-option">Today</button>
                <button class="range-option">Yesterday</button>
                <button class="range-option">Last 7 days</button>
                <button class="range-option">Last 30 days</button>
                <button class="range-option">This month</button>
                <button class="range-option">Last month</button>
                <button class="range-option active">Custom Range</button>
            </div>
        </div>
    </div>
    <span class="icon-date"></span>
</div>