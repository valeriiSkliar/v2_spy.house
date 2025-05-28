<div class="form-item mb-20">
    <label class="d-block mb-15">{{ __('profile.code from the') }} <span class="font-weight-500">{{
            __('profile.authenticator_mode.email') }}</span> {{ __('profile.letter') }}</label>
    <div class="form-code-authenticator">
        <img src="/img/email-code.svg" alt="">
        <input type="text" name="verification_code" class="input-h-57" placeholder="xxx  xxx">
    </div>
    @error('verification_code')
    <span class="text-danger">{{ $message }}</span>
    @enderror
</div>