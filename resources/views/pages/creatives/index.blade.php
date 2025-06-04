@extends('layouts.main')

@section('page-content')
<h1>{{ __('creatives.title') }}</h1>
<div class="row align-items-center">
    <div class="col-12 col-md-auto mb-20 flex-grow-1">
        <x-ui.filter-tabs :activeTab="$activeTab" :counts="$counts" />
    </div>
    <div class="col-12 col-md-auto mb-2">
        <div class="row">
            <div class="col-12 col-md-auto mb-15">
                <a href="#" class="btn justify-content-start _flex w-100 _medium _gray"><span
                        class="icon-favorite-empty font-16 mr-2"></span>{{ __('creatives.favorites') }} <span {{-- TODO:
                        BACKEND=> add favorites count --}}
                        class="btn__count">31</span></a>
            </div>
            <div class="col-12 col-md-auto mb-15">
                <div class="base-select-icon">
                    <x-common.base-select id="creatives-per-page" :placeholder="__('creatives.filter.on-page')"
                        :selected="['value' => '12', 'order' => '1', 'label' => '12']" :options="[
                    ['value' => '12', 'order' => '1', 'label' => '12'],
                    ['value' => '24', 'order' => '2', 'label' => '24'],
                    ['value' => '48', 'order' => '3', 'label' => '48'],
                    ['value' => '96', 'order' => '4', 'label' => '96']
                    ]" />
                    <span class="icon-list"></span>
                </div>
            </div>
        </div>
    </div>
</div>

@include('components.creatives.filter')

<div class="mb-20">
    <div class="search-count">{!! trans_choice('creatives.advertisements', 34567, ['count' => 34567]) !!}</div>
</div>

@if($activeTab == 'push')
@include('components.creatives.push')
@elseif($activeTab == 'inpage')
@include('components.creatives.inpage')
@elseif($activeTab == 'facebook' || $activeTab == 'tiktok')
@include('components.creatives.social', ['type' => $activeTab])
@endif

@include('components.ui.pagination')
@endsection

@push('scripts')
@vite(['resources/js/creatives/app.js'])
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Creative item details handling
        const showDetailsButtons = document.querySelectorAll('.js-show-details');
        const hideDetailsButtons = document.querySelectorAll('.js-hide-details');
        const creativesListDetails = document.querySelector('.creatives-list__details');

        showDetailsButtons.forEach(button => {
            button.addEventListener('click', function() {
                creativesListDetails.classList.add('show-details');
            });
        });

        hideDetailsButtons.forEach(button => {
            button.addEventListener('click', function() {
                creativesListDetails.classList.remove('show-details');
            });
        });

        // Copy button functionality
        const copyButtons = document.querySelectorAll('.js-copy');
        copyButtons.forEach(button => {
            button.addEventListener('click', function() {
                const copyIcon = this.querySelector('.icon-copy');
                const checkIcon = this.querySelector('.icon-check');

                // Show check icon temporarily
                if (copyIcon && checkIcon) {
                    copyIcon.classList.add('d-none');
                    checkIcon.classList.remove('d-none');

                    setTimeout(() => {
                        copyIcon.classList.remove('d-none');
                        checkIcon.classList.add('d-none');
                    }, 2000);
                }
            });
        });

        // Video play functionality
        const videoElements = document.querySelectorAll('.creative-video');
        videoElements.forEach(element => {
            const playButton = element.querySelector('.icon-play');
            if (playButton) {
                playButton.addEventListener('click', function() {
                    const videoContent = element.querySelector('.creative-video__content');
                    const videoSrc = videoContent.dataset.video;

                    if (videoSrc) {
                        element.classList.add('_playing');
                        const videoElement = document.createElement('video');
                        videoElement.src = videoSrc;
                        videoElement.controls = true;
                        videoElement.autoplay = true;

                        videoContent.innerHTML = '';
                        videoContent.appendChild(videoElement);
                    }
                });
            }
        });
    });
</script>
@endpush