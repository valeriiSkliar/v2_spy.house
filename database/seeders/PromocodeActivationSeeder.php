<?php

namespace Database\Seeders;

use App\Finance\Models\Payment;
use App\Finance\Models\Promocode;
use App\Finance\Models\PromocodeActivation;
use App\Models\User;
use Illuminate\Database\Seeder;

class PromocodeActivationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Получаем промокоды и пользователей для активаций
        $promocodes = Promocode::all();
        $users = User::all();

        if ($promocodes->isEmpty()) {
            $this->command->warn('No promocodes found. Please run PromocodeSeeder first.');

            return;
        }

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Creating test users.');
            $users = User::factory()->count(5)->create();
        }

        // Берем первые 10 пользователей для активаций
        $testUsers = $users->take(10);

        // Создаем активации для конкретных промокодов
        $welcomePromo = $promocodes->where('promocode', 'WELCOME10')->first();
        if ($welcomePromo) {
            // Несколько пользователей активировали WELCOME10
            foreach ($testUsers->take(3) as $user) {
                PromocodeActivation::factory()->create([
                    'promocode_id' => $welcomePromo->id,
                    'user_id' => $user->id,
                    'ip_address' => fake()->ipv4(),
                    'user_agent' => fake()->userAgent(),
                    'created_at' => now()->subDays(rand(1, 30)),
                ]);
            }
        }

        $save20Promo = $promocodes->where('promocode', 'SAVE20')->first();
        if ($save20Promo) {
            // SAVE20 использовали несколько раз разные пользователи
            foreach ($testUsers->take(5) as $user) {
                PromocodeActivation::factory()->create([
                    'promocode_id' => $save20Promo->id,
                    'user_id' => $user->id,
                    'ip_address' => fake()->ipv4(),
                    'user_agent' => fake()->userAgent(),
                    'created_at' => now()->subDays(rand(1, 15)),
                ]);
            }
        }

        $foreverPromo = $promocodes->where('promocode', 'FOREVER15')->first();
        if ($foreverPromo) {
            // FOREVER15 - популярный промокод, много активаций
            foreach ($testUsers as $user) {
                // Некоторые пользователи использовали его несколько раз (max_per_user = 3)
                $activationCount = rand(1, 3);
                for ($i = 0; $i < $activationCount; $i++) {
                    PromocodeActivation::factory()->create([
                        'promocode_id' => $foreverPromo->id,
                        'user_id' => $user->id,
                        'ip_address' => fake()->ipv4(),
                        'user_agent' => fake()->userAgent(),
                        'created_at' => now()->subDays(rand(7, 60)),
                    ]);
                }
            }
        }

        // Создаем активации с привязкой к платежам (если они есть)
        $payments = Payment::limit(5)->get();
        if ($payments->isNotEmpty()) {
            foreach ($payments as $payment) {
                // Случайным образом привязываем промокод к платежу
                $randomPromocode = $promocodes->random();
                PromocodeActivation::factory()->create([
                    'promocode_id' => $randomPromocode->id,
                    'user_id' => $payment->user_id,
                    'payment_id' => $payment->id,
                    'ip_address' => fake()->ipv4(),
                    'user_agent' => fake()->userAgent(),
                    'created_at' => $payment->created_at,
                ]);
            }
        }

        // Создаем активации для других случайных промокодов
        $otherPromocodes = $promocodes->whereNotIn('promocode', ['WELCOME10', 'SAVE20', 'FOREVER15']);
        foreach ($otherPromocodes as $promocode) {
            // Каждый промокод активируют 1-3 пользователя
            $activatingUsers = $testUsers->random(rand(1, 3));
            foreach ($activatingUsers as $user) {
                PromocodeActivation::factory()->create([
                    'promocode_id' => $promocode->id,
                    'user_id' => $user->id,
                    'ip_address' => fake()->ipv4(),
                    'user_agent' => fake()->userAgent(),
                    'created_at' => now()->subDays(rand(1, 90)),
                ]);
            }
        }

        // Создаем несколько активаций с одинаковым IP (для тестирования системы обнаружения злоупотреблений)
        $suspiciousIp = '192.168.1.100';
        $suspiciousUsers = $testUsers->take(3);
        $testPromocode = $promocodes->random();

        foreach ($suspiciousUsers as $user) {
            PromocodeActivation::factory()->create([
                'promocode_id' => $testPromocode->id,
                'user_id' => $user->id,
                'ip_address' => $suspiciousIp,
                'user_agent' => 'Mozilla/5.0 (Suspicious Bot)',
                'created_at' => now()->subHours(rand(1, 12)),
            ]);
        }

        // Дополнительные случайные активации
        PromocodeActivation::factory()->count(20)->create();

        $this->command->info('Created '.PromocodeActivation::count().' promocode activations');
        $this->command->info('Test data includes:');
        $this->command->info('- Regular user activations');
        $this->command->info('- Multiple uses of same promocode');
        $this->command->info('- Activations with payment associations');
        $this->command->info('- Suspicious IP activity for abuse testing');
    }
}
