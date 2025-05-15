@props(['name', 'type', 'label' => '', 'value', 'disabled' => false, 'autocomplete' => 'off', 'dataPass' => 'pass-' . uniqid()])

<div class="form-item mb-20">
    <div class="row justify-content-between align-items-center mb-10">
        <div class="col-auto"><label class="d-block">{{ $label }}</label></div>
        {{-- <div class="col-auto"><a href="{{ route('profile.change-password') }}" class="link">{{ __('profile.personal_info.change_password_link') }}</a></div> --}}
    </div>
    <div class="form-password">
        <input 
            type="password" 
            class="input-h-57" 
            data-pass="{{ $dataPass }}" 
            name="{{ $name }}" 
            autocomplete="{{ $autocomplete }}"
            @if (isset($value))
                value="{{ $value }}"
            @endif
            {{ $disabled ? 'disabled' : '' }}
        >
        <button type="button" class="btn-icon switch-password" data-pass-switch="{{ $dataPass }}">
            <span class="icon-view-off"></span>
            <span class="icon-view-on"></span>
        </button>
    </div>
</div>