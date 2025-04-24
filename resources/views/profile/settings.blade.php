@extends('layouts.authorized')

@section('page-content')
<h1 class="mb-25">Profile Settings</h1>
<div class="section profile-settings">
    <ul class="tubs _mob100">
        <li class="flex-grow-1"><button data-tub="personal" data-group="profile" class="active"><span class="icon-personal"></span> Personal information</button></li>
        <li class="flex-grow-1"><button data-tub="security" data-group="profile" class=""><span class="icon-security"></span> Security</button></li>
        <li class="flex-grow-1"><button data-tub="notifications" data-group="profile" class=""><span class="icon-email"></span> Notifications</button></li>
    </ul>
    <div class="tubs-content">
        <div class="tubs-content__item active" data-tub="personal" data-group="profile">
            <form action="{{ route('profile.update-settings') }}" method="POST" enctype="multipart/form-data" class="pt-3">
                @csrf
                @method('PUT')

                <div class="user-info">
                    <div class="user-info__photo thumb">
                        @if($user->photo)
                        <img src="{{ asset('storage/avatars/' . $user->photo) }}" alt="{{ $user->name }}">
                        @else
                        {{ substr($user->name, 0, 2) }}
                        @endif
                    </div>
                    <div class="user-info__wrap">
                        <div class="user-info__name">{{ $user->name }}</div>
                        <div class="user-info__load-photo">
                            <input id="photo" name="photo" type="file" class="d-none">
                            <label for="photo" class="btn _flex _gray2 _medium"><span class="icon-blog mr-2 font-16"></span>Change photo</label>
                            <span>PNG (600x600) 200кб</span>
                        </div>
                    </div>
                </div>

                <div class="row _offset20 mb-20">
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="form-item mb-20">
                            <label class="d-block mb-10">Login</label>
                            <input type="text" name="login" class="input-h-57" value="{{ old('login', $user->login ?? '') }}">
                            @error('login')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="form-item mb-20">
                            <label class="d-block mb-10">E-mail</label>
                            <input type="email" name="email" class="input-h-57" value="{{ old('email', $user->email) }}">
                            @error('email')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="form-item mb-20">
                            <div class="row justify-content-between align-items-center mb-10">
                                <div class="col-auto"><label class="d-block">Password</label></div>
                                <div class="col-auto"><a href="{{ route('profile.change-password') }}" class="link">Change password</a></div>
                            </div>
                            <div class="form-password">
                                <input type="password" class="input-h-57" data-pass="pass-1" value="••••••••" disabled>
                                <button type="button" class="btn-icon switch-password" data-pass-switch="pass-1">
                                    <span class="icon-view-off"></span>
                                    <span class="icon-view-on"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="form-item mb-20">
                            <label class="d-block mb-10">Phone</label>
                            <div class="form-phone">
                                <input type="text" name="phone" class="input-h-57" value="{{ old('phone', $user->phone ?? '') }}">
                                <div class="base-select">
                                    <div class="base-select__trigger">
                                        <span class="base-select__value"><span class="base-select__img"><img src="/img/flags/UA.svg" alt="">UA</span></span>
                                        <span class="base-select__arrow"></span>
                                    </div>
                                    <ul class="base-select__dropdown" style="display: none;">
                                        <li class="base-select__option is-selected"><span class="base-select__img"><img src="/img/flags/UA.svg" alt="">UA</span></li>
                                        <li class="base-select__option"><span class="base-select__img"><img src="/img/flags/KZ.svg" alt="">KZ</span></li>
                                        <li class="base-select__option"><span class="base-select__img"><img src="/img/flags/ES.svg" alt="">ES</span></li>
                                    </ul>
                                </div>
                            </div>
                            @error('phone')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="form-item mb-20">
                            <label class="d-block mb-10">Telegram</label>
                            <input type="text" name="telegram" class="input-h-57" value="{{ old('telegram', $user->telegram ?? '') }}">
                            @error('telegram')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="form-item mb-20">
                            <label class="d-block mb-10">Scope of activity</label>
                            <div class="base-select base-select_big">
                                <div class="base-select__trigger">
                                    <span class="base-select__value">{{ old('scope', $user->scope ?? 'Arbitrage (solo)') }}</span>
                                    <span class="base-select__arrow"></span>
                                </div>
                                <ul class="base-select__dropdown" style="display: none;">
                                    @foreach($scopes as $scope)
                                    <li class="base-select__option {{ $scope === (old('scope', $user->scope ?? 'Arbitrage (solo)')) ? 'is-selected' : '' }}">{{ $scope }}</li>
                                    @endforeach
                                </ul>
                                <input type="hidden" name="scope" value="{{ old('scope', $user->scope ?? 'Arbitrage (solo)') }}">
                            </div>
                            @error('scope')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                @if(session('status') === 'profile-updated')
                <div class="message _bg _with-border _green mb-15">
                    <span class="icon-check font-18"></span>
                    <div class="message__txt">Your profile information has been updated.</div>
                </div>
                @endif

                <div class="mb-20">
                    <button type="submit" class="btn _flex _green _big min-200 w-mob-100">Save</button>
                </div>
            </form>
        </div>

        <div class="tubs-content__item" data-tub="security" data-group="profile">
            <div class="pt-3">
                <h2 class="mb-30">Account access settings</h2>
                <div class="row _offset30">
                    <div class="col-12 col-md-6 d-flex">
                        <a href="{{ route('profile.change-password') }}" class="access-link">
                            <span class="access-link__icon"><span class="icon-security"></span></span>
                            <span class="access-link__title">Change password</span>
                            <span class="access-link__desc">Login to the system is done using login and password</span>
                            <span class="access-link__arrow"><span class="icon-next-long"></span></span>
                        </a>
                    </div>
                    <div class="col-12 col-md-6 d-flex">
                        <a href="{{ route('profile.change-email') }}" class="access-link">
                            <span class="access-link__icon"><span class="icon-email"></span></span>
                            <span class="access-link__title">Change E-mail</span>
                            <span class="access-link__desc">Login to the system is done using login and password</span>
                            <span class="access-link__arrow"><span class="icon-next-long"></span></span>
                        </a>
                    </div>
                    <div class="col-12 col-md-6 d-flex">
                        <a href="{{ route('profile.personal-greeting') }}" class="access-link">
                            <span class="access-link__icon"><span class="icon-personal"></span></span>
                            <span class="access-link__title">Personal greeting</span>
                            <span class="access-link__desc">Set a greeting to protect against phishing.</span>
                            <span class="access-link__arrow"><span class="icon-next-long"></span></span>
                        </a>
                    </div>
                    <div class="col-12 col-md-6 d-flex">
                        <a href="{{ route('profile.ip-restriction') }}" class="access-link">
                            <span class="access-link__icon"><span class="icon-ip"></span></span>
                            <span class="access-link__title">IP Restriction</span>
                            <span class="access-link__desc">Restrict access by IP address or range of IP addresses.</span>
                            <span class="access-link__arrow"><span class="icon-next-long"></span></span>
                        </a>
                    </div>
                </div>

                <h2 class="mb-30">Confirmation methods</h2>
                <div class="row _offset30">
                    <div class="col-12 col-md-6 d-flex">
                        <div class="confirmation-method">
                            <figure class="confirmation-method__icon"><img width="42" height="42" src="/img/google-authenticator.svg" alt=""></figure>
                            <div class="confirmation-method__title">Google 2FA</div>
                            <div class="confirmation-method__desc">Google two-factor authentication for signing in and confirming payments.</div>
                            <div class="confirmation-method__btn">
                                <a href="{{ route('profile.connect-2fa') }}" class="btn _flex _border-green _medium">Connect</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 d-flex">
                        <div class="confirmation-method">
                            <figure class="confirmation-method__icon"><img width="42" height="42" src="/img/pin-code.svg" alt=""></figure>
                            <div class="confirmation-method__title">PIN code</div>
                            <div class="confirmation-method__desc">Create your PIN code and use it to confirm transactions or other operations</div>
                            <div class="confirmation-method__btn">
                                <a href="{{ route('profile.connect-pin') }}" class="btn _flex _border-green _medium">Connect</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tubs-content__item" data-tub="notifications" data-group="profile">
            <div class="pt-3">
                <div class="row _offset30">
                    <div class="col-12 d-flex">
                        <div class="confirmation-method">
                            <figure class="confirmation-method__icon"><img width="29" height="23" src="/img/notification.svg" alt=""></figure>
                            <div class="row justify-content-between align-items-center">
                                <div class="col-12 col-lg-auto">
                                    <div class="confirmation-method__title">Email notifications</div>
                                    <div class="confirmation-method__desc">The e-mail specified in your profile will be used for notifications. (<strong>{{ $user->email }}</strong>)</div>
                                </div>
                                <div class="col-12 col-lg-auto">
                                    <div class="confirmation-method__btns">
                                        <form action="{{ route('profile.update-notifications') }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="row _offset20 mt-3">
                                                <div class="col-12 col-md-auto mb-10">
                                                    <label class="checkbox-btn">
                                                        <input type="checkbox" name="notifications[]" value="system" {{ in_array('system', old('notifications', $user->notifications ?? ['system'])) ? 'checked' : '' }}>
                                                        <span class="checkbox-btn__content">
                                                            <span class="checkbox-btn__icon icon-check-circle"></span>
                                                            <span class="checkbox-btn__text">System messages</span>
                                                        </span>
                                                    </label>
                                                </div>
                                                <div class="col-12 col-md-auto mb-10">
                                                    <label class="checkbox-btn">
                                                        <input type="checkbox" name="notifications[]" value="bonus" {{ in_array('bonus', old('notifications', $user->notifications ?? [])) ? 'checked' : '' }}>
                                                        <span class="checkbox-btn__content">
                                                            <span class="checkbox-btn__icon icon-check-circle"></span>
                                                            <span class="checkbox-btn__text">Bonus offers</span>
                                                        </span>
                                                    </label>
                                                </div>
                                                <div class="col-12 col-md-auto mb-10">
                                                    <button type="submit" class="btn _flex _green _medium">Save</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle base select change for scope
        const baseSelectOptions = document.querySelectorAll('.base-select_big .base-select__option');
        const scopeInput = document.querySelector('input[name="scope"]');
        const baseSelectValue = document.querySelector('.base-select_big .base-select__value');

        baseSelectOptions.forEach(option => {
            option.addEventListener('click', function() {
                const value = this.textContent.trim();
                scopeInput.value = value;
                baseSelectValue.textContent = value;

                // Update selected class
                baseSelectOptions.forEach(opt => opt.classList.remove('is-selected'));
                this.classList.add('is-selected');
            });
        });
    });
</script>
@endsection