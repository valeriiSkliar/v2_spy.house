<?php

namespace Database\Seeders;

use App\Enums\Finance\PaymentMethod;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\PaymentType;
use App\Finance\Models\Payment;
use App\Finance\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Получаем пользователей и подписки
        $users = User::all();
        $subscriptions = Subscription::all();

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Creating test users.');
            $users = User::factory()->count(10)->create();
        }

        if ($subscriptions->isEmpty()) {
            $this->command->warn('No subscriptions found. Please run SubscriptionSeeder first.');
            return;
        }

        // Берем первых 10 пользователей для платежей
        $testUsers = $users->take(10);

        // Создаем успешные платежи за подписки (будут использоваться для промокодов)
        foreach ($testUsers->take(5) as $user) {
            // Успешная покупка подписки через USDT
            Payment::factory()->create([
                'user_id' => $user->id,
                'amount' => $subscriptions->random()->amount,
                'payment_type' => PaymentType::DIRECT_SUBSCRIPTION,
                'subscription_id' => $subscriptions->random()->id,
                'payment_method' => PaymentMethod::USDT,
                'status' => PaymentStatus::SUCCESS,
                'webhook_processed_at' => now()->subDays(rand(1, 30)),
                'promocode_id' => null, // Будет заполнено в PromocodeActivationSeeder
            ]);

            // Успешная покупка подписки через Pay2.House
            Payment::factory()->create([
                'user_id' => $user->id,
                'amount' => $subscriptions->random()->amount,
                'payment_type' => PaymentType::DIRECT_SUBSCRIPTION,
                'subscription_id' => $subscriptions->random()->id,
                'payment_method' => PaymentMethod::PAY2_HOUSE,
                'status' => PaymentStatus::SUCCESS,
                'webhook_processed_at' => now()->subDays(rand(1, 15)),
                'promocode_id' => null,
            ]);
        }

        // Создаем успешные депозиты (пополнения баланса)
        foreach ($testUsers->take(7) as $user) {
            Payment::factory()->create([
                'user_id' => $user->id,
                'amount' => fake()->randomFloat(2, 50, 500),
                'payment_type' => PaymentType::DEPOSIT,
                'subscription_id' => null,
                'payment_method' => PaymentMethod::USDT,
                'status' => PaymentStatus::SUCCESS,
                'webhook_processed_at' => now()->subDays(rand(1, 45)),
                'promocode_id' => null,
            ]);
        }

        // Создаем платежи с баланса (за подписки) - используем USER_BALANCE вместо INTERNAL_BALANCE
        foreach ($testUsers->take(4) as $user) {
            Payment::factory()->create([
                'user_id' => $user->id,
                'amount' => $subscriptions->random()->amount,
                'payment_type' => PaymentType::DIRECT_SUBSCRIPTION,
                'subscription_id' => $subscriptions->random()->id,
                'payment_method' => PaymentMethod::USER_BALANCE,
                'status' => PaymentStatus::SUCCESS,
                'webhook_processed_at' => now()->subDays(rand(1, 20)),
                'promocode_id' => null,
            ]);
        }

        // Создаем несколько ожидающих платежей
        foreach ($testUsers->take(3) as $user) {
            Payment::factory()->create([
                'user_id' => $user->id,
                'amount' => $subscriptions->random()->amount,
                'payment_type' => PaymentType::DIRECT_SUBSCRIPTION,
                'subscription_id' => $subscriptions->random()->id,
                'payment_method' => PaymentMethod::USDT,
                'status' => PaymentStatus::PENDING,
                'webhook_processed_at' => null,
                'promocode_id' => null,
            ]);
        }

        // Создаем несколько неудачных платежей
        foreach ($testUsers->take(2) as $user) {
            Payment::factory()->create([
                'user_id' => $user->id,
                'amount' => $subscriptions->random()->amount,
                'payment_type' => PaymentType::DIRECT_SUBSCRIPTION,
                'subscription_id' => $subscriptions->random()->id,
                'payment_method' => PaymentMethod::PAY2_HOUSE,
                'status' => PaymentStatus::FAILED,
                'webhook_processed_at' => now()->subDays(rand(1, 7)),
                'promocode_id' => null,
            ]);
        }

        // Создаем дополнительные случайные платежи
        Payment::factory()->count(15)->create();

        // Создаем несколько платежей с конкретными суммами (для удобства тестирования промокодов)
        $testAmounts = [100.00, 50.00, 200.00, 75.00, 150.00];
        foreach ($testAmounts as $amount) {
            Payment::factory()->create([
                'user_id' => $testUsers->random()->id,
                'amount' => $amount,
                'payment_type' => PaymentType::DIRECT_SUBSCRIPTION,
                'subscription_id' => $subscriptions->random()->id,
                'payment_method' => PaymentMethod::USDT,
                'status' => PaymentStatus::SUCCESS,
                'webhook_processed_at' => now()->subDays(rand(1, 30)),
                'promocode_id' => null,
            ]);
        }

        $this->command->info('Created ' . Payment::count() . ' payments');
        $this->command->info('Payment types created:');
        $this->command->info('- Direct subscription payments: ' . Payment::where('payment_type', PaymentType::DIRECT_SUBSCRIPTION)->count());
        $this->command->info('- Deposit payments: ' . Payment::where('payment_type', PaymentType::DEPOSIT)->count());
        $this->command->info('- Balance subscription payments: ' . Payment::where('payment_method', PaymentMethod::USER_BALANCE)->count());
        $this->command->info('- Successful payments: ' . Payment::where('status', PaymentStatus::SUCCESS)->count());
        $this->command->info('- Pending payments: ' . Payment::where('status', PaymentStatus::PENDING)->count());
        $this->command->info('- Failed payments: ' . Payment::where('status', PaymentStatus::FAILED)->count());
    }
}
