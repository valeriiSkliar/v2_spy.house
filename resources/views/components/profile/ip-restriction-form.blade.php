@props(['user', 'ip_restrictions'])

<form id="ip-restriction-form" action="{{ route('api.profile.update-ip-restriction') }}" method="POST" class="pt-3">
    @csrf
        <div class="row-col _offset20 mb-30">

    <div class="col-lg-4 col-md-6 col-12 pl-0 mb-20">
        <label class="d-block mb-10">{{ __('profile.ip_restriction.allowed_ip_addresses_label') }}</label>
        <textarea name="ip_restrictions" class="auto-resize" rows="5"
            placeholder="{{ __('profile.ip_restriction.allowed_ip_addresses_placeholder') }}">{{ is_array($ip_restrictions) ? implode("\n", $ip_restrictions) : $ip_restrictions }}</textarea>
        @error('ip_restrictions')
        <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>
    <div class="col-lg-4 col-md-6 col-12 pl-0 mb-20">
        <label class="d-block mb-10">{{ __('profile.password_label') }}</label>
        <input type="password" name="password" class="input-h-57" autocomplete="current-password">
        @error('password')
        <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>
    </div>

    <x-profile.submit-button formId="ip-restriction-form" id="ip-restriction-submit-button"
        :label="__('profile.save_button')" />
</form>