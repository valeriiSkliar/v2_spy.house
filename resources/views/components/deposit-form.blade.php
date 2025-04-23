<form action="{{ route('finances.deposit') }}" method="POST" class="max-w-400">
    @csrf
    <input type="hidden" name="payment_method" id="selected_payment_method" value="Tether">

    <div class="row _offset20">
        <div class="col-12 col-md-auto w-md-1 flex-grow-1">
            <div class="form-item mb-20">
                <label class="d-block mb-10 font-weight-600">Deposit Amount</label>
                <div class="form-price">
                    <input type="text" name="amount" class="input-h-57" value="{{ old('amount') }}">
                    <div class="form-price__currency">$</div>
                </div>
                @error('amount')
                <div class="text-danger mt-2">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="col-12 col-md-auto align-self-end">
            <div class="mb-20">
                <button type="submit" class="btn _flex _green _big w-mob-100">Deposit</button>
            </div>
        </div>
    </div>
</form>

<script>
    // Скрипт для обновления скрытого поля при выборе метода оплаты
    document.addEventListener('DOMContentLoaded', function() {
        const paymentMethods = document.querySelectorAll('input[name="payment"]');
        const selectedMethodInput = document.getElementById('selected_payment_method');

        paymentMethods.forEach(method => {
            method.addEventListener('change', function() {
                const methodName = this.closest('.payment-method').querySelector('span > span').textContent;
                selectedMethodInput.value = methodName;
            });
        });
    });
</script>