<h3 class="mb-15">Choose a convenient payment method:</h3>
<div class="payment-methods">
    @foreach($paymentMethods as $index => $method)
    <label class="payment-method">
        <input type="radio" name="payment" {{ $index===0 ? 'checked' : '' }}>
        <span class="payment-method__content"><img src="{{ $method['img'] }}" alt="{{ $method['name'] }}"> <span>{{
                $method['name'] }}</span></span>
    </label>
    @endforeach
</div>