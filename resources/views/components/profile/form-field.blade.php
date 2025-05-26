@props(['name', 'type', 'label', 'value', 'disabled' => false, 'autocomplete' => 'off' , 'popover' => false,
'popoverText' => '', 'tabindex' => null, 'autofocus' => false])

<div class="form-item mb-20">
    <label class="d-block mb-10">{{ $label }}
        @if($popover)
        <span data-bs-toggle="popover" data-bs-placement="top" data-bs-content="{{ $popoverText }}"
            class="popover-icon icon-i ml-2"></span>
        @endif</label>
    <input autocomplete="{{ $autocomplete }}" {{ $disabled ? 'disabled' : '' }} type="{{ $type }}" name="{{ $name }}"
        class="input-h-57 input-h-57-lg" value="{{ old($name, $value ?? '') }}" @if(!$disabled && ($type=='email' ||
        $type=='password' )) readonly onfocus="this.removeAttribute('readonly');" @endif @if ($tabindex)
        tabindex="{{ $tabindex }}" @endif @if ($autofocus) autofocus @endif>
    @error($name)
    <span class="text-danger">{{ $message }}</span>
    @enderror
</div>