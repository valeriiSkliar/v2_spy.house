<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Frontend\NotificationType;
use App\Finance\Models\Subscription;
use App\Models\Frontend\Rating;
use App\Notifications\Auth\VerifyEmailNotification;
use App\Notifications\Auth\WelcomeNotification;
use App\Notifications\WelcomeInAppNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'login',
        'surname',
        'date_of_birth',
        'experience',
        'scope',
        'email',
        'password',
        'notification_settings',
        'preferred_locale',
        'phone_country_code',
        'phone',
        'messenger_type',
        'messenger_contact',
        'scope_of_activity',
        'personal_greeting',
        'ip_restrictions',
        'google_2fa_enabled',
        'google_2fa_secret',
        'user_avatar',
        'last_password_reset_at',
        'email_contact_id',
        'is_newsletter_subscribed',
        'unsubscribe_hash',
        // Financial system fields
        'available_balance',
        'subscription_id',
        'subscription_time_start',
        'subscription_time_end',
        'subscription_is_expired',
        'queued_subscription_id',
        'balance_version',
        'is_trial_period',
        'trial_period_used',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'tariff_expires_at' => 'datetime',
            'last_password_reset_at' => 'datetime',
            'password' => 'hashed',
            'notification_settings' => 'array',
            'ip_restrictions' => 'array',
            'google_2fa_enabled' => 'boolean',
            'is_newsletter_subscribed' => 'boolean',
            // Financial system casts
            'available_balance' => 'decimal:2',
            'subscription_time_start' => 'datetime',
            'subscription_time_end' => 'datetime',
            'subscription_is_expired' => 'boolean',
            'balance_version' => 'integer',
            'is_trial_period' => 'boolean',
            'trial_period_used' => 'boolean',
        ];
    }

    /**
     * Get user's current subscription
     */
    public function subscription()
    {
        return $this->belongsTo(\App\Finance\Models\Subscription::class);
    }

    /**
     * Get flag for show upgrade tariff promo in sidebar
     */
    public function showUpgradeTariffPromo(): bool
    {
        $currentTariff = $this->currentTariff();

        return Subscription::shouldShowPromoForSubscription($currentTariff['name']);
    }

    public function currentTariff(): array
    {
        // Если активен триал период
        if ($this->isTrialPeriod()) {
            return [
                'id' => null,
                'name' => 'Trial',
                'css_class' => 'trial',
                'expires_at' => $this->subscription_time_end ? $this->subscription_time_end->format('d.m.Y') : null,
                'status' => 'Триал активен',
                'is_active' => true,
                'is_trial' => true,
            ];
        }

        if (! $this->subscription_id || ! $this->subscription) {
            return [
                'id' => null,
                'name' => 'Free',
                'css_class' => 'free',
                'expires_at' => null,
                'status' => 'Не активно',
                'is_active' => false,
                'is_trial' => false,
            ];
        }

        $isActive = $this->hasActiveSubscription();

        return [
            'id' => $this->subscription->id,
            'name' => $this->subscription->name,
            'css_class' => strtolower($this->subscription->name),
            'expires_at' => $this->subscription_time_end ? $this->subscription_time_end->format('d.m.Y') : null,
            'status' => $isActive ? 'Активная' : 'Не активно',
            'is_active' => $isActive,
            'is_trial' => false,
        ];
    }

    /**
     * Check if user has active subscription (new method for subscription system)
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription_id
            && $this->subscription_time_end
            && $this->subscription_time_end > now()
            && ! $this->subscription_is_expired;
    }

    /**
     * Check if user has specific subscription or any active subscription
     */
    public function hasTariff($subscriptionId = null): bool
    {
        if ($subscriptionId) {
            return $this->subscription_id == $subscriptionId && $this->hasActiveSubscription();
        }

        return $this->hasActiveSubscription();
    }

    /**
     * Get subscription expiration date
     */
    public function tariffExpiresAt(): ?string
    {
        return $this->subscription_time_end ? $this->subscription_time_end->format('d.m.Y') : null;
    }

    /**
     * Get formatted balance with currency
     */
    public function getFormattedBalance(): string
    {
        return '$' . number_format($this->available_balance, 2);
    }
    /**
     * Get formatted balance with currency
     */
    public function getFormattedBalanceWithoutCurrency(): string
    {
        return number_format($this->available_balance, 2);
    }

    /**
     * Check if user has sufficient balance for amount
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->available_balance >= $amount;
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * Get the refresh tokens for the user.
     */
    public function refreshTokens(): HasMany
    {
        return $this->hasMany(RefreshToken::class);
    }

    public function getRatingForService(int $serviceId): ?int
    {
        $rating = $this->ratings()->where('service_id', $serviceId)->first();

        return $rating?->rating;
    }

    public function getFullPhoneNumber(): ?string
    {
        if ($this->phone_country_code && $this->phone) {
            return '+' . $this->phone_country_code . $this->phone;
        }

        return null;
    }

    public function isTwoFactorEnabled(): bool
    {
        return (bool) $this->google_2fa_enabled;
    }

    public function validateIpRestriction(string $ip): bool
    {
        if (empty($this->ip_restrictions)) {
            return true; // No restrictions means IP is allowed
        }

        return in_array($ip, $this->ip_restrictions);
    }

    public function setPersonalGreeting(string $greeting): void
    {
        $this->personal_greeting = $greeting;
        $this->save();
    }

    public function getPersonalGreeting(): ?string
    {
        return $this->personal_greeting;
    }

    /**
     * Проверяет, включены ли уведомления определенного типа
     *
     * @param  NotificationType|string  $type
     */
    public function hasNotificationEnabled($type): bool
    {
        if ($type instanceof NotificationType) {
            $type = $type->value;
        }

        $settings = $this->notification_settings ?? [];

        // Если настроек для этого типа нет, считаем, что уведомления включены
        if (! isset($settings[$type])) {
            return true;
        }

        // Если настройка установлена в false, уведомления отключены
        if ($settings[$type] === false) {
            return false;
        }

        // Если настройка - массив каналов, проверяем, не пустой ли он
        if (is_array($settings[$type])) {
            return ! empty($settings[$type]);
        }

        // По умолчанию считаем, что уведомления включены
        return true;
    }

    /**
     * Проверяет, включен ли указанный канал для определенного типа уведомлений
     *
     * @param  NotificationType|string  $type
     */
    public function hasNotificationChannelEnabled($type, string $channel): bool
    {
        if ($type instanceof NotificationType) {
            $type = $type->value;
        }

        $settings = $this->notification_settings ?? [];

        // Получаем настройки типа уведомления
        $notificationType = app(\App\Models\NotificationType::class)->where('key', $type)->first();
        $defaultChannels = $notificationType ? $notificationType->default_channels : ['mail'];

        // Если настроек для этого типа нет, проверяем каналы по умолчанию
        if (! isset($settings[$type])) {
            return in_array($channel, $defaultChannels);
        }

        // Если настройка установлена в false, канал отключен
        if ($settings[$type] === false) {
            return false;
        }

        // Если настройка - массив каналов, проверяем наличие указанного канала
        if (is_array($settings[$type])) {
            return in_array($channel, $settings[$type]);
        }

        // Проверяем каналы по умолчанию
        return in_array($channel, $defaultChannels);
    }

    /**
     * Включает или отключает уведомления определенного типа
     *
     * @param  NotificationType|string  $type
     */
    public function setNotificationEnabled($type, bool $enabled): void
    {
        if ($type instanceof NotificationType) {
            $type = $type->value;
        }

        $settings = $this->notification_settings ?? [];

        if ($enabled) {
            $notificationType = app(\App\Models\NotificationType::class)->where('key', $type)->first();
            $settings[$type] = $notificationType ? $notificationType->default_channels : ['mail'];
        } else {
            $settings[$type] = false;
        }

        $this->notification_settings = $settings;
        $this->save();
    }

    /**
     * Устанавливает каналы для определенного типа уведомлений
     *
     * @param  NotificationType|string  $type
     */
    public function setNotificationChannels($type, array $channels): void
    {
        if ($type instanceof NotificationType) {
            $type = $type->value;
        }

        $settings = $this->notification_settings ?? [];
        $settings[$type] = $channels;

        $this->notification_settings = $settings;
        $this->save();
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification(?string $verificationCode = null)
    {
        $this->notify(new VerifyEmailNotification($verificationCode));
    }

    /**
     * Send welcome notification via email
     */
    public function sendWelcomeNotification(): void
    {
        $this->notify(new WelcomeNotification);
    }

    /**
     * Send welcome notification to in-app database
     */
    public function sendWelcomeInAppNotification(): void
    {
        $this->notify(new WelcomeInAppNotification);
    }

    /**
     * Get all payments for the user.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(\App\Finance\Models\Payment::class);
    }

    public function deposits(): HasMany
    {
        return $this->payments()->where('payment_type', \App\Enums\Finance\PaymentType::DEPOSIT);
    }

    /**
     * Get successful payments for the user.
     */
    public function successfulPayments(): HasMany
    {
        return $this->payments()->successful();
    }

    /**
     * Get pending payments for the user.
     */
    public function pendingPayments(): HasMany
    {
        return $this->payments()->pending();
    }

    /**
     * Get deposit payments for the user.
     */
    public function depositPayments(): HasMany
    {
        return $this->payments()->deposits();
    }

    /**
     * Get subscription payments for the user.
     */
    public function subscriptionPayments(): HasMany
    {
        return $this->payments()->subscriptions()->with('subscription')->orderBy('created_at', 'desc');
    }

    /**
     * Get all active subscriptions for the user.
     */
    public function activeSubscriptions()
    {
        return $this->successfulPayments()
            ->with('subscription')
            ->whereHas('subscription', function ($query) {
                $query->where('status', 'active');
            })
            ->get()
            ->pluck('subscription')
            ->unique('id');
    }

    public function isTrialPeriod(): bool
    {
        // Если флаг триала не установлен, триал не активен
        if (!$this->is_trial_period) {
            return false;
        }

        // Если нет даты окончания триала, значит триал был установлен неправильно
        if (!$this->subscription_time_end) {
            return false;
        }

        // Проверяем, не истек ли триал
        if ($this->subscription_time_end->isPast()) {
            // Автоматически снимаем флаг триала если срок истек
            $this->update([
                'is_trial_period' => false,
                'subscription_time_start' => null,
                'subscription_time_end' => null,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Активировать 7-дневный триал период
     */
    public function activateTrialPeriod(): void
    {
        $this->update([
            'is_trial_period' => true,
            'trial_period_used' => true,
            'subscription_time_start' => now(),
            'subscription_time_end' => now()->addDays(7),
            'subscription_is_expired' => false,
        ]);
    }

    /**
     * Проверить, был ли триал период уже использован
     */
    public function hasTrialPeriodBeenUsed(): bool
    {
        return $this->trial_period_used;
    }

    /**
     * Получить количество оставшихся дней триала
     */
    public function getTrialDaysLeft(): int
    {
        if (!$this->isTrialPeriod() || !$this->subscription_time_end) {
            return 0;
        }

        // Простой подсчет: разница в днях между окончанием триала и сегодня
        $now = now()->startOfDay();
        $endDate = $this->subscription_time_end->startOfDay();

        if ($endDate <= $now) {
            return 0; // Триал истек
        }

        return (int) $now->diffInDays($endDate, false);
    }
}
