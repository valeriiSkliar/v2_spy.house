<div class="col-12 col-md-6 d-flex">
    <div class="confirmation-method">
        <figure class="confirmation-method__icon"><img width="{{ $width }}" height="{{ $height }}" src="{{ $icon }}" alt=""></figure>
        <div class="confirmation-method__title">{{ $title }}</div>
        <div class="confirmation-method__desc">{{ $description }}</div>
        <div class="confirmation-method__btn">
            <a href="{{ $route }}" class="btn _flex _border-green _medium">{{ __('profile.security_settings.connect_button') }}</a>
        </div>
    </div>
</div>