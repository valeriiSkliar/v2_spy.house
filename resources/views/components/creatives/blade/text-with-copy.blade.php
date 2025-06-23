<div class="mb-20">
    <div class="mb-10 row align-items-center justify-content-between">
        <div class="col-auto"><span class="txt-gray">{{ $label }}</span></div>
        <div class="col-auto">
            @include('components.ui.copy-button')
        </div>
    </div>
    <p class="font-roboto {{ isset($fontWeight) ? $fontWeight : '' }} font-16">{{ $text }}</p>
</div>