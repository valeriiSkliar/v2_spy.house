<div class="form-item mb-20">
    <label class="d-block mb-10">{{ $label }}</label>
    <input type="{{ $type }}" name="{{ $name }}" class="input-h-57 " value="{{ old($name, $value ?? '') }}">
    @error($name)
        <span class="text-danger">{{ $message }}</span>
    @enderror
</div>