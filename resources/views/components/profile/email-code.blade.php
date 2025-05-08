<div class="form-item mb-20">
    <label class="d-block mb-15">Code from the <span class="font-weight-500">E-mail</span></label>
    <div class="form-code-authenticator">
        <img src="/img/email-code.svg" alt="">
        <input type="text" name="code" class="input-h-57" placeholder="xxx  xxx">
    </div>
    @error('code')
        <span class="text-danger">{{ $message }}</span>
    @enderror
</div>