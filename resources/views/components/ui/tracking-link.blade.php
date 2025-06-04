<h3 class="mb-20">{{ $title ?? __('creatives.details.tracking-link') }}</h3>
<div class="form-link mb-25">
    <input type="url" value="{{ $link }}" readonly>
    <a href="{{ $link }}" target="_blank" class="btn-icon _small _white"><span
            class="icon-new-tab remore_margin"></span></a>
</div>