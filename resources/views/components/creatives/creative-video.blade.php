<div class="creative-video {{ $class ?? '' }}">
    <div class="thumb {{ $thumbClass ?? '' }}">
        @if(isset($blurImage))
        <img src="{{ $blurImage }}" alt="" class="thumb-blur">
        @endif
        <img src="{{ $image }}" alt="" class="{{ isset($blurImage) ? 'thumb-contain' : '' }}">
        @if(isset($controls) && $controls)
        <div class="thumb-controls">
            <a href="#" class="btn-icon _black"><span class="icon-download2 remore_margin"></span></a>
            @if(isset($showNewTab) && $showNewTab)
            <a href="#" class="btn-icon _black"><span class="icon-new-tab remore_margin"></span></a>
            @endif
        </div>
        @endif
    </div>
    @if(isset($hasVideo) && $hasVideo)
    <span class="icon-play"></span>
    <div class="creative-video__time">{{ $duration ?? '00:45' }}</div>
    <div class="creative-video__content" data-video="{{ $videoSrc ?? '' }}"></div>
    @endif
</div>