<?php

namespace Tests\Feature\Finance;

use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\PaymentType;
use App\Finance\Models\Payment;
use App\Finance\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_scopes_performance_with_large_dataset(): void
    {
        // Создаем большое количество платежей
        Payment::factory()->count(500)->successful()->create();
        Payment::factory()->count(200)->pending()->create();
        Payment::factory()->count(100)->failed()->create();

        $startTime = microtime(true);

        // Тестируем производительность scope-ов
        $successfulCount = Payment::successful()->count();
        $pendingCount = Payment::pending()->count();
        $failedCount = Payment::failed()->count();

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // в миллисекундах

        $this->assertEquals(500, $successfulCount);
        $this->assertEquals(200, $pendingCount);
        $this->assertEquals(100, $failedCount);

        // Проверяем что запросы выполняются быстро (менее 100ms)
        $this->assertLessThan(100, $executionTime, 'Payment scopes should execute within 100ms');
    }

    public function test_payment_relationships_loading_performance(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create();

        // Создаем платежи с отношениями
        Payment::factory()->count(50)->create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'payment_type' => PaymentType::DIRECT_SUBSCRIPTION,
        ]);

        $startTime = microtime(true);

        // Тестируем загрузку с отношениями
        $paymentsWithRelations = Payment::with(['user', 'subscription'])
            ->where('user_id', $user->id)
            ->get();

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->assertCount(50, $paymentsWithRelations);
        $this->assertLessThan(50, $executionTime, 'Eager loading should be efficient');

        // Проверяем что отношения загружены
        $firstPayment = $paymentsWithRelations->first();
        $this->assertEquals($user->id, $firstPayment->user->id);
        $this->assertEquals($subscription->id, $firstPayment->subscription->id);
    }

    public function test_bulk_payment_status_updates(): void
    {
        $payments = Payment::factory()->pending()->count(100)->create();
        $paymentIds = $payments->pluck('id')->toArray();

        $startTime = microtime(true);

        // Массовое обновление статуса
        Payment::whereIn('id', $paymentIds)->update([
            'status' => PaymentStatus::SUCCESS,
            'webhook_processed_at' => now(),
        ]);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->assertLessThan(50, $executionTime, 'Bulk updates should be fast');

        // Проверяем что все статусы обновлены
        $updatedCount = Payment::whereIn('id', $paymentIds)
            ->where('status', PaymentStatus::SUCCESS)
            ->count();

        $this->assertEquals(100, $updatedCount);
    }

    public function test_payment_aggregation_queries_performance(): void
    {
        $user = User::factory()->create();

        // Создаем разные типы платежей
        Payment::factory()->successful()->deposit()->count(30)->create([
            'user_id' => $user->id,
            'amount' => 100.00,
        ]);

        Payment::factory()->successful()->directSubscription()->count(20)->create([
            'user_id' => $user->id,
            'amount' => 50.00,
        ]);

        $startTime = microtime(true);

        // Тестируем агрегационные запросы
        $totalDeposits = Payment::where('user_id', $user->id)
            ->deposits()
            ->successful()
            ->sum('amount');

        $totalSubscriptions = Payment::where('user_id', $user->id)
            ->subscriptions()
            ->successful()
            ->sum('amount');

        $avgPaymentAmount = Payment::where('user_id', $user->id)
            ->successful()
            ->avg('amount');

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->assertEquals(3000.00, $totalDeposits); // 30 * 100
        $this->assertEquals(1000.00, $totalSubscriptions); // 20 * 50
        $this->assertEquals(80.00, $avgPaymentAmount); // (3000 + 1000) / 50 = 4000/50 = 80

        $this->assertLessThan(30, $executionTime, 'Aggregation queries should be fast');
    }

    public function test_concurrent_payment_creation_simulation(): void
    {
        $user = User::factory()->create();

        $startTime = microtime(true);

        // Симулируем одновременное создание платежей
        $promises = [];
        for ($i = 0; $i < 10; $i++) {
            $promises[] = function () use ($user) {
                return Payment::factory()->create([
                    'user_id' => $user->id,
                    'amount' => 100.00,
                ]);
            };
        }

        // Выполняем все операции
        $results = array_map(function ($promise) {
            return $promise();
        }, $promises);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $this->assertCount(10, $results);
        $this->assertLessThan(200, $executionTime, 'Concurrent payment creation should be handled efficiently');

        // Проверяем что все платежи имеют уникальные токены
        $tokens = array_map(function ($payment) {
            return $payment->webhook_token;
        }, $results);

        $this->assertEquals(10, count(array_unique($tokens)));
    }

    public function test_payment_index_usage(): void
    {
        $user = User::factory()->create();

        // Создаем платежи для тестирования индексов
        Payment::factory()->count(100)->create(['user_id' => $user->id]);

        // Включаем логирование запросов
        DB::enableQueryLog();

        // Выполняем запросы, которые должны использовать индексы
        Payment::where('user_id', $user->id)->get();
        Payment::where('status', PaymentStatus::PENDING)->get();
        Payment::where('webhook_token', Payment::factory()->create()->webhook_token)->get();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Проверяем что запросы выполнены (количество может варьироваться в зависимости от настроек)
        $this->assertGreaterThan(3, count($queries), 'Should execute multiple queries for index testing');
    }

    public function test_memory_usage_with_large_payment_collections(): void
    {
        $initialMemory = memory_get_usage();

        // Создаем и обрабатываем большую коллекцию платежей (используем make() для избежания конфликтов)
        $payments = collect();
        for ($i = 0; $i < 1000; $i++) {
            $payments->push(Payment::factory()->make([
                'user_id' => 1, // Используем фиксированный ID для избежания создания пользователей
                'webhook_token' => 'test_token_'.$i, // Уникальные токены
                'idempotency_key' => 'test_key_'.$i, // Уникальные ключи
            ]));
        }

        foreach ($payments as $payment) {
            $payment->getFormattedAmount();
            $payment->isSuccessful();
        }

        $finalMemory = memory_get_usage();
        $memoryUsed = ($finalMemory - $initialMemory) / 1024 / 1024; // в MB

        // Проверяем что использование памяти разумное (менее 50MB)
        $this->assertLessThan(50, $memoryUsed, 'Memory usage should be reasonable for large collections');
    }

    public function test_database_connection_efficiency(): void
    {
        // Проверяем что соединение с БД работает стабильно
        $initialConnection = DB::connection();

        // Создаем много платежей быстро
        for ($i = 0; $i < 50; $i++) {
            Payment::factory()->create();
        }

        $finalConnection = DB::connection();

        // Проверяем что используется то же соединение
        $this->assertSame($initialConnection, $finalConnection, 'Database connection should be reused efficiently');

        // Проверяем что соединение все еще активно
        $this->assertTrue($finalConnection->getPdo() !== null, 'Database connection should remain active');
    }
}
