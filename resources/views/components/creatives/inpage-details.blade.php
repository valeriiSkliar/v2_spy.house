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
                                    class="icon-download2 remore_margin font-16 mr-2"></span>{{
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
                    <button class="btn _flex _gray _medium"><span class="icon-translate font-18 mr-2"></span>
                        {{ __('creatives.details.translate') }}</button>
                </div>
            </div>

            <div class="creative-details__group">
                <h3 class="mb-20">{{ __('creatives.details.redirects-details') }}</h3>
                <div class="form-link mb-25">
                    <input type="url" value="{{ $redirectLink ?? 'track.luxeprofit.pro' }}" readonly>
                    <a href="#" target="_blank" class="btn-icon _small _white"><span class="icon-new-tab"></span></a>
                </div>
                @include('components.creatives.creative-details-table', [
                'network' => $network ?? 'Pushhouse',
                'country' => $country ?? 'Bangladesh',
                'language' => $language ?? 'English',
                'firstDate' => $firstDate ?? 'Mar 02, 2025',
                'lastDate' => $lastDate ?? 'Mar 02, 2025',
                'status' => $status ?? true
                ])
            </div>

            @include('components.creatives.similar-creatives', [
            'inpageClass' => true,
            'creativeComponent' => view('components.creatives.creative-item-inpage', [
            'icon' => '/img/th-1.jpg',
            'activeText' => trans_choice('creatives.card.active', $activeDays ?? 3, ['count' => $activeDays ?? 3]),
            ])->render()
            ])
        </div>
    </div>
</div>