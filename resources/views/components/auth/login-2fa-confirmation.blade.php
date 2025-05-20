@props([ 'error', 'message'])
<div class="form-item mb-25">
    <label class="d-block mb-15">{{ __('Code from the') }} <span class="font-weight-500">{{
            __('Authenticator') }}</span> {{ __('app') }}</label>
    <div class="form-code-authenticator">
        <img src="{{ asset('img/google-authenticator.svg') }}" alt="" width="50">
        <input type="text" name="code" class="input-h-57" placeholder="xxx xxx" required>
        {{-- @error($error)
        <span class="invalid-feedback" role="alert">
            <strong>{{ $message }}</strong>
        </span>
        @enderror --}}
    </div>
</div>