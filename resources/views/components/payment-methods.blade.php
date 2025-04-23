@props(['methods' => []])

<div class="payment-methods">
    @foreach($methods as $index => $method)
    <label class="payment-method">
        <input type="radio" name="payment" {{ $index === 0 ? 'checked' : '' }}>
        <span class="payment-method__content">
            <img src="{{ $method['img'] }}" alt="{{ $method['name'] }}">
            <span>{{ $method['name'] }}</span>
        </span>
    </label>
    @endforeach
</div>