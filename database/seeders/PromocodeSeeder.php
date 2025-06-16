<?php

namespace Database\Seeders;

use App\Enums\Finance\PromocodeStatus;
use App\Finance\Models\Promocode;
use App\Models\User;
use Illuminate\Database\Seeder;

class PromocodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создаем админского пользователя для промокодов, если его нет
        $admin = User::first() ?? User::factory()->create([
            'email' => 'admin@example.com',
            'name' => 'Admin',
        ]);

        // Активные промокоды с разными процентами скидок
        Promocode::factory()->create([
            'promocode' => 'WELCOME10',
            'discount' => 10.00,
            'status' => PromocodeStatus::ACTIVE,
            'date_start' => now()->subDays(7),
            'date_end' => now()->addMonth(),
            'max_per_user' => 1,
            'count_activation' => 0,
            'created_by_user_id' => $admin->id,
        ]);

        Promocode::factory()->create([
            'promocode' => 'SAVE20',
            'discount' => 20.00,
            'status' => PromocodeStatus::ACTIVE,
            'date_start' => now()->subDays(3),
            'date_end' => now()->addWeeks(2),
            'max_per_user' => 2,
            'count_activation' => 5,
            'created_by_user_id' => $admin->id,
        ]);

        Promocode::factory()->create([
            'promocode' => 'BIGDEAL50',
            'discount' => 50.00,
            'status' => PromocodeStatus::ACTIVE,
            'date_start' => now(),
            'date_end' => now()->addDays(7),
            'max_per_user' => 1,
            'count_activation' => 2,
            'created_by_user_id' => $admin->id,
        ]);

        // Промокод без временных ограничений
        Promocode::factory()->create([
            'promocode' => 'FOREVER15',
            'discount' => 15.00,
            'status' => PromocodeStatus::ACTIVE,
            'date_start' => null,
            'date_end' => null,
            'max_per_user' => 3,
            'count_activation' => 12,
            'created_by_user_id' => $admin->id,
        ]);

        // Неактивный промокод
        Promocode::factory()->create([
            'promocode' => 'INACTIVE25',
            'discount' => 25.00,
            'status' => PromocodeStatus::INACTIVE,
            'date_start' => now()->subMonth(),
            'date_end' => now()->addMonth(),
            'max_per_user' => 1,
            'count_activation' => 0,
            'created_by_user_id' => $admin->id,
        ]);

        // Истекший промокод
        Promocode::factory()->create([
            'promocode' => 'EXPIRED30',
            'discount' => 30.00,
            'status' => PromocodeStatus::ACTIVE,
            'date_start' => now()->subMonths(2),
            'date_end' => now()->subWeek(),
            'max_per_user' => 1,
            'count_activation' => 8,
            'created_by_user_id' => $admin->id,
        ]);

        // Промокод с высоким лимитом использования
        Promocode::factory()->create([
            'promocode' => 'UNLIMITED5',
            'discount' => 5.00,
            'status' => PromocodeStatus::ACTIVE,
            'date_start' => now()->subDays(30),
            'date_end' => now()->addDays(30),
            'max_per_user' => 10,
            'count_activation' => 35,
            'created_by_user_id' => $admin->id,
        ]);

        // Промокод для VIP клиентов
        Promocode::factory()->create([
            'promocode' => 'VIP40',
            'discount' => 40.00,
            'status' => PromocodeStatus::ACTIVE,
            'date_start' => now()->subDays(5),
            'date_end' => now()->addDays(25),
            'max_per_user' => 1,
            'count_activation' => 3,
            'created_by_user_id' => $admin->id,
        ]);

        // Дополнительно создаем несколько случайных промокодов
        Promocode::factory()->count(10)->create([
            'created_by_user_id' => $admin->id,
            'status' => PromocodeStatus::ACTIVE,
        ]);

        // Несколько неактивных промокодов
        Promocode::factory()->count(3)->inactive()->create([
            'created_by_user_id' => $admin->id,
        ]);

        // Несколько истекших промокодов
        Promocode::factory()->count(2)->expired()->create([
            'created_by_user_id' => $admin->id,
        ]);

        $this->command->info('Created '.Promocode::count().' promocodes');
    }
}
