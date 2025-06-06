<!-- resources/views/components/subscription-activated-modal.blade.php -->
@props(['type' => 'start', 'tariff' => 'Start', 'expires' => '02.05.25'])

<div class="modal fade modal-subscription-activated" id="modal-subscription-activated" style="z-index: 10005;">
    <div class="modal-dialog">
        <div class="modal-content">
            <button type="button" class="btn-icon _gray btn-close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><span class="icon-x remore_margin"></span></span>
            </button>
            <div class="subscription-activated">
                <div class="subscription-activated-icon icon-check-circle">
                    <div class="tariff-name _{{ $type }}">{{ $tariff }}</div>
                </div>
                <h2 class="font-20 mb-20">Subscription activated</h2>
                <p class="mb-30">
                    Your <strong>"{{ $tariff }}"</strong> subscription is active. <br>
                    Valid until: <span class="icon-clock"></span> <strong>{{ $expires }}</strong>
                </p>
                <div class="row justify-content-center">
                    <div class="col-auto">
                        <button class="btn _flex _green _medium min-120" data-dismiss="modal">Ok</button>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('tariffs.index') }}" class="btn _flex _gray _medium min-120">Change tariff</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>