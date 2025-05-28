@props([
'service',
'variant' => 'default',
'route' => 'services.redirect',
'target' => '_blank',
'buttonText' => __('services.buttons.follow'),
'icon' => 'icon-next'
])
@switch($variant)
@case('link')
<div class="single-market__link">
    <a aria-label="{{ __('services.buttons.follow') }}" href="{{ route('services.redirect', $service['id']) }}"
        class="site-link" target="_blank"><span class="icon-link"></span>{{ $service['url'] }}</a>
</div>
@break

@default
<a href="{{ route($route, $service['id']) }}" target="{{ $target }}" class="btn w-100 _flex _green">
    <span class="btn__text">{{ $buttonText }}</span>
    <span class="icon-next ml-3 font-18"></span>
</a>
@endswitch