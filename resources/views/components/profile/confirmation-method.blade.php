@props([
'icon' => '',
'width' => '42',
'height' => '42',
'title' => '',
'description' => '',
'isEnabled' => false,
'routeEnable' => '',
'routeDisable' => '',
'buttonTextEnableKey' => 'profile.security_settings.connect_button',
'buttonTextDisableKey' => 'profile.security_settings.disconnect_button',
'buttonClassEnable' => 'btn _flex _border-green _medium',
'buttonClassDisable' => 'btn _flex _red _medium',
])

<div class="col-12 col-md-6 d-flex">
    <div class="confirmation-method">
        <figure class="confirmation-method__icon"><img width="{{ $width }}" height="{{ $height }}" src="{{ $icon }}"
                alt="{{ $title }}"></figure>
        <div class="confirmation-method__title">{{ $title }}</div>
        <div class="confirmation-method__desc">{{ $description }}</div>
        <div class="confirmation-method__btn">
            @if ($isEnabled)
            <a href="{{ $routeDisable }}" class="{{ $buttonClassDisable }}">{{ __($buttonTextDisableKey) }}</a>
            @else
            <a href="{{ $routeEnable }}" class="{{ $buttonClassEnable }}">{{ __($buttonTextEnableKey) }}</a>
            @endif
        </div>
    </div>
</div>