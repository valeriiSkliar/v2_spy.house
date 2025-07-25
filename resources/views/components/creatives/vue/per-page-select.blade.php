@props([
'options' => [],
'translations' => [
'onPage' => 'На странице',
'perPage' => 'Элементов на странице',
],
'activePerPage' => 12,
])

{{-- Vue island for selecting the number of items per page --}}
<div data-vue-component="PerPageSelect" data-vue-props="{{ json_encode([
        'options' => $options,
        'translations' => $translations,
        'initialPerPage' => $activePerPage
    ]) }}" class="vue-per-page-select-container">
    {{-- Placeholder until Vue component is loaded --}}
    <div data-vue-placeholder class="base-select-icon">
        <div class="base-select">
            <div class="base-select__trigger">
                <span class="base-select__value">{{ $translations['onPage'] }} {{ $activePerPage }}</span>
                <span class="base-select__arrow"></span>
            </div>
            <ul class="base-select__dropdown" style="display: none;">
                @foreach($options as $option)
                <li class="base-select__option{{ (int)$option['value'] === (int)$activePerPage ? ' is-selected' : '' }}">
                    {{ $option['label'] }}
                </li>
                @endforeach
            </ul>
        </div>
        <span class="icon-list"></span>
    </div>
</div>