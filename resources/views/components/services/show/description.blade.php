@props(['service', 'buttonText' => 'Read more', 'showText' => 'Read more', 'hideText' => 'Hide'])
<div class="single-market__desc">
    <div class="hidden-txt">
        <div class="hidden-txt__content">{{ $service['description'] }}</div>
        <a class="js-toggle-txt" data-show="{{ $showText }}" data-hide="{{ $hideText }}">{{ $buttonText }}</a>
    </div>
</div>