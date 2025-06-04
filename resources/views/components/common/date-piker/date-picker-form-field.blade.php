    <div class="form-item mb-20">
        <label class="d-block mb-10">{{ $label }}</label>
        <input 
            type="{{ $type }}" 
            name="{{ $name }}" 
            class="input-h-57 datepicker" 
            value="{{ old($name, $value ?? '') }}" 
            autocomplete="off"
        >
        <div class="datepicker-calendar _icon-calendar"></div>
        @error($name)
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>
