@extends('layouts.main')

@section('page-content')
<div x-data="creativesFilter">
    <h1>{{ __('creatives.title') }}</h1>
    <div class="row align-items-center">
        <div class="col-12 col-md-auto mb-20 flex-grow-1">
            <x-ui.filter-tabs :activeTab="$activeTab" :counts="$counts" />
        </div>
        <div class="col-12 col-md-auto mb-2">
            <div class="row">
                <div class="col-12 col-md-auto mb-15">
                    <a href="#" class="btn justify-content-start _flex w-100 _medium _gray"><span
                            class="icon-favorite-empty font-16 mr-2"></span>{{ __('creatives.favorites') }} <span {{--
                            TODO: BACKEND=> add favorites count --}}
                            class="btn__count">31</span></a>
                </div>
                <div class="col-12 col-md-auto mb-15">
                    <x-common.base-select-alpina id="creatives-per-page" :options="$perPageOptions"
                        :initial-selected-value="$perPage" placeholder="{{ __('creatives.filter.on-page') }}"
                        icon="list" store-path="creatives.perPage" />
                </div>
            </div>
        </div>
    </div>

    @include('components.creatives.filter')

    <div class="mb-20">
        <div class="search-count" x-show="$store.creatives.totalCount > 0">
            <span x-text="$store.creatives.totalCount"></span> {{ __('creatives.advertisements-found') }}
        </div>
        <div class="search-count" x-show="$store.creatives.totalCount === 0 && !$store.creatives.loading">
            {{ __('creatives.no-results') }}
        </div>
    </div>

    <!-- Loader -->
    <div x-show="$store.creatives.loading" class="text-center py-4">
        <div class="spinner-border" role="status">
            <span class="sr-only">
                {{-- {{ __('common.loading') }} --}}
            </span>
        </div>
    </div>

    <!-- Error State -->
    <div x-show="$store.creatives.error && !$store.creatives.loading" class="alert alert-danger">
        <span x-text="$store.creatives.error"></span>
    </div>

    <!-- Content -->
    <div x-show="!$store.creatives.loading && !$store.creatives.error">
        @if($activeTab == 'push')
        @include('components.creatives.push', ['creatives' => $creatives])
        @elseif($activeTab == 'inpage')
        @include('components.creatives.inpage', ['creatives' => $creatives])
        @elseif($activeTab == 'facebook' || $activeTab == 'tiktok')
        @include('components.creatives.social', ['type' => $activeTab, 'creatives' => $creatives])
        @endif
    </div>

    @include('components.ui.pagination')
</div>
@endsection

@push('scripts')
@vite(['resources/js/creatives/app.js'])
<script type="module">
    // Передаем счетчики табов в JavaScript для Alpine.js
    window.creativesTabCounts = @json($counts);
    
    document.addEventListener('DOMContentLoaded', function() {
        // Creative item details handling
        const showDetailsButtons = document.querySelectorAll('.js-show-details');
        const hideDetailsButtons = document.querySelectorAll('.js-hide-details');
        const creativesListDetails = document.querySelector('.creatives-list__details');

        showDetailsButtons.forEach(button => {
            button.addEventListener('click', function() {
                creativesListDetails?.classList.add('show-details');
            });
        });

        hideDetailsButtons.forEach(button => {
            button.addEventListener('click', function() {
                creativesListDetails?.classList.remove('show-details');
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
                    const videoSrc = videoContent?.dataset.video;

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