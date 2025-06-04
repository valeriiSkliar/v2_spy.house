@extends('layouts.authorized')

@section('page-content')
<h1>Creatives</h1>
<div class="row align-items-center">
    <div class="col-12 col-md-auto mb-20 flex-grow-1">
        <div class="filter-push">
            <a href="{{ route('creatives.index', ['type' => 'push']) }}"
                class="filter-push__item {{ $activeTab == 'push' ? 'active' : '' }}">
                Push <span class="filter-push__count">{{ $counts['push'] }}</span>
            </a>
            <a href="{{ route('creatives.index', ['type' => 'inpage']) }}"
                class="filter-push__item {{ $activeTab == 'inpage' ? 'active' : '' }}">
                In Page <span class="filter-push__count">{{ $counts['inpage'] }}</span>
            </a>
            <a href="{{ route('creatives.index', ['type' => 'facebook']) }}"
                class="filter-push__item {{ $activeTab == 'facebook' ? 'active' : '' }}">
                Facebook <span class="filter-push__count">{{ $counts['facebook'] }}</span>
            </a>
            <a href="{{ route('creatives.index', ['type' => 'tiktok']) }}"
                class="filter-push__item {{ $activeTab == 'tiktok' ? 'active' : '' }}">
                TikTok <span class="filter-push__count">{{ $counts['tiktok'] }}</span>
            </a>
        </div>
    </div>
    <div class="col-12 col-md-auto mb-2">
        <div class="row">
            <div class="col-12 col-md-auto mb-15">
                <a href="#" class="btn justify-content-start _flex w-100 _medium _gray"><span
                        class="icon-favorite-empty font-16 mr-2"></span>Favorites <span class="btn__count">31</span></a>
            </div>
            <div class="col-12 col-md-auto mb-15">
                <div class="base-select-icon">
                    <div class="base-select">
                        <div class="base-select__trigger"><span class="base-select__value">On page — 12</span><span
                                class="base-select__arrow"></span></div>
                        <ul class="base-select__dropdown" style="display: none;">
                            <li class="base-select__option is-selected">12</li>
                            <li class="base-select__option">24</li>
                            <li class="base-select__option">48</li>
                            <li class="base-select__option">96</li>
                        </ul>
                    </div>
                    <span class="icon-list"></span>
                </div>
            </div>
        </div>
    </div>
</div>

@include('components.creatives.filter')

<div class="mb-20">
    <div class="search-count"><span>34 567</span> advertisements</div>
</div>

@if($activeTab == 'push')
@include('components.creatives.push')
@elseif($activeTab == 'inpage')
@include('components.creatives.inpage')
@elseif($activeTab == 'facebook' || $activeTab == 'tiktok')
@include('components.creatives.social', ['type' => $activeTab])
@endif

<nav class="pagination-nav" role="navigation" aria-label="pagination">
    <ul class="pagination-list">
        <li><a class="pagination-link prev disabled" aria-disabled="true" href=""><span class="icon-prev"></span> <span
                    class="pagination-link__txt">Previous</span></a></li>
        <li><a class="pagination-link active" href="#">1</a></li>
        <li><a class="pagination-link" href="#">2</a></li>
        <li><a class="pagination-link" href="#">3</a></li>
        <li><a class="pagination-link next" aria-disabled="false" href="#"><span
                    class="pagination-link__txt">Next</span> <span class="icon-next"></span></a></li>
    </ul>
</nav>
@endsection

@section('scripts')
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
@endsection