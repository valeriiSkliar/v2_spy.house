@props(['methods' => []])

<div class="payment-methods">
    @foreach($methods as $index => $method)
    <label class="payment-method">
        <input id="{{ $method['id'] }}" type="radio" name="payment" {{ $index===0 ? 'checked' : '' }}
            value="{{ $method['value'] }}">
        <span class="payment-method__content">
            <img src="{{ $method['img'] }}" alt="{{ $method['name'] }}">
            <span>{{ $method['name'] }}</span>
        </span>
    </label>
    @endforeach
</div>