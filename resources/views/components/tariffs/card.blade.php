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
    <a href="{{ route('tariffs.payment', ['slug' => $tariff->id, 'billing_type' => 'month']) }}"
        class="btn w-100 _flex _medium _green tariff-select-btn" data-tariff-id="{{ $tariff->id }}"
        data-billing-type="month">Выбрать</a>
    @endif
</div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Обработка переключения между месяцем и годом для каждой карточки
    const rateItems = document.querySelectorAll('.rate-item');
    
    rateItems.forEach(rateItem => {
        const monthTab = rateItem.querySelector('[data-tub="month"]');
        const yearTab = rateItem.querySelector('[data-tub="year"]');
        const selectBtn = rateItem.querySelector('.tariff-select-btn');
        
        if (!selectBtn) return; // Если кнопка "Продлить", то пропускаем
        
        // Обработчики кликов на табы
        [monthTab, yearTab].forEach(tab => {
            if (tab) {
                tab.addEventListener('click', function() {
                    const billingType = this.getAttribute('data-tub');
                    
                    // Убираем активный класс у всех табов в этой карточке
                    rateItem.querySelectorAll('[data-group="pay"]').forEach(t => {
                        t.classList.remove('active');
                    });
                    
                    // Добавляем активный класс к выбранному табу
                    this.classList.add('active');
                    
                    // Обновляем ссылку кнопки
                    const tariffId = selectBtn.getAttribute('data-tariff-id');
                    const newUrl = "{{ route('tariffs.payment', ['slug' => ':tariff_id', 'billing_type' => ':billing_type']) }}"
                        .replace(':tariff_id', tariffId)
                        .replace(':billing_type', billingType);
                    
                    selectBtn.href = newUrl;
                    selectBtn.setAttribute('data-billing-type', billingType);
                });
            }
        });
    });
});
</script>