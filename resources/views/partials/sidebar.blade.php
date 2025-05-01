<aside class="aside">
    <div class="aside__head">
        <div class="aside__lang">
            <x-frontend.language-selector />
        </div>
        <div class="aside__tariff">
            @if(auth()->check() && auth()->user()->hasTariff())
            <x-tariff-link :type="auth()->user()->currentTariff()['css_class']">
                {{ auth()->user()->currentTariff()['name'] }}
            </x-tariff-link>
            @else
            <x-tariff-link>Free</x-tariff-link>
            @endif
        </div>
    </div>
    <div class="aside__content">
        @include('partials.sidebar-menu')
        @include('components.promo-tariff')
        <div class="aside__copyright">© 2012 - 2025 Spy.House</div>
    </div>
    <div class="sep _h20"></div>
    <a href="#" target="_blank" class="banner-item"><img src="/img/52400c8bd4719323579fd1a074fff985.gif" alt="" class="d-block w-100 rounded-10"></a>
</aside>