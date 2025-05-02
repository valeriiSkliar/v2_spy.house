<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
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
        'email',
        'password',
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
            'password' => 'hashed',
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
}
