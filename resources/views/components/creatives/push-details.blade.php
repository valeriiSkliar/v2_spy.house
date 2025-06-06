<div class="creatives-list__details">
    <div class="creative-details">
        <div class="creative-details__content">
            @include('components.creatives.creative-details-head')

            <div class="creative-details__group _first">
                <div class="row _offset20 align-items-center">
                    <div class="col-5">
                        <div class="thumb thumb-icon">
                            <img src="{{ $iconImage ?? '/img/th-2.jpg' }}" alt="">
                        </div>
                    </div>
                    <div class="col-6">
                        <p class="font-16 mb-15"><span class="font-weight-600">{{ __('creatives.details.icon') }}</span>
                            {{ $iconSize ?? '7.2 KB' }}
                        </p>
                        <div class="mb-10">
                            <a href="#" class="btn _flex _medium _green w-100"><span
                                    class="icon-download2  font-16 mr-2"></span>{{
                                __('creatives.details.download') }}</a>
                        </div>
                        <div class="mb-0">
                            <a href="#" class="btn _flex _medium _gray w-100"><span
                                    class="icon-new-tab remore_margin font-16 mr-2"></span>{{
                                __('creatives.details.open-in-tab') }}</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="creative-details__group">
                <p class="font-16 mb-15"><span class="font-weight-600">{{ __('creatives.details.image') }}</span> {{
                    $imageSize ?? '7.2 KB' }}</p>
                <div class="thumb thumb-image mb-15">
                    <img src="{{ $mainImage ?? '/img/th-3.jpg' }}" alt="">
                </div>
                <div class="row _offset20">
                    <div class="col-6">
                        <a href="#" class="btn _flex _medium _green w-100"><span
                                class="icon-download2  font-16 mr-2"></span>{{
                            __('creatives.details.download') }}</a>
                    </div>
                    <div class="col-6">
                        <a href="#" class="btn _flex _medium _gray w-100"><span
                                class="icon-new-tab remore_margin font-16 mr-2"></span>{{
                            __('creatives.details.open-in-tab') }}</a>
                    </div>
                </div>
            </div>

            <div class="creative-details__group">
                <p class="mb-15 font-16 font-weight-600">{{ __('creatives.details.text') }}</p>
                @include('components.creatives.text-with-copy', [
                'label' => __('creatives.details.creative-title'),
                'text' => $title ?? '⚡What are the pensions the increase? 💰',
                'fontWeight' => 'font-weight-500'
                ])
                @include('components.creatives.text-with-copy', [
                'label' => __('creatives.details.description'),
                'text' => $description ?? 'How much did Kazakhstanis begin to receive'
                ])
                <div class="pt-2">
                    <button class="btn _flex _gray _medium"><span class="icon-translate font-18 mr-2"></span>{{
                        __('creatives.details.translate') }}</button>
                </div>
            </div>

            @include('components.creatives.similar-creatives', [
            'creativeComponent' => view('components.creatives.creative-item-push', [
            'isActive' => true,
            'activeText' => trans_choice('creatives.card.active', $activeDays ?? 3, ['count' => $activeDays ?? 3]),
            'icon' => '/img/th-2.jpg',
            'image' => '/img/th-3.jpg',
            'deviceType' => 'phone',
            'deviceText' => 'Mob'
            ])->render()
            ])
        </div>
    </div>
</div>