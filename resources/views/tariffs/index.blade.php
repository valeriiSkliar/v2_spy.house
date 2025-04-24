@extends('layouts.authorized')

@section('page-content')
<div class="row align-items-center">
    <div class="col-12 col-lg-auto mr-auto">
        <h1>Tariffs</h1>
    </div>
    <div class="col-6 col-lg-auto mb-15">
        <button class="btn _flex _gray _medium w-100 active" data-tub="month" data-group="pay">For a Month</button>
    </div>
    <div class="col-6 col-lg-auto mb-15">
        <button class="btn _flex _gray _medium w-100" data-tub="year" data-group="pay">For a year <span class="btn__count">Sale</span></button>
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
            <button type="button" class="btn _flex _medium _gray js-toggle-rate" data-show="Показать все" data-hide="Скрыть">
                <span class="icon-down font-18 mr-2"></span>
                <span class="btn__text">Показать все</span>
            </button>
        </div>
    </div>
    <div class="rate__scroll">
        <div class="rate__list">
            @foreach($tariffs as $tariff)
            <div class="rate-item _{{ $tariff['css_class'] }}">
                <div class="rate-item-head">
                    <div class="rate-item-head__title">{{ $tariff['name'] }}</div>
                    <div class="active" data-tub="month" data-group="pay">
                        <div class="rate-item-head__price">${{ $tariff['monthly_price'] }}</div>
                        <div class="rate-item-head__term">за месяц</div>
                    </div>
                    <div data-tub="year" data-group="pay">
                        <div class="rate-item-head__price">${{ $tariff['yearly_price'] }}</div>
                        <div class="rate-item-head__term">за год</div>
                    </div>
                </div>
                <div class="rate-item-body">
                    <div class="rate-item-body__desc">
                        <p><strong>{{ $tariff['active_flows'] }}</strong> Активных потока</p>
                        <p><strong>{{ $tariff['api_requests'] }}</strong> запросов API</p>
                    </div>
                    @foreach(array_slice($tariff['features'], 0, 7) as $feature)
                    <div class="rate-item-body__row"><span class="icon-check"></span></div>
                    @endforeach
                    <div class="rate-item-body__hidden">
                        @foreach(array_slice($tariff['features'], 7) as $feature)
                        <div class="rate-item-body__row">
                            @if($loop->last && $tariff['css_class'] === 'premium' || $tariff['css_class'] === 'enterprise')
                            <span>Приоритетная</span>
                            @else
                            <span class="icon-check"></span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="rate-item-bottom">
                    @if($currentTariff['id'] === $tariff['id'])
                    <button type="button" class="btn w-100 _flex _medium _border-green">Продлить</button>
                    @else
                    <a href="{{ route('tariffs.payment', $tariff['slug']) }}" class="btn w-100 _flex _medium _green">Выбрать</a>
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
                @foreach($payments as $payment)
                <tr>
                    <td>{{ $payment['date'] }}</td>
                    <td>
                        <div class="tariff-name _{{ $payment['tariff_class'] }} _small">{{ $payment['tariff'] }}</div>
                    </td>
                    <td>{{ $payment['type'] }}</td>
                    <td>{{ $payment['payment_method'] }}</td>
                    <td><span class="font-weight-bold">${{ $payment['amount'] }} </span></td>
                    <td><span class="table-status _{{ $payment['status_class'] }}">{{ $payment['status'] }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<nav class="pagination-nav" role="navigation" aria-label="pagination">
    <ul class="pagination-list">
        <li><a class="pagination-link prev disabled" aria-disabled="true" href=""><span class="icon-prev"></span> <span class="pagination-link__txt">Previous</span></a></li>
        <li><a class="pagination-link active" href="#">1</a></li>
        <li><a class="pagination-link" href="#">2</a></li>
        <li><a class="pagination-link" href="#">3</a></li>
        <li><a class="pagination-link next" aria-disabled="false" href="#"><span class="pagination-link__txt">Next</span> <span class="icon-next"></span></a></li>
    </ul>
</nav>
@endsection