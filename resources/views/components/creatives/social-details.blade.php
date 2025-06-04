<div class="creatives-list__details">
    <div class="creative-details">
        <div class="creative-details__content">
            @include('components.creatives.creative-details-head', [
            'mobileOnly' => true,
            'isFavorite' => $isFavorite ?? false
            ])

            <div class="creative-details__group _first">
                @include('components.creatives.creative-video', [
                'class' => '_single',
                'image' => $videoImage ?? '/img/facebook-2.jpg',
                'blurImage' => $videoImage ?? '/img/facebook-2.jpg',
                'hasVideo' => true,
                'duration' => $duration ?? '00:45',
                'videoSrc' => $videoSrc ?? '/img/video-2.mp4'
                ])
                <div class="row">
                    <div class="col-auto flex-grow-1 mb-10">
                        <a href="#" class="btn _flex _medium _green w-100"><span
                                class="icon-download2 font-16 mr-2"></span>Download</a>
                    </div>
                    <div class="col-auto flex-grow-1 mb-10">
                        <a href="#" class="btn _flex _medium _gray w-100"><span
                                class="icon-new-tab font-16 mr-2"></span>Open in tab</a>
                    </div>
                    <div class="col-auto flex-grow-1 mb-10 d-none d-md-block">
                        <button
                            class="btn _flex _gray _medium btn-favorite {{ isset($isFavorite) && $isFavorite ? 'active' : '' }} w-100">
                            <span
                                class="icon-favorite{{ isset($isFavorite) && $isFavorite ? '' : '-empty' }} font-16 mr-2"></span>
                            {{ isset($isFavorite) && $isFavorite ? 'Remove from favorites' : 'Add to favorites' }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="creative-details__group">
                <p class="mb-15 font-16 font-weight-600">Text</p>
                @include('components.creatives.text-with-copy', [
                'label' => 'Title',
                'text' => $title ?? 'Area71 Eid Salami Offer 2025 – Scholarship + Bonus Gift!',
                'fontWeight' => 'font-weight-500'
                ])
                <div class="mb-0">
                    <div class="mb-10 row align-items-center justify-content-between">
                        <div class="col-auto"><span class="txt-gray">Description</span></div>
                        <div class="col-auto">
                            @include('components.ui.copy-button')
                        </div>
                    </div>
                    @include('components.ui.hidden-text', [
                    'text' => $longDescription ?? 'Extra Benefits: ⏳ Scholarship Offer Valid for 7 Days Only! Website
                    থেকে Enroll করলেই পাচ্ছেন-...',
                    'showTranslate' => true
                    ])
                </div>
            </div>

            <div class="creative-details__group">
                @include('components.creatives.creative-social-metrics', [
                'likes' => $likes ?? '12 285',
                'comments' => $comments ?? '76',
                'shares' => $shares ?? '145'
                ])

                @include('components.ui.tracking-link', [
                'link' => $trackingLink ?? 'https://area71academy.com/trainings/'
                ])

                @include('components.creatives.creative-details-table', [
                'network' => ucfirst($type ?? 'facebook')
                ])
            </div>

            @include('components.creatives.similar-creatives', [
            'socialClass' => true,
            'hasSecondCreative' => true,
            'creativeComponent' => view('components.creatives.creative-item-social', [
            'type' => $type ?? 'facebook',
            'image' => '/img/facebook-1.jpg',
            'controls' => true,
            'showNewTab' => true
            ])->render(),
            'secondCreativeComponent' => view('components.creatives.creative-item-social', [
            'type' => $type ?? 'facebook',
            'image' => '/img/facebook-2.jpg',
            'hasVideo' => true,
            'videoSrc' => '/img/video-1.mp4'
            ])->render()
            ])
        </div>
    </div>
</div>