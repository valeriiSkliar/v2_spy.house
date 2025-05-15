@props(['name', 'type', 'label', 'value', 'disabled' => false, 'autocomplete' => 'off'])

<div class="form-item mb-20">
    <label class="d-block mb-10">{{ $label }}</label>
    <input 
        autocomplete="{{ $autocomplete }}" 
        {{ $disabled ? 'disabled' : '' }} 
        type="{{ $type }}" 
        name="{{ $name }}" 
        class="input-h-57 input-h-57-lg" 
        value="{{ old($name, $value ?? '') }}"
    >
    @error($name)
        <span class="text-danger">{{ $message }}</span>
    @enderror
</div>