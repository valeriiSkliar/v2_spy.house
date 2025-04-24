@extends('layouts.authorized')

@section('content')
<div class="container">
    <h1>Тарифы</h1>

    <div class="tariffs">
        @foreach($tariffs as $tariff)
        <div class="tariff-card {{ $tariff['css_class'] }}">
            <h2>{{ $tariff['name'] }}</h2>
            <div class="price">
                ${{ $tariff['price'] }}/{{ $tariff['period'] }}
            </div>
            <ul class="features">
                @foreach($tariff['features'] as $feature)
                <li>{{ $feature }}</li>
                @endforeach
            </ul>
            <a href="#" class="btn btn-primary">Выбрать тариф</a>
        </div>
        @endforeach
    </div>
</div>
@endsection