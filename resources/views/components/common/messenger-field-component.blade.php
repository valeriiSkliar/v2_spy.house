@props([
'name' => 'messenger_contact',
'messengerTypeName' => 'messenger_type',
'label' => null,
'value' => '',
'messengerType' => 'telegram',
'showLabel' => true,
'containerClass' => 'form-item mb-3',
'inputClass' => 'input-h-57',
'selectId' => null,
'showErrors' => true
])

@php
$messenger_type = old($messengerTypeName, $messengerType);
$messenger_contact = old($name, $value);

$placeholders = [
'telegram' => '@username',
'viber' => '+1 (999) 999-99-99',
'whatsapp' => '+1 (999) 999-99-99'
];

$currentPlaceholder = $placeholders[$messenger_type] ?? $placeholders['telegram'];

$images = [
'telegram' => Vite::asset('resources/img/telegram.svg'),
'viber' => Vite::asset('resources/img/viber.svg'),
'whatsapp' => Vite::asset('resources/img/whatsapp.svg')
];

$initImage = $images[$messenger_type] ?? $images['telegram'];

// Определяем ID селекта
$selectElementId = $selectId ?? ($name . '-select');
@endphp

<div class="{{ $containerClass }}">
    @if($showLabel && $label)
    <label class="d-block mb-10">{{ $label }}</label>
    @endif

    <div class="form-phone">
        <input readonly onfocus="this.removeAttribute('readonly');" type="text" name="{{ $name }}"
            class="{{ $inputClass }} @error($name) error @enderror" value="{{ $messenger_contact }}"
            placeholder="{{ $currentPlaceholder }}">

        <input type="hidden" name="{{ $messengerTypeName }}" value="{{ $messenger_type }}">

        <div id="{{ $selectElementId }}" class="base-select" data-target="{{ $messengerTypeName }}">
            <div class="base-select__trigger">
                <span class="base-select__value">
                    <span class="base-select__img">
                        <img src="{{ $initImage }}" alt="{{ ucfirst($messenger_type) }}">
                    </span>
                </span>
                <span class="base-select__arrow"></span>
            </div>
            <ul class="base-select__dropdown" style="display: none;">
                <li class="base-select__option {{ $messenger_type == 'telegram' ? 'is-selected' : '' }}"
                    data-value="telegram">
                    <span class="base-select__img">
                        <img src="{{ $images['telegram'] }}" alt="Telegram">
                    </span>
                </li>
                <li class="base-select__option {{ $messenger_type == 'viber' ? 'is-selected' : '' }}"
                    data-value="viber">
                    <span class="base-select__img">
                        <img src="{{ $images['viber'] }}" alt="Viber">
                    </span>
                </li>
                <li class="base-select__option {{ $messenger_type == 'whatsapp' ? 'is-selected' : '' }}"
                    data-value="whatsapp">
                    <span class="base-select__img">
                        <img src="{{ $images['whatsapp'] }}" alt="WhatsApp">
                    </span>
                </li>
            </ul>
        </div>
    </div>

    @if($showErrors)
    @error($messengerTypeName)
    <span class="text-danger">{{ $message }}</span>
    @enderror
    @error($name)
    <span class="text-danger">{{ $message }}</span>
    @enderror
    @endif
</div>