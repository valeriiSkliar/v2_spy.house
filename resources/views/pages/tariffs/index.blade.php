@extends('layouts.main')

@section('page-content')
<div class="row align-items-center">
    <div class="col-12 col-lg-auto mr-auto">
        <h1>Tariffs</h1>
    </div>
    <div class="col-6 col-lg-auto mb-15">
        <button class="btn _flex _gray _medium w-100 active" data-tub="month" data-group="pay">For a Month</button>
    </div>
    <div class="col-6 col-lg-auto mb-15">
        <button class="btn _flex _gray _medium w-100" data-tub="year" data-group="pay">For a year <span
                class="btn__count">Sale</span></button>
    </div>
</div>
<div class="sep _h10"></div>
<div class="rate">
    <div class="rate__fixed">
        <div class="rate__current">
            <div class="rate-current">
                <div class="rate-current__title">{{ $currentTariff['name'] }}</div>
                <div class="rate-current__status">Активная</div>
                <!-- <div class="rate-current__status _disabled">Не активно</div> -->
                <div class="rate-current__term">Действительна до <span>{{ $currentTariff['expires_at'] }}</span></div>
            </div>
        </div>
        <div class="rate-item-body _fixed">
            <div class="rate-item-body__desc">
                <p>Что входит</p>
            </div>
            <div class="rate-item-body__row"><span>Безлимит кликов</span></div>
            <div class="rate-item-body__row"><span>Защита от ботов и всех рекл. источников</span></div>
            <div class="rate-item-body__row"><span>Защита от спай сервисов</span></div>
            <div class="rate-item-body__row"><span>Защита от VPN/Proxy</span></div>
            <div class="rate-item-body__row"><span>Статистика в реальном времени</span></div>
            <div class="rate-item-body__row"><span>PHP Интеграция</span></div>
            <div class="rate-item-body__row"><span>Премиум ГЕО Базы</span></div>
            <div class="rate-item-body__row"><span>Поддержка IPv4</span></div>
            <div class="rate-item-body__hidden">
                <div class="rate-item-body__row"><span>Безлимит кликов</span></div>
                <div class="rate-item-body__row"><span>Поддержка IPv6</span></div>
                <div class="rate-item-body__row"><span>Поддержка ISP</span></div>
                <div class="rate-item-body__row"><span>Поддержка Referrer</span></div>
                <div class="rate-item-body__row"><span>Фильтрация по устройствам</span></div>
                <div class="rate-item-body__row"><span>Фильтрация по операционным системам</span></div>
                <div class="rate-item-body__row"><span>Фильтрация по браузерам</span></div>
                <div class="rate-item-body__row"><span>Фильтрация по черным спискам</span></div>
                <div class="rate-item-body__row"><span>Поддержка всех источников трафика</span></div>
                <div class="rate-item-body__row"><span>Служба поддержки</span></div>
            </div>
        </div>
        <div class="rate-item-bottom _fixed">
            <button type="button" class="btn _flex _medium _gray js-toggle-rate" data-show="Показать все"
                data-hide="Скрыть">
                <span class="icon-down font-18 mr-2"></span>
                <span class="btn__text">Показать все</span>
            </button>
        </div>
    </div>
    <div class="rate__scroll">
        <div class="rate__list">
            @foreach($tariffs as $tariff)
            <div class="rate-item _{{ strtolower($tariff->name) }}">
                <div class="rate-item-head">
                    <div class="rate-item-head__title">{{ $tariff->name }}</div>
                    <div class="active" data-tub="month" data-group="pay">
                        <div class="rate-item-head__price">${{ $tariff->amount }}</div>
                        <div class="rate-item-head__term">за месяц</div>
                    </div>
                    <div data-tub="year" data-group="pay">
                        <div class="rate-item-head__price">${{ number_format($tariff->amount * 12 * (1 -
                            $tariff->early_discount / 100), 2) }}</div>
                        <div class="rate-item-head__term">за год</div>
                        @if($tariff->early_discount > 0)
                        <div class="rate-item-head__discount">Скидка {{ $tariff->early_discount }}%</div>
                        @endif
                    </div>
                </div>
                <div class="rate-item-body">
                    <div class="rate-item-body__desc">
                        <p><strong>{{ $tariff->search_request_count }}</strong> Поисковых запросов</p>
                        <p><strong>{{ $tariff->api_request_count }}</strong> запросов API</p>
                    </div>
                    @for($i = 0; $i < 7; $i++) <div class="rate-item-body__row"><span class="icon-check"></span>
                </div>
                @endfor
                <div class="rate-item-body__hidden">
                    @for($i = 0; $i < 5; $i++) <div class="rate-item-body__row">
                        @if($i === 4 && (strtolower($tariff->name) === 'premium' || strtolower($tariff->name) ===
                        'enterprise'))
                        <span>Приоритетная</span>
                        @else
                        <span class="icon-check"></span>
                        @endif
                </div>
                @endfor
            </div>
        </div>
        <div class="rate-item-bottom">
            @if($currentTariff['id'] === $tariff->id)
            <button type="button" class="btn w-100 _flex _medium _border-green">Продлить</button>
            @else
            <a href="{{ route('tariffs.payment', $tariff->id) }}" class="btn w-100 _flex _medium _green">Выбрать</a>
            @endif
        </div>
    </div>
    @endforeach
</div>
</div>
</div>
<div class="sep _h60"></div>
<h2>Мои платежи</h2>
<div class="c-table">
    <div class="inner">
        <table class="table thead-transparent no-wrap-table">
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>Название</th>
                    <th>Тип</th>
                    <th>Метод оплаты</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                </tr>
            </thead>
            <tbody>

                @php
                $status_classes = [
                'PENDING' => 'warning',
                'SUCCESS' => 'successful',
                'FAILED' => 'rejected',
                ];
                @endphp
                @foreach($payments as $payment)
                <tr>
                    <td>{{ Carbon\Carbon::parse($payment->created_at)->format('d.m.Y / H:i') }}</td>
                    <td>
                        <div class="tariff-name _{{ strtolower($payment->subscription->name) }} _small">{{
                            $payment->subscription->name }}</div>
                    </td>
                    <td>{{ $payment->payment_type }}</td>
                    <td>{{ $payment->payment_method }}</td>
                    <td><span class="font-weight-bold">${{ $payment->amount }} </span></td>
                    <td><span class="table-status _{{ $status_classes[$payment->status->value] }}">{{
                            $payment->status->translatedLabel()
                            }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<nav class="pagination-nav" role="navigation" aria-label="pagination">
    <ul class="pagination-list">
        <li><a class="pagination-link prev disabled" aria-disabled="true" href=""><span class="icon-prev"></span> <span
                    class="pagination-link__txt">Previous</span></a></li>
        <li><a class="pagination-link active" href="#">1</a></li>
        <li><a class="pagination-link" href="#">2</a></li>
        <li><a class="pagination-link" href="#">3</a></li>
        <li><a class="pagination-link next" aria-disabled="false" href="#"><span
                    class="pagination-link__txt">Next</span> <span class="icon-next"></span></a></li>
    </ul>
</nav>
@endsection