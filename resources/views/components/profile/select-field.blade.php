<div class="form-item mb-20">
    <label class="d-block mb-10">{{ $label }}</label>
    <div class="base-select base-select_big">
        <div class="base-select__trigger">
            <span class="base-select__value">
                @php
                    $currentValue = is_null($value) ? old($name) : $value;
                    if ($currentValue instanceof \UnitEnum) {
                        $currentValue = $currentValue->value;
                    }
                    $currentValue = is_null($currentValue) ? '' : (string)$currentValue;
                    $displayValue = array_key_exists($currentValue, $options ?? []) ? $options[$currentValue] : $displayDefaultValue;
                @endphp
                {{ $displayValue }}
            </span>
            <span class="base-select__arrow"></span>
        </div>
        <ul class="base-select__dropdown" style="display: none;">
            @foreach($options ?? [] as $key => $option)
                @php
                    $optionKey = $key instanceof \UnitEnum ? $key->value : $key;
                @endphp
                <li class="base-select__option" data-value="{{ $optionKey }}" {{ (string)$optionKey === $currentValue ? 'is-selected' : '' }}>{{ $option }}</li>
            @endforeach
        </ul>
        <input type="hidden" name="{{ $name }}" value="{{ $currentValue }}">
    </div>
    @error($name)
        <span class="text-danger">{{ $message }}</span>
    @enderror
</div>