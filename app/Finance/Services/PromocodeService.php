<?php

namespace App\Finance\Services;

use App\Finance\Models\Promocode;
use App\Finance\Models\PromocodeActivation;
use App\Finance\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PromocodeService
{
    /**
     * Validate promocode for user and amount
     *
     * @param string $promocodeString
     * @param int $userId
     * @param float $amount
     * @return array
     * @throws ValidationException
     */
    public function validatePromocode(string $promocodeString, int $userId, float $amount): array
    {
        $promocode = Promocode::findByCode($promocodeString);

        if (!$promocode) {
            throw ValidationException::withMessages([
                'promocode' => 'Промокод не найден'
            ]);
        }

        if (!$promocode->isValid()) {
            $message = match ($promocode->status) {
                \App\Enums\Finance\PromocodeStatus::INACTIVE => 'Промокод неактивен',
                \App\Enums\Finance\PromocodeStatus::EXPIRED => 'Промокод истек',
                \App\Enums\Finance\PromocodeStatus::EXHAUSTED => 'Промокод исчерпан',
                default => 'Промокод недействителен'
            };

            throw ValidationException::withMessages([
                'promocode' => $message
            ]);
        }

        if (!$promocode->canBeUsedByUser($userId)) {
            throw ValidationException::withMessages([
                'promocode' => 'Вы уже использовали этот промокод максимальное количество раз'
            ]);
        }

        $discountAmount = $promocode->calculateDiscountAmount($amount);
        $finalAmount = $promocode->calculateFinalAmount($amount);

        return [
            'valid' => true,
            'promocode_id' => $promocode->id,
            'discount_percentage' => $promocode->discount,
            'discount_amount' => $discountAmount,
            'original_amount' => $amount,
            'final_amount' => $finalAmount,
        ];
    }

    /**
     * Apply promocode to payment
     *
     * @param string $promocodeString
     * @param int $userId
     * @param float $amount
     * @param string $ipAddress
     * @param string $userAgent
     * @param int|null $paymentId
     * @return array
     * @throws ValidationException
     */
    public function applyPromocode(
        string $promocodeString,
        int $userId,
        float $amount,
        string $ipAddress,
        string $userAgent,
        ?int $paymentId = null
    ): array {
        $validationResult = $this->validatePromocode($promocodeString, $userId, $amount);

        $promocode = Promocode::find($validationResult['promocode_id']);

        try {
            // Activate promocode for user
            $activation = $promocode->activateForUser($userId, $ipAddress, $userAgent, $paymentId);

            // Log successful activation
            Log::info('Promocode activated', [
                'promocode' => $promocodeString,
                'user_id' => $userId,
                'activation_id' => $activation->id,
                'discount_amount' => $validationResult['discount_amount'],
                'ip_address' => $ipAddress
            ]);

            return array_merge($validationResult, [
                'activation_id' => $activation->id,
                'message' => 'Промокод успешно применен'
            ]);
        } catch (\Exception $e) {
            Log::error('Promocode activation failed', [
                'promocode' => $promocodeString,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'ip_address' => $ipAddress
            ]);

            throw ValidationException::withMessages([
                'promocode' => 'Ошибка применения промокода. Попробуйте позже.'
            ]);
        }
    }

    /**
     * Get user promocode usage statistics
     *
     * @param int $userId
     * @return array
     */
    public function getUserPromocodeStats(int $userId): array
    {
        $activations = PromocodeActivation::byUser($userId)
            ->with(['promocode', 'payment'])
            ->orderBy('created_at', 'desc')
            ->get();

        $totalSaved = $activations->sum(function ($activation) {
            if ($activation->payment) {
                $originalAmount = $activation->payment->amount;
                return $activation->promocode->calculateDiscountAmount($originalAmount);
            }
            return 0;
        });

        return [
            'total_activations' => $activations->count(),
            'total_saved' => $totalSaved,
            'activations' => $activations->map(function ($activation) {
                return [
                    'id' => $activation->id,
                    'promocode' => $activation->promocode->promocode,
                    'discount_percentage' => $activation->promocode->discount,
                    'payment_id' => $activation->payment_id,
                    'created_at' => $activation->created_at,
                ];
            })
        ];
    }

    /**
     * Create new promocode
     *
     * @param array $data
     * @param int $createdByUserId
     * @return Promocode
     */
    public function createPromocode(array $data, int $createdByUserId): Promocode
    {
        $promocodeData = array_merge($data, [
            'created_by_user_id' => $createdByUserId,
            'promocode' => $data['promocode'] ?? Promocode::generateUniqueCode()
        ]);

        $promocode = Promocode::create($promocodeData);

        Log::info('Promocode created', [
            'promocode_id' => $promocode->id,
            'promocode' => $promocode->promocode,
            'discount' => $promocode->discount,
            'created_by_user_id' => $createdByUserId
        ]);

        return $promocode;
    }

    /**
     * Check for potential promocode abuse
     *
     * @param string $ipAddress
     * @param int $userId
     * @return bool
     */
    public function checkForAbuse(string $ipAddress, int $userId): bool
    {
        $recentActivations = PromocodeActivation::where('ip_address', $ipAddress)
            ->where('created_at', '>=', now()->subHours(24))
            ->count();

        // Too many activations from same IP in 24 hours
        if ($recentActivations > 10) {
            Log::warning('Potential promocode abuse detected', [
                'ip_address' => $ipAddress,
                'user_id' => $userId,
                'activations_count' => $recentActivations
            ]);
            return true;
        }

        return false;
    }
}
