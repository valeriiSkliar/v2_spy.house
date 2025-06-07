# Фронтенд компоненты финансового модуля и их взаимодействие с API

## Архитектура фронтенда

### Технологический стек
- **Alpine.js 3.14.9** - реактивность и управление состоянием
- **jQuery** - DOM манипуляции и AJAX запросы  
- **Axios** - HTTP клиент для API запросов
- **SweetAlert2** - уведомления и модальные окна
- **SCSS + TailwindCSS** - стилизация компонентов

### Паттерны взаимодействия
- **Alpine Store** - централизованное управление состоянием
- **Event-driven** - компоненты общаются через события
- **Component-based** - переиспользуемые UI компоненты

## Основные компоненты финансового модуля

### 1. 💰 BalanceWidget - Виджет баланса

**Назначение**: Отображение текущего баланса пользователя с real-time обновлениями

**Структура файлов**:
```
resources/
├── js/components/finance/
│   └── balance-widget.js
├── views/components/finance/
│   └── balance-widget.blade.php
└── scss/components/
    └── balance-widget.scss
```

**Alpine.js компонент**:
```javascript
// resources/js/components/finance/balance-widget.js
document.addEventListener('alpine:init', () => {
    Alpine.data('balanceWidget', () => ({
        balance: 0,
        currency: 'RUB',
        loading: false,
        lastUpdated: null,
        
        async init() {
            await this.loadBalance();
            // Подписка на обновления баланса
            this.$watch('$store.finance.balance', (value) => {
                this.balance = value;
                this.lastUpdated = new Date();
            });
        },
        
        async loadBalance() {
            this.loading = true;
            try {
                const response = await financeAPI.getBalance();
                this.balance = response.data.balance;
                this.currency = response.data.currency;
                this.lastUpdated = new Date(response.data.last_updated);
            } catch (error) {
                this.handleError(error);
            } finally {
                this.loading = false;
            }
        },
        
        async refreshBalance() {
            await this.loadBalance();
            this.showToast('Баланс обновлен', 'success');
        },
        
        formatBalance() {
            return new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: this.currency
            }).format(this.balance);
        },
        
        handleError(error) {
            console.error('Balance widget error:', error);
            this.showToast('Ошибка загрузки баланса', 'error');
        },
        
        showToast(message, type) {
            this.$dispatch('show-toast', { message, type });
        }
    }));
});
```

**Blade шаблон**:
```blade
{{-- resources/views/components/finance/balance-widget.blade.php --}}
<div x-data="balanceWidget" class="balance-widget">
    <div class="balance-widget__header">
        <h3 class="balance-widget__title">Баланс</h3>
        <button 
            @click="refreshBalance()" 
            class="balance-widget__refresh"
            :disabled="loading"
        >
            <i class="icon-refresh" :class="{ 'spinning': loading }"></i>
        </button>
    </div>
    
    <div class="balance-widget__content">
        <template x-if="loading">
            <div class="balance-widget__skeleton">
                <div class="skeleton-line"></div>
            </div>
        </template>
        
        <template x-if="!loading">
            <div class="balance-widget__amount">
                <span class="balance-widget__value" x-text="formatBalance()"></span>
                <small class="balance-widget__updated" x-show="lastUpdated">
                    Обновлено: <span x-text="lastUpdated?.toLocaleTimeString()"></span>
                </small>
            </div>
        </template>
    </div>
</div>
```

**API взаимодействие**:
```javascript
// GET /api/finance/balance
{
    "success": true,
    "data": {
        "balance": 1250.50,
        "currency": "RUB",
        "last_updated": "2024-01-15T10:30:00Z",
        "version": 15
    }
}
```

### 2. 💳 PaymentForm - Форма оплаты

**Назначение**: Универсальная форма для пополнения баланса и оплаты подписок

**Alpine.js компонент**:
```javascript
// resources/js/components/finance/payment-form.js
document.addEventListener('alpine:init', () => {
    Alpine.data('paymentForm', (options = {}) => ({
        // Состояние формы
        amount: options.amount || '',
        paymentMethod: 'TetherUSDT',
        promocode: '',
        type: options.type || 'deposit', // deposit, subscription
        subscriptionId: options.subscriptionId || null,
        
        // UI состояние
        loading: false,
        validating: false,
        promocodeValid: null,
        promocodeDiscount: 0,
        
        // Методы оплаты
        paymentMethods: [
            { id: 'TetherUSDT', name: 'Tether USDT', icon: '/img/pay/tether.svg', popular: true },
            { id: 'Pay2House', name: 'Pay2.House', icon: '/img/pay/pay2.svg' },
            { id: 'balance', name: 'Баланс', icon: '/img/pay/balance.svg', condition: 'hasBalance' }
        ],
        
        // Валидация
        get isValid() {
            return this.amount > 0 && this.paymentMethod && !this.loading;
        },
        
        get finalAmount() {
            return this.amount - this.promocodeDiscount;
        },
        
        async init() {
            // Загружаем доступные методы оплаты
            await this.loadPaymentMethods();
            
            // Отслеживаем изменения промокода
            this.$watch('promocode', () => {
                if (this.promocode) {
                    this.validatePromocode();
                } else {
                    this.resetPromocode();
                }
            });
        },
        
        async loadPaymentMethods() {
            try {
                const userBalance = await this.$store.finance.getBalance();
                this.paymentMethods = this.paymentMethods.filter(method => {
                    if (method.id === 'balance') {
                        return userBalance >= this.amount;
                    }
                    return true;
                });
            } catch (error) {
                console.error('Error loading payment methods:', error);
            }
        },
        
        async validatePromocode() {
            if (!this.promocode || this.promocode.length < 3) return;
            
            this.validating = true;
            try {
                const response = await financeAPI.validatePromocode({
                    promocode: this.promocode,
                    amount: this.amount,
                    subscription_id: this.subscriptionId
                });
                
                if (response.data.valid) {
                    this.promocodeValid = true;
                    this.promocodeDiscount = response.data.discount_amount;
                } else {
                    this.promocodeValid = false;
                    this.promocodeDiscount = 0;
                }
            } catch (error) {
                this.promocodeValid = false;
                this.promocodeDiscount = 0;
            } finally {
                this.validating = false;
            }
        },
        
        resetPromocode() {
            this.promocodeValid = null;
            this.promocodeDiscount = 0;
        },
        
        async submitPayment() {
            if (!this.isValid) return;
            
            this.loading = true;
            try {
                let response;
                
                if (this.type === 'deposit') {
                    response = await financeAPI.depositFunds({
                        amount: this.amount,
                        payment_method: this.paymentMethod,
                        promocode: this.promocode || null
                    });
                } else {
                    response = await financeAPI.purchaseSubscription({
                        subscription_id: this.subscriptionId,
                        payment_method: this.paymentMethod,
                        promocode: this.promocode || null
                    });
                }
                
                if (response.data.payment_url) {
                    // Редирект на внешнюю платежную систему
                    window.location.href = response.data.payment_url;
                } else {
                    // Платеж обработан (например, списание с баланса)
                    this.handlePaymentSuccess(response.data);
                }
            } catch (error) {
                this.handlePaymentError(error);
            } finally {
                this.loading = false;
            }
        },
        
        handlePaymentSuccess(data) {
            this.$store.finance.loadUserFinanceData();
            this.$dispatch('payment-success', { payment: data });
            this.resetForm();
        },
        
        handlePaymentError(error) {
            const message = error.response?.data?.error?.message || 'Произошла ошибка при обработке платежа';
            this.$dispatch('show-toast', { message, type: 'error' });
        },
        
        resetForm() {
            this.amount = '';
            this.promocode = '';
            this.resetPromocode();
        }
    }));
});
```

**Blade шаблон**:
```blade
{{-- resources/views/components/finance/payment-form.blade.php --}}
<div x-data="paymentForm({ type: '{{ $type ?? 'deposit' }}', subscriptionId: {{ $subscriptionId ?? 'null' }} })" 
     class="payment-form">
     
    <form @submit.prevent="submitPayment()" class="payment-form__form">
        <!-- Сумма -->
        <div class="form-group">
            <label class="form-label">Сумма</label>
            <input 
                type="number" 
                x-model="amount" 
                class="form-input"
                placeholder="Введите сумму"
                min="1"
                step="0.01"
                required
            >
        </div>
        
        <!-- Методы оплаты -->
        <div class="form-group">
            <label class="form-label">Способ оплаты</label>
            <div class="payment-methods">
                <template x-for="method in paymentMethods" :key="method.id">
                    <label class="payment-method">
                        <input 
                            type="radio" 
                            x-model="paymentMethod" 
                            :value="method.id"
                            class="payment-method__radio"
                        >
                        <div class="payment-method__content">
                            <img :src="method.icon" :alt="method.name" class="payment-method__icon">
                            <span class="payment-method__name" x-text="method.name"></span>
                            <span x-show="method.popular" class="payment-method__badge">Популярный</span>
                        </div>
                    </label>
                </template>
            </div>
        </div>
        
        <!-- Промокод -->
        <div class="form-group">
            <label class="form-label">Промокод (необязательно)</label>
            <div class="promocode-input">
                <input 
                    type="text" 
                    x-model="promocode" 
                    class="form-input"
                    :class="{
                        'is-valid': promocodeValid === true,
                        'is-invalid': promocodeValid === false
                    }"
                    placeholder="Введите промокод"
                    :disabled="validating"
                >
                <div class="promocode-status">
                    <i x-show="validating" class="icon-spinner spinning"></i>
                    <i x-show="promocodeValid === true" class="icon-check text-success"></i>
                    <i x-show="promocodeValid === false" class="icon-close text-danger"></i>
                </div>
            </div>
            <div x-show="promocodeDiscount > 0" class="promocode-discount">
                Скидка: <span x-text="promocodeDiscount + ' ₽'"></span>
            </div>
        </div>
        
        <!-- Итоговая сумма -->
        <div x-show="promocodeDiscount > 0" class="form-group">
            <div class="payment-summary">
                <div class="payment-summary__row">
                    <span>Сумма:</span>
                    <span x-text="amount + ' ₽'"></span>
                </div>
                <div class="payment-summary__row">
                    <span>Скидка:</span>
                    <span x-text="'-' + promocodeDiscount + ' ₽'"></span>
                </div>
                <div class="payment-summary__row payment-summary__total">
                    <span>К оплате:</span>
                    <span x-text="finalAmount + ' ₽'"></span>
                </div>
            </div>
        </div>
        
        <!-- Кнопка отправки -->
        <button 
            type="submit" 
            class="btn btn-primary btn-lg w-100"
            :disabled="!isValid"
        >
            <template x-if="loading">
                <i class="icon-spinner spinning mr-2"></i>
            </template>
            <span x-text="loading ? 'Обработка...' : (type === 'deposit' ? 'Пополнить' : 'Оплатить')"></span>
        </button>
    </form>
</div>
```

### 3. 📋 SubscriptionCard - Карточка подписки

**Назначение**: Отображение информации о текущей подписке и управление ею

**Alpine.js компонент**:
```javascript
// resources/js/components/finance/subscription-card.js
document.addEventListener('alpine:init', () => {
    Alpine.data('subscriptionCard', () => ({
        subscription: null,
        loading: true,
        
        async init() {
            await this.loadSubscription();
            
            // Подписка на обновления
            this.$watch('$store.finance.subscription', (value) => {
                this.subscription = value;
            });
        },
        
        async loadSubscription() {
            this.loading = true;
            try {
                const response = await financeAPI.getActiveSubscription();
                this.subscription = response.data.subscription;
            } catch (error) {
                console.error('Error loading subscription:', error);
            } finally {
                this.loading = false;
            }
        },
        
        get isActive() {
            return this.subscription && !this.subscription.is_expired;
        },
        
        get daysLeft() {
            if (!this.subscription) return 0;
            const now = new Date();
            const expiresAt = new Date(this.subscription.expires_at);
            const diffTime = expiresAt - now;
            return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        },
        
        get usagePercentage() {
            if (!this.subscription?.usage) return 0;
            const { api_requests_used, api_requests_limit } = this.subscription.usage;
            return Math.round((api_requests_used / api_requests_limit) * 100);
        },
        
        async cancelSubscription() {
            const result = await Swal.fire({
                title: 'Отменить подписку?',
                text: 'Вы уверены, что хотите отменить подписку?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Да, отменить',
                cancelButtonText: 'Нет'
            });
            
            if (!result.isConfirmed) return;
            
            try {
                await financeAPI.cancelSubscription();
                await this.loadSubscription();
                this.$dispatch('show-toast', { 
                    message: 'Подписка отменена', 
                    type: 'success' 
                });
            } catch (error) {
                this.$dispatch('show-toast', { 
                    message: 'Ошибка отмены подписки', 
                    type: 'error' 
                });
            }
        }
    }));
});
```

### 4. 📊 TransactionHistory - История транзакций

**Назначение**: Отображение истории платежей с фильтрацией и пагинацией

**Alpine.js компонент**:
```javascript
// resources/js/components/finance/transaction-history.js
document.addEventListener('alpine:init', () => {
    Alpine.data('transactionHistory', () => ({
        transactions: [],
        loading: false,
        pagination: {
            current_page: 1,
            total_pages: 1,
            per_page: 20
        },
        filters: {
            date_from: '',
            date_to: '',
            operation_type: '',
            sort: 'created_at',
            order: 'desc'
        },
        
        async init() {
            await this.loadTransactions();
        },
        
        async loadTransactions() {
            this.loading = true;
            try {
                const response = await financeAPI.getPaymentHistory({
                    page: this.pagination.current_page,
                    per_page: this.pagination.per_page,
                    ...this.filters
                });
                
                this.transactions = response.data.items;
                this.pagination = response.data.pagination;
            } catch (error) {
                console.error('Error loading transactions:', error);
                this.$dispatch('show-toast', { 
                    message: 'Ошибка загрузки истории', 
                    type: 'error' 
                });
            } finally {
                this.loading = false;
            }
        },
        
        async changePage(page) {
            this.pagination.current_page = page;
            await this.loadTransactions();
        },
        
        async applyFilters() {
            this.pagination.current_page = 1;
            await this.loadTransactions();
        },
        
        clearFilters() {
            this.filters = {
                date_from: '',
                date_to: '',
                operation_type: '',
                sort: 'created_at',
                order: 'desc'
            };
            this.applyFilters();
        },
        
        formatAmount(amount, operation_type) {
            const formatted = new Intl.NumberFormat('ru-RU', {
                style: 'currency',
                currency: 'RUB'
            }).format(Math.abs(amount));
            
            return operation_type === 'withdrawal' ? `-${formatted}` : `+${formatted}`;
        },
        
        getStatusClass(status) {
            const statusClasses = {
                'completed': 'text-success',
                'pending': 'text-warning',
                'failed': 'text-danger',
                'cancelled': 'text-muted'
            };
            return statusClasses[status] || 'text-muted';
        }
    }));
});
```

### 5. 🔄 PaymentStatus - Статус платежа

**Назначение**: Real-time отслеживание статуса платежа с long polling

**Alpine.js компонент**:
```javascript
// resources/js/components/finance/payment-status.js
document.addEventListener('alpine:init', () => {
    Alpine.data('paymentStatus', (paymentId) => ({
        paymentId,
        status: 'pending',
        loading: true,
        pollTimeout: null,
        maxPollAttempts: 30,
        pollAttempts: 0,
        
        async init() {
            await this.startPolling();
        },
        
        async startPolling() {
            this.pollAttempts = 0;
            await this.pollPaymentStatus();
        },
        
        async pollPaymentStatus() {
            if (this.pollAttempts >= this.maxPollAttempts) {
                this.stopPolling();
                return;
            }
            
            try {
                const response = await financeAPI.pollPaymentStatus(this.paymentId, {
                    timeout: 30
                });
                
                this.status = response.data.status;
                this.loading = false;
                
                if (this.status === 'completed' || this.status === 'failed') {
                    this.handleFinalStatus();
                    return;
                }
                
                this.pollAttempts++;
                this.scheduleNextPoll();
                
            } catch (error) {
                console.error('Polling error:', error);
                this.scheduleNextPoll();
            }
        },
        
        scheduleNextPoll() {
            this.pollTimeout = setTimeout(() => {
                this.pollPaymentStatus();
            }, 5000);
        },
        
        stopPolling() {
            if (this.pollTimeout) {
                clearTimeout(this.pollTimeout);
                this.pollTimeout = null;
            }
        },
        
        handleFinalStatus() {
            this.stopPolling();
            
            if (this.status === 'completed') {
                this.$store.finance.loadUserFinanceData();
                this.$dispatch('payment-completed', { paymentId: this.paymentId });
            } else {
                this.$dispatch('payment-failed', { paymentId: this.paymentId });
            }
        },
        
        get statusText() {
            const statusTexts = {
                'pending': 'Ожидание оплаты',
                'processing': 'Обработка',
                'completed': 'Оплачено',
                'failed': 'Ошибка',
                'cancelled': 'Отменено'
            };
            return statusTexts[this.status] || 'Неизвестно';
        },
        
        get statusIcon() {
            const statusIcons = {
                'pending': 'icon-clock',
                'processing': 'icon-spinner spinning',
                'completed': 'icon-check',
                'failed': 'icon-close',
                'cancelled': 'icon-close'
            };
            return statusIcons[this.status] || 'icon-question';
        }
    }));
});
```

## Финансовый Store (Alpine Store)

### Центральное состояние

```javascript
// resources/js/stores/finance-store.js
document.addEventListener('alpine:init', () => {
    Alpine.store('finance', {
        // Состояние
        balance: 0,
        subscription: null,
        paymentHistory: [],
        loading: false,
        
        // Методы
        async loadUserFinanceData() {
            this.loading = true;
            try {
                const [balanceResponse, subscriptionResponse] = await Promise.all([
                    financeAPI.getBalance(),
                    financeAPI.getActiveSubscription()
                ]);
                
                this.balance = balanceResponse.data.balance;
                this.subscription = subscriptionResponse.data.subscription;
            } catch (error) {
                console.error('Error loading finance data:', error);
            } finally {
                this.loading = false;
            }
        },
        
        async getBalance() {
            if (!this.balance) {
                const response = await financeAPI.getBalance();
                this.balance = response.data.balance;
            }
            return this.balance;
        },
        
        updateBalance(newBalance) {
            this.balance = newBalance;
        },
        
        updateSubscription(subscription) {
            this.subscription = subscription;
        }
    });
});
```

## API клиент для финансов

### Основной API клиент

```javascript
// resources/js/api/finance-api.js
class FinanceAPI {
    constructor() {
        this.client = axios.create({
            baseURL: '/api/finance',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });
        
        this.setupInterceptors();
    }
    
    setupInterceptors() {
        this.client.interceptors.response.use(
            response => response,
            error => {
                if (error.response?.status === 401) {
                    window.location.href = '/login';
                }
                return Promise.reject(error);
            }
        );
    }
    
    // Баланс
    async getBalance() {
        return this.client.get('/balance');
    }
    
    async depositFunds(data) {
        return this.client.post('/deposit', data);
    }
    
    // Подписки
    async getActiveSubscription() {
        return this.client.get('/subscription/active');
    }
    
    async getAvailableSubscriptions() {
        return this.client.get('/subscriptions');
    }
    
    async purchaseSubscription(data) {
        return this.client.post('/subscription/purchase', data);
    }
    
    async cancelSubscription() {
        return this.client.post('/subscription/cancel');
    }
    
    // Платежи
    async getPaymentHistory(params = {}) {
        return this.client.get('/payments', { params });
    }
    
    async getPaymentStatus(paymentId) {
        return this.client.get(`/payments/${paymentId}/status`);
    }
    
    async pollPaymentStatus(paymentId, options = {}) {
        return this.client.get(`/payments/${paymentId}/status/poll`, {
            params: options,
            timeout: (options.timeout || 30) * 1000
        });
    }
    
    // Промокоды
    async validatePromocode(data) {
        return this.client.post('/promocode/validate', data);
    }
}

// Глобальный экземпляр
window.financeAPI = new FinanceAPI();
```

## Интеграция компонентов

### Использование в Blade шаблонах

```blade
{{-- Финансовая страница --}}
@extends('layouts.authorized')

@section('page-content')
<div class="finance-page" x-data="{ activeTab: 'balance' }">
    <!-- Навигация -->
    <div class="finance-tabs">
        <button @click="activeTab = 'balance'" :class="{ active: activeTab === 'balance' }">
            Баланс
        </button>
        <button @click="activeTab = 'subscription'" :class="{ active: activeTab === 'subscription' }">
            Подписка
        </button>
        <button @click="activeTab = 'history'" :class="{ active: activeTab === 'history' }">
            История
        </button>
    </div>
    
    <!-- Контент вкладок -->
    <div class="finance-content">
        <div x-show="activeTab === 'balance'" class="finance-tab-content">
            <x-finance.balance-widget />
            <x-finance.payment-form type="deposit" />
        </div>
        
        <div x-show="activeTab === 'subscription'" class="finance-tab-content">
            <x-finance.subscription-card />
        </div>
        
        <div x-show="activeTab === 'history'" class="finance-tab-content">
            <x-finance.transaction-history />
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ mix('js/components/finance/balance-widget.js') }}"></script>
<script src="{{ mix('js/components/finance/payment-form.js') }}"></script>
<script src="{{ mix('js/components/finance/subscription-card.js') }}"></script>
<script src="{{ mix('js/components/finance/transaction-history.js') }}"></script>
@endpush
```

## Обработка событий и уведомлений

### Система событий

```javascript
// resources/js/events/finance-events.js
document.addEventListener('alpine:init', () => {
    // Глобальные события финансов
    document.addEventListener('payment-success', (event) => {
        const { payment } = event.detail;
        
        // Обновляем состояние
        Alpine.store('finance').loadUserFinanceData();
        
        // Показываем уведомление
        Swal.fire({
            title: 'Платеж успешен!',
            text: `Платеж на сумму ${payment.amount} ₽ обработан`,
            icon: 'success'
        });
    });
    
    document.addEventListener('payment-failed', (event) => {
        const { paymentId } = event.detail;
        
        Swal.fire({
            title: 'Ошибка платежа',
            text: 'Произошла ошибка при обработке платежа',
            icon: 'error'
        });
    });
    
    document.addEventListener('subscription-changed', (event) => {
        Alpine.store('finance').loadUserFinanceData();
    });
});
```

## Тестирование компонентов

### Unit тесты для Alpine компонентов

```javascript
// tests/frontend/finance/balance-widget.test.js
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { Alpine } from 'alpinejs';

describe('BalanceWidget', () => {
    let component;
    
    beforeEach(() => {
        // Mock API
        global.financeAPI = {
            getBalance: vi.fn().mockResolvedValue({
                data: { balance: 1000, currency: 'RUB' }
            })
        };
        
        // Инициализация компонента
        component = Alpine.reactive(balanceWidgetComponent());
    });
    
    it('loads balance on init', async () => {
        await component.init();
        
        expect(component.balance).toBe(1000);
        expect(component.currency).toBe('RUB');
        expect(component.loading).toBe(false);
    });
    
    it('formats balance correctly', () => {
        component.balance = 1234.56;
        component.currency = 'RUB';
        
        const formatted = component.formatBalance();
        expect(formatted).toBe('1 234,56 ₽');
    });
});
```

## Преимущества архитектуры

1. **Модульность**: Каждый компонент независим и переиспользуем
2. **Реактивность**: Автоматическое обновление UI при изменении данных
3. **Типизация**: TypeScript поддержка для API клиента
4. **Тестируемость**: Легкое unit и интеграционное тестирование
5. **Производительность**: Lazy loading и оптимизация запросов
6. **UX**: Real-time обновления и интуитивный интерфейс

Эта архитектура обеспечивает создание современного, отзывчивого и надежного финансового интерфейса, полностью интегрированного с backend API.