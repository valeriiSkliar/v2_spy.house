<div class="filter-section">
    <div class="multi-select" disabled="false">
        <div class="is-empty multi-select__tags">
            <span class="multi-select__placeholder">{{ $placeholder ?? 'Select options' }}</span>
        </div>
        <div class="multi-select__dropdown" style="display: none;">
            <div class="multi-select__search">
                <input type="text" placeholder="Search" class="multi-select__search-input">
            </div>
            <ul class="multi-select__options">
                @if(isset($options))
                @foreach($options as $option)
                <li class="">{{ $option }}</li>
                @endforeach
                @else
                <li class="">Option 1</li>
                <li class="">Option 2</li>
                <li class="">Option 3</li>
                @endif
            </ul>
        </div>
        <span class="multi-select__arrow"></span>
    </div>
</div>