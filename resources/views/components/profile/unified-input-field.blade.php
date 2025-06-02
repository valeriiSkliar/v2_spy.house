@props([
    'name', 
    'type' => 'text', 
    'label', 
    'value' => '', 
    'disabled' => false, 
    'autocomplete' => 'off',
    'popover' => false,
    'popoverText' => '', 
    'tabindex' => null, 
    'autofocus' => false,
    'showPasswordToggle' => false,
    'changePasswordLink' => false,
    'placeholder' => ''
])

@php
    $isPassword = $type === 'password' || $showPasswordToggle;
    $dataPass = $isPassword ? 'pass-' . uniqid() : null;
@endphp

<div class="form-item mb-20">
    @if($changePasswordLink)
        <div class="row justify-content-between align-items-center mb-10">
            <div class="col-auto">
                <label class="d-block">{{ $label }}
                    @if($popover)
                        <span data-bs-toggle="popover" data-bs-placement="top" data-bs-content="{{ $popoverText }}"
                            class="popover-icon icon-i ml-2"></span>
                    @endif
                </label>
            </div>
            <div class="col-auto">
                <a href="{{ route('profile.change-password') }}" class="link">
                    {{ __('profile.personal_info.change_password_link') }}
                </a>
            </div>
        </div>
    @else
        <label class="d-block mb-10">{{ $label }}
            @if($popover)
                <span data-bs-toggle="popover" data-bs-placement="top" data-bs-content="{{ $popoverText }}"
                    class="popover-icon icon-i ml-2"></span>
            @endif
        </label>
    @endif

    @if($isPassword && $showPasswordToggle)
        <div class="form-password">
            <input 
                type="password" 
                class="input-h-57 input-h-57-lg" 
                data-pass="{{ $dataPass }}" 
                name="{{ $name }}"
                autocomplete="{{ $autocomplete }}" 
                value="{{ old($name, $value) }}"
                placeholder="{{ $placeholder }}"
                {{ $disabled ? 'disabled' : '' }}
                @if(!$disabled) readonly onfocus="this.removeAttribute('readonly');" @endif
                @if($tabindex) tabindex="{{ $tabindex }}" @endif
                @if($autofocus) autofocus @endif
            >
            <button type="button" class="btn-icon switch-password" data-pass-switch="{{ $dataPass }}">
                <span class="icon-view-off"></span>
                <span class="icon-view-on"></span>
            </button>
        </div>
    @else
        <input 
            autocomplete="{{ $autocomplete }}" 
            {{ $disabled ? 'disabled' : '' }} 
            type="{{ $type }}" 
            name="{{ $name }}"
            class="input-h-57 input-h-57-lg" 
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            @if(!$disabled && ($type === 'email' || $type === 'password')) readonly onfocus="this.removeAttribute('readonly');" @endif
            @if($tabindex) tabindex="{{ $tabindex }}" @endif
            @if($autofocus) autofocus @endif
        >
    @endif

    @error($name)
        <span class="text-danger">{{ $message }}</span>
    @enderror
</div>