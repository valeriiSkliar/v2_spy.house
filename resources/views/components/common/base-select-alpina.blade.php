{{--
Улучшенный базовый select компонент с автоматической синхронизацией с Alpine store

Примеры использования:

1. Простое использование без store:
<x-common.base-select-alpina id="simple-select"
    :options="[['value' => '1', 'label' => 'Option 1'], ['value' => '2', 'label' => 'Option 2']]"
    placeholder="Выберите опцию" />

2. С автоматической синхронизацией со store:
<x-common.base-select-alpina id="per-page-select" :options="$perPageOptions" :initial-selected-value="$initialPerPage"
    placeholder="На странице" icon="list" store-path="creatives.perPage" />

3. С флагами стран:
<x-common.base-select-alpina id="country-select" :options="$countries" placeholder="Выберите страну" :with-flag="true"
    store-path="filters.selectedCountry" />

--}}

@props([
'initialSelectedValue' => null, // Initial selected value (e.g., a string or number)
'options' => [], // Array of option objects (e.g., [{value: '1', label: 'One'}, ...])
'id' => '', // Unique ID for the component instance
'withFlag' => false, // Boolean to indicate if flags should be shown
'icon' => null, // String name of the icon to display
'placeholder' => '', // Placeholder text
'storePath' => null, // Путь к свойству в Alpine store для автоматической синхронизации (например, 'creatives.perPage')
])

<div x-data="baseSelect({
        initialSelectedValue: {{ $initialSelectedValue !== null ? json_encode($initialSelectedValue) : 'null' }},
        optionsArray: {{ json_encode($options) }},
        elementId: '{{ $id }}',
        useFlags: {{ $withFlag ? 'true' : 'false' }},
        iconClass: {{ $icon ? "'".$icon."'" : 'null' }},
        placeholderText: '{{ $placeholder }}',
        storePath: {{ $storePath ? "'".$storePath."'" : 'null' }}
    })" x-init="init()" class="base-select" :class="{ 'base-select-icon': iconClass, 'active': open }" :id="elementId"
    @click.away="if(open) open = false">
    <div class="base-select__trigger" @click="toggleDropdown()">
        <span class="base-select__value">
            {{-- Display flag if enabled and selected option has a country code --}}
            <template x-if="useFlags && selectedOption.value && selectedOption.code">
                <img :src="'/img/flags/' + selectedOption.code + '.svg'" alt="">
            </template>

            {{-- Display placeholder if no option is selected and placeholder text is provided --}}
            <span class="base-select__placeholder" x-show="!selectedOption.value && placeholderText"
                x-text="placeholderText">
            </span>

            {{-- Display selected option's label if an option is selected --}}
            <span class="base-select__selected-label" x-show="selectedOption.value" x-text="selectedOption.label">
            </span>
        </span>
        <span class="base-select__arrow"></span>
    </div>

    <ul class="base-select__dropdown" x-show="open" style="display: none;" x-transition.opacity.duration.200ms>
        <template x-for="option in optionsArray" :key="option.value + (option.order || '')">
            <li @click="selectOption(option)" :data-value="option.value" :data-order="option.order"
                :data-label="option.label" class="base-select__option" :class="{
                    'is-selected': String(option.value) === String(selectedOption.value) &&
                                   (option.order === undefined || String(option.order) === String(selectedOption.order))
                }">
                {{-- Display flag in dropdown if enabled and option has a country code --}}
                <template x-if="useFlags && option.code">
                    <img :src="'/img/flags/' + option.code + '.svg'" alt="">
                </template>
                <span x-text="option.label"></span>
            </li>
        </template>
    </ul>

    {{-- Display icon if provided --}}
    <template x-if="iconClass">
        <span :class="'icon-' + iconClass"></span>
    </template>
</div>