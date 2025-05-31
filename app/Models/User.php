<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Frontend\NotificationType;
use App\Models\Frontend\Rating;
use App\Notifications\Auth\VerifyEmailNotification;
use App\Notifications\Auth\WelcomeNotification;
use App\Notifications\WelcomeInAppNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Log;

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
        ];
    }

    public function currentTariff()
    {
        // For the demo, return Enterprise tariff
        return [
            'id' => 4,
            'name' => 'Enterprise',
            'css_class' => 'enterprise',
            'expires_at' => '12.06.2024',
            'status' => 'Активная', // or 'Не активно'
        ];
    }

    /**
     * Check if tariff is active
     */
    public function hasTariff($tariffId = null)
    {
        if ($tariffId) {
            return $this->tariff_id == $tariffId && $this->tariff_expires_at > now();
        }

        return $this->tariff_id && $this->tariff_expires_at > now();
    }

    /**
     * Get tariff expiration date
     */
    public function tariffExpiresAt()
    {
        return $this->tariff_expires_at ? $this->tariff_expires_at->format('d.m.Y') : null;
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
    public function sendEmailVerificationNotification()
    {
        $this->notify(new VerifyEmailNotification());
    }

    /**
     * Send welcome notification via email
     */
    public function sendWelcomeNotification(): void
    {
        $this->notify(new WelcomeNotification());
    }

    /**
     * Send welcome notification to in-app database
     */
    public function sendWelcomeInAppNotification(): void
    {
        $this->notify(new WelcomeInAppNotification());
    }
}
