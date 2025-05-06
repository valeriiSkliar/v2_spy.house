<div class="form-item mb-20">
    <label class="d-block mb-10">{{ $label }}</label>
    <div class="base-select base-select_big">
        <div class="base-select__trigger">
            <span class="base-select__value">{{ old($name, $value ?? 'Arbitrage (solo)') }}</span>
            <span class="base-select__arrow"></span>
        </div>
        <ul class="base-select__dropdown" style="display: none;">
            @foreach($options as $option)
                <li class="base-select__option {{ $option === (old($name, $value ?? 'Arbitrage (solo)')) ? 'is-selected' : '' }}">{{ $option }}</li>
            @endforeach
        </ul>
        <input type="hidden" name="{{ $name }}" value="{{ old($name, $value ?? 'Arbitrage (solo)') }}">
    </div>
    @error($name)
        <span class="text-danger">{{ $message }}</span>
    @enderror
</div>