@props(['service', 'buttonText' => 'Show promo code'])
<div class="single-market__code">
    <button class="btn _code _flex w-100 js-toggle-code">{{ $buttonText }}</button>
    <div class="form-item pb-0">
        <div class="form-item__field _copy mb-0">
            <input type="text" readonly="" value="{{ $service['code'] }}">
            <button type="button" class="btn-copy">
                <span class="icon-copy"></span>
            </button>
        </div>
    </div>
</div>
<div class="single-market__percent">-{{ $service['code_description'] }}</div>