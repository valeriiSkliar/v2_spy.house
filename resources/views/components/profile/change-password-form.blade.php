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
            <x-profile.form-field name="password" type="password" label="New Password" />
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.form-field name="password_confirmation" type="password" label="New password again" />
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.select-field 
                name="confirmation_method" 
                label="Confirmation method" 
                value="authenticator" 
                :options="['Authenticator app', 'SMS code']" 
                data-confirmation="true"
            />
        </div>
    </div>
    <x-profile.submit-button label="Next" />
    <div class="row _offset20 mb-20 pt-4">
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.authenticator-code />
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.info-message />
        </div>
    </div>
    <x-profile.submit-button label="Confirm" />
</form>