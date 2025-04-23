@props(['placeholder' => 'Select', 'selected' => [], 'options' => []])

<div class="multi-select" disabled="false">
    @if(count($selected) === 0)
    <div class="is-empty multi-select__tags">
        <span class="multi-select__placeholder">{{ $placeholder }}</span>
    </div>
    @else
    <div class="multi-select__tags">
        @foreach($selected as $item)
        <span class="multi-select__tag">{{ $item }} <button type="button" class="multi-select__remove"> × </button></span>
        @endforeach
    </div>
    @endif
    <div class="multi-select__dropdown" style="display: none;">
        <div class="multi-select__search">
            <input type="text" placeholder="Search" class="multi-select__search-input">
        </div>
        <ul class="multi-select__options">
            @foreach($options as $option)
            <li class="{{ in_array($option, $selected) ? 'selected' : '' }}"><!----> {{ $option }}</li>
            @endforeach
        </ul>
    </div>
    <span class="multi-select__arrow"></span>
</div>