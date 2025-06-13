<div class="modal fade modal-current-subscription" id="modal-current-subscription" tabindex="-1" role="dialog"
    aria-labelledby="modal-current-subscription-label" aria-hidden="true" style="z-index: 1070;">
    <div class="modal-dialog">
        <div class="modal-content">
            <button type="button" class="btn-icon _gray btn-close" data-bs-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><span class="icon-x"></span></span>
            </button>
            <div class="subscription-activated">
                <div class="subscription-activated-icon icon-check-circle">
                    <div class="tariff-name _{{ $currentTariff['css_class'] }}">{{ $currentTariff['name'] }}</div>
                </div>
                <h2 class="font-20 mb-20" id="modal-current-subscription-label">Subscription activated</h2>
                <p class="mb-30">
                    Your <strong>"{{ $currentTariff['name'] }}"</strong> subscription is active. <br>
                    Valid until: <span class="icon-clock"></span> <strong>{{ $currentTariff['expires_at'] }}</strong>
                </p>
                <div class="row justify-content-center">
                    <div class="col-auto">
                        <button class="btn _flex _green _medium min-120" data-bs-dismiss="modal">Ok</button>
                    </div>
                    <div class="col-auto">
                        <button class="btn _flex _gray _medium min-120" data-bs-dismiss="modal"
                            onclick="window.location.href='{{ route('tariffs.index') }}'">Change tariff</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>