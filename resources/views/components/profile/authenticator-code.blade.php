<div class="form-item mb-20">
    <label class="d-block mb-15">{{ __('profile.authenticator_code.code_from_the') }} <span class="font-weight-500">{{
            __('profile.authenticator_code.Authenticator') }}</span> {{ __('profile.authenticator_code.app') }}</label>

    <div class="form-code-authenticator">
        <img src="/img/google-authenticator.svg" alt="">
        <input type="text" name="verification_code" class="input-h-57" placeholder="xxx  xxx">
    </div>
    @error('verification_code')
    <span class="text-danger">{{ $message }}</span>
    @enderror
</div>