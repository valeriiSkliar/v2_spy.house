@extends('layouts.main')

@section('page-content')
<h1 class="mb-25">Change password</h1>
<div class="section">
    <form action="{{ route('profile.update-password') }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row _offset20 mb-10">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="form-item mb-20">
                    <label class="d-block mb-10">Current Password</label>
                    <div class="form-password">
                        <input type="password" name="current_password" class="input-h-57" data-pass="pass-1" value="">
                        <button type="button" class="btn-icon switch-password" data-pass-switch="pass-1">
                            <span class="icon-view-off"></span>
                            <span class="icon-view-on"></span>
                        </button>
                    </div>
                    @error('current_password')
                    <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="form-item mb-20">
                    <label class="d-block mb-10">New Password</label>
                    <input type="password" name="password" class="input-h-57">
                    @error('password')
                    <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="form-item mb-20">
                    <label class="d-block mb-10">New password again</label>
                    <input type="password" name="password_confirmation" class="input-h-57">
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="form-item mb-20">
                    <label class="d-block mb-10">Confirmation method</label>
                    <div class="base-select base-select_big">
                        <div class="base-select__trigger">
                            <span class="base-select__value">Authenticator app</span>
                            <span class="base-select__arrow"></span>
                        </div>
                        <ul class="base-select__dropdown" style="display: none;">
                            <li class="base-select__option is-selected">Authenticator app</li>
                            <li class="base-select__option">SMS code</li>
                        </ul>
                        <input type="hidden" name="confirmation_method" value="authenticator">
                    </div>
                    @error('confirmation_method')
                    <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>
        <div class="mb-20">
            <button type="submit" class="btn _flex _green _big min-200 w-mob-100">Next</button>
        </div>
        <div class="row _offset20 mb-20 pt-4">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="form-item mb-20">
                    <label class="d-block mb-15">Code from the <span class="font-weight-500">Authenticator</span> app</label>
                    <div class="form-code-authenticator">
                        <img src="/img/google-authenticator.svg" alt="">
                        <input type="text" name="code" class="input-h-57" placeholder="xxx  xxx">
                    </div>
                    @error('code')
                    <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="message mb-20 pt-md-4 mt-md-3">
                    <span class="icon-i"></span>
                    <div class="message__txt"><span class="font-weight-500">Authenticator</span> is a 6-digit one-time password that the user must enter into the field to log in.</div>
                </div>
            </div>
        </div>
        <div class="mb-20">
            <button type="submit" class="btn _flex _green _big min-200 w-mob-100">Confirm</button>
        </div>
    </form>
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle base select change for confirmation method
        const baseSelectOptions = document.querySelectorAll('.base-select_big .base-select__option');
        const confirmationMethodInput = document.querySelector('input[name="confirmation_method"]');
        const baseSelectValue = document.querySelector('.base-select_big .base-select__value');

        baseSelectOptions.forEach(option => {
            option.addEventListener('click', function() {
                const value = this.textContent.trim() === 'Authenticator app' ? 'authenticator' : 'sms';
                confirmationMethodInput.value = value;
                baseSelectValue.textContent = this.textContent.trim();

                // Update selected class
                baseSelectOptions.forEach(opt => opt.classList.remove('is-selected'));
                this.classList.add('is-selected');

                // Show/hide the authenticator code field
                const authenticatorSection = document.querySelector('.form-code-authenticator').closest('.row');
                if (value === 'authenticator') {
                    authenticatorSection.style.display = 'flex';
                } else {
                    authenticatorSection.style.display = 'none';
                }
            });
        });
    });
</script>
@endsection