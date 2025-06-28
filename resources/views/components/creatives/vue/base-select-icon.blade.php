@props([
'options' => [],
'value' => '',
'icon' => 'list',
'initialValue' => '12',
'placeholder' => 'Select option',
'onPageTranslations' => [
'onPage' => 'On page',
'perPage' => 'Per page',
],
])

{{-- Vue островок для интерактивного выбора количества креативов на странице --}}
<div data-vue-component="BaseSelect" data-vue-props="{{ json_encode([
        'options' => $options,
        'value' => $value,
        'icon' => $icon,
        'initialValue' => $initialValue,
        'placeholder' => $placeholder,
        'onPageTranslations' => $onPageTranslations
    ]) }}" class="vue-base-select-icon-container">
    {{-- Placeholder пока Vue компонент не загрузился --}}
    <div data-vue-placeholder class="base-select-icon">
        <div class="base-select">
            <div class="base-select__trigger"><span class="base-select__value">{{ $onPageTranslations['onPage'] }} —
                    {{ $initialValue }}</span><span class="base-select__arrow"></span></div>
            <ul class="base-select__dropdown" style="display: none;">
                <li class="base-select__option is-selected">12</li>
                <li class="base-select__option">24</li>
                <li class="base-select__option">48</li>
                <li class="base-select__option">96</li>
            </ul>
        </div>
        <span class="icon-{{ $icon }}"></span>
    </div>
</div>