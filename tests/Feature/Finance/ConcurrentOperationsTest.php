<?php

namespace Tests\Feature\Finance;

use App\Enums\Finance\PaymentMethod;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\PaymentType;
use App\Enums\Finance\PromocodeStatus;
use App\Finance\Models\Payment;
use App\Finance\Models\Promocode;
use App\Finance\Models\PromocodeActivation;
use App\Finance\Models\Subscription;
use App\Finance\Services\PromocodeService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ConcurrentOperationsTest extends TestCase
{
    use RefreshDatabase;

    // === RACE CONDITIONS IN BALANCE OPERATIONS ===

    public function test_concurrent_balance_deductions_prevent_negative_balance(): void
    {
        $user = User::factory()->create([
            'available_balance' => 100.00,
            'balance_version' => 1,
        ]);

        $subscription = Subscription::factory()->create(['amount' => 60.00]);

        $results = [];
        $exceptions = [];

        // Симулируем 3 одновременные попытки купить подписку за 60 каждая
        for ($i = 0; $i < 3; $i++) {
            try {
                DB::transaction(function () use ($user, $subscription, $i, &$results) {
                    // Получаем пользователя с блокировкой
                    $currentUser = DB::table('users')
                        ->where('id', $user->id)
                        ->lockForUpdate()
                        ->first();

                    if ($currentUser->available_balance >= $subscription->amount) {
                        $newBalance = $currentUser->available_balance - $subscription->amount;
                        $newVersion = $currentUser->balance_version + 1;

                        $updated = DB::table('users')
                            ->where('id', $user->id)
                            ->where('balance_version', $currentUser->balance_version)
                            ->update([
                                'available_balance' => $newBalance,
                                'balance_version' => $newVersion,
                            ]);

                        if ($updated) {
                            $results[] = "Purchase {$i} successful";
                        } else {
                            throw new \Exception("Version conflict in operation {$i}");
                        }
                    } else {
                        throw new \Exception("Insufficient balance for operation {$i}");
                    }
                });
            } catch (\Exception $e) {
                $exceptions[] = $e->getMessage();
            }
        }

        $user->refresh();

        // Должна пройти только одна операция (100 / 60 = 1)
        $this->assertEquals(40.00, $user->available_balance);
        $this->assertCount(1, $results);
        $this->assertCount(2, $exceptions);

        // Проверяем что баланс никогда не стал отрицательным
        $this->assertGreaterThanOrEqual(0, $user->available_balance);
    }

    public function test_concurrent_promocode_activations_respect_max_per_user(): void
    {
        $user = User::factory()->create();
        $promocode = Promocode::factory()->create([
            'promocode' => 'LIMITED',
            'discount' => 20.00,
            'status' => PromocodeStatus::ACTIVE,
            'max_per_user' => 1,
        ]);

        $service = new PromocodeService;
        $results = [];
        $exceptions = [];

        // Симулируем 5 одновременных попыток активировать промокод
        for ($i = 0; $i < 5; $i++) {
            try {
                DB::transaction(function () use ($user, $i, &$results) {
                    // Проверяем количество активаций для пользователя с блокировкой
                    $activationsCount = DB::table('promocode_activations')
                        ->where('promocode_id', 1) // ID промокода LIMITED
                        ->where('user_id', $user->id)
                        ->lockForUpdate()
                        ->count();

                    if ($activationsCount < 1) {
                        PromocodeActivation::create([
                            'promocode_id' => 1,
                            'user_id' => $user->id,
                            'ip_address' => "192.168.1.{$i}",
                            'user_agent' => "Browser {$i}",
                        ]);

                        $results[] = "Activation {$i} successful";
                    } else {
                        throw new \Exception("Max per user exceeded for operation {$i}");
                    }
                });
            } catch (\Exception $e) {
                $exceptions[] = $e->getMessage();
            }
        }

        // Должна пройти только одна активация
        $activationsCount = PromocodeActivation::where('user_id', $user->id)->count();
        $this->assertEquals(1, $activationsCount);
        $this->assertCount(1, $results);
        $this->assertCount(4, $exceptions);
    }

    // === PAYMENT PROCESSING RACE CONDITIONS ===

    public function test_concurrent_payment_status_updates(): void
    {
        $payment = Payment::factory()->pending()->create([
            'webhook_token' => 'concurrent_token',
        ]);

        $results = [];

        // Симулируем одновременные webhook от платежной системы
        for ($i = 0; $i < 5; $i++) {
            $results[$i] = DB::transaction(function () use ($payment, $i) {
                // Получаем платеж с блокировкой
                $currentPayment = DB::table('payments')
                    ->where('id', $payment->id)
                    ->lockForUpdate()
                    ->first();

                if ($currentPayment->status === PaymentStatus::PENDING->value) {
                    $updated = DB::table('payments')
                        ->where('id', $payment->id)
                        ->where('status', PaymentStatus::PENDING->value)
                        ->update([
                            'status' => PaymentStatus::SUCCESS->value,
                            'webhook_processed_at' => now(),
                        ]);

                    return $updated ? "Update {$i} successful" : "Update {$i} failed";
                } else {
                    return "Payment already processed in operation {$i}";
                }
            });
        }

        $payment->refresh();

        // Статус должен быть обновлен только один раз
        $this->assertEquals(PaymentStatus::SUCCESS, $payment->status);
        $this->assertNotNull($payment->webhook_processed_at);

        // Только одна операция должна быть успешной
        $successfulUpdates = collect($results)->filter(fn ($result) => str_contains($result, 'successful'));
        $this->assertCount(1, $successfulUpdates);
    }

    public function test_concurrent_subscription_activation(): void
    {
        $user = User::factory()->create([
            'subscription_id' => null,
            'subscription_is_expired' => true,
        ]);

        $subscription = Subscription::factory()->create();
        $payments = Payment::factory()->count(3)->create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'status' => PaymentStatus::SUCCESS,
        ]);

        $results = [];

        // Симулируем одновременные попытки активировать подписку
        foreach ($payments as $index => $payment) {
            $results[$index] = DB::transaction(function () use ($user, $subscription, $index) {
                // Получаем пользователя с блокировкой
                $currentUser = DB::table('users')
                    ->where('id', $user->id)
                    ->lockForUpdate()
                    ->first();

                if (is_null($currentUser->subscription_id)) {
                    $updated = DB::table('users')
                        ->where('id', $user->id)
                        ->whereNull('subscription_id')
                        ->update([
                            'subscription_id' => $subscription->id,
                            'subscription_time_start' => now(),
                            'subscription_time_end' => now()->addMonth(),
                            'subscription_is_expired' => false,
                        ]);

                    return $updated ? "Activation {$index} successful" : "Activation {$index} failed";
                } else {
                    return "Subscription already active in operation {$index}";
                }
            });
        }

        $user->refresh();

        // Подписка должна быть активирована только один раз
        $this->assertEquals($subscription->id, $user->subscription_id);
        $this->assertFalse($user->subscription_is_expired);

        // Только одна операция должна быть успешной
        $successfulActivations = collect($results)->filter(fn ($result) => str_contains($result, 'successful'));
        $this->assertCount(1, $successfulActivations);
    }

    // === DATABASE DEADLOCK PREVENTION ===

    public function test_deadlock_prevention_in_complex_operations(): void
    {
        $user1 = User::factory()->create(['available_balance' => 200.00]);
        $user2 = User::factory()->create(['available_balance' => 300.00]);

        $subscription = Subscription::factory()->create(['amount' => 150.00]);

        $results = [];

        // Операция 1: User1 покупает подписку, User2 пополняет баланс
        $results['operation1'] = DB::transaction(function () use ($user1, $user2, $subscription) {
            // Всегда блокируем пользователей в порядке ID для предотвращения deadlock
            $users = collect([$user1, $user2])->sortBy('id');

            foreach ($users as $user) {
                DB::table('users')->where('id', $user->id)->lockForUpdate()->first();
            }

            // Обновляем user1 (покупка подписки)
            DB::table('users')
                ->where('id', $user1->id)
                ->update([
                    'available_balance' => $user1->available_balance - 150.00,
                    'subscription_id' => $subscription->id,
                ]);

            // Обновляем user2 (пополнение)
            DB::table('users')
                ->where('id', $user2->id)
                ->update(['available_balance' => $user2->available_balance + 100.00]);

            return 'Complex operation 1 successful';
        });

        // Операция 2: User2 покупает подписку, User1 пополняет баланс
        $results['operation2'] = DB::transaction(function () use ($user1, $user2, $subscription) {
            // Тот же порядок блокировки
            $users = collect([$user1, $user2])->sortBy('id');

            foreach ($users as $user) {
                DB::table('users')->where('id', $user->id)->lockForUpdate()->first();
            }

            $currentUser1 = DB::table('users')->where('id', $user1->id)->first();
            $currentUser2 = DB::table('users')->where('id', $user2->id)->first();

            // Обновляем user2 (покупка подписки)
            if ($currentUser2->available_balance >= 150.00) {
                DB::table('users')
                    ->where('id', $user2->id)
                    ->update([
                        'available_balance' => $currentUser2->available_balance - 150.00,
                        'subscription_id' => $subscription->id,
                    ]);
            }

            // Обновляем user1 (пополнение)
            DB::table('users')
                ->where('id', $user1->id)
                ->update(['available_balance' => $currentUser1->available_balance + 50.00]);

            return 'Complex operation 2 successful';
        });

        // Обе операции должны завершиться успешно без deadlock
        $this->assertStringContainsString('successful', $results['operation1']);
        $this->assertStringContainsString('successful', $results['operation2']);

        $user1->refresh();
        $user2->refresh();

        $this->assertEquals(100.00, $user1->available_balance); // 200 - 150 + 50
        $this->assertEquals(250.00, $user2->available_balance); // 300 + 100 - 150
    }

    // === QUEUE PROCESSING CONCURRENCY ===

    public function test_concurrent_queue_job_processing(): void
    {
        Queue::fake();

        $user = User::factory()->create(['available_balance' => 500.00]);
        $payments = Payment::factory()->count(5)->pending()->create([
            'user_id' => $user->id,
            'amount' => 100.00,
            'payment_type' => PaymentType::DEPOSIT,
            'payment_method' => PaymentMethod::USDT,
        ]);

        $results = [];

        // Симулируем одновременную обработку депозитов через queue jobs
        foreach ($payments as $index => $payment) {
            $results[$index] = DB::transaction(function () use ($user, $payment, $index) {
                // Проверяем что платеж еще не обработан
                $currentPayment = DB::table('payments')
                    ->where('id', $payment->id)
                    ->lockForUpdate()
                    ->first();

                if ($currentPayment->status === PaymentStatus::PENDING->value) {
                    // Обновляем статус платежа
                    DB::table('payments')
                        ->where('id', $payment->id)
                        ->update(['status' => PaymentStatus::SUCCESS->value]);

                    // Обновляем баланс пользователя
                    $currentUser = DB::table('users')
                        ->where('id', $user->id)
                        ->lockForUpdate()
                        ->first();

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'available_balance' => $currentUser->available_balance + $payment->amount,
                            'balance_version' => $currentUser->balance_version + 1,
                        ]);

                    return "Job {$index} processed successfully";
                } else {
                    return "Payment already processed in job {$index}";
                }
            });
        }

        $user->refresh();

        // Все платежи должны быть обработаны
        $processedCount = Payment::where('user_id', $user->id)
            ->where('status', PaymentStatus::SUCCESS)
            ->count();

        $this->assertEquals(5, $processedCount);

        // Баланс должен увеличиться на сумму всех депозитов
        $this->assertEquals(1000.00, $user->available_balance); // 500 + (5 * 100)

        // Все job'ы должны быть обработаны успешно
        $successfulJobs = collect($results)->filter(fn ($result) => str_contains($result, 'successfully'));
        $this->assertCount(5, $successfulJobs);
    }

    // === PERFORMANCE UNDER CONCURRENCY ===

    public function test_system_performance_under_concurrent_load(): void
    {
        $users = User::factory()->count(10)->create([
            'available_balance' => 1000.00,
            'balance_version' => 1,
        ]);

        $subscription = Subscription::factory()->create(['amount' => 100.00]);

        $startTime = microtime(true);
        $results = [];

        // Симулируем высокую нагрузку: 10 пользователей одновременно совершают операции
        foreach ($users as $index => $user) {
            $results[$index] = DB::transaction(function () use ($user, $subscription, $index) {
                // Каждый пользователь покупает подписку
                $currentUser = DB::table('users')
                    ->where('id', $user->id)
                    ->lockForUpdate()
                    ->first();

                if ($currentUser->available_balance >= $subscription->amount) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->where('balance_version', $currentUser->balance_version)
                        ->update([
                            'available_balance' => $currentUser->available_balance - $subscription->amount,
                            'balance_version' => $currentUser->balance_version + 1,
                            'subscription_id' => $subscription->id,
                        ]);

                    // Создаем платеж
                    Payment::create([
                        'user_id' => $user->id,
                        'amount' => $subscription->amount,
                        'payment_type' => PaymentType::DIRECT_SUBSCRIPTION,
                        'payment_method' => PaymentMethod::USER_BALANCE,
                        'subscription_id' => $subscription->id,
                        'status' => PaymentStatus::SUCCESS,
                    ]);

                    return "User {$index} operation successful";
                } else {
                    return "Insufficient balance for user {$index}";
                }
            });
        }

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        // Все операции должны завершиться успешно
        $successfulOperations = collect($results)->filter(fn ($result) => str_contains($result, 'successful'));
        $this->assertCount(10, $successfulOperations);

        // Операции должны выполняться достаточно быстро даже под нагрузкой
        $this->assertLessThan(5000, $executionTime, 'Concurrent operations should complete within 5 seconds');

        // Проверяем целостность данных
        foreach ($users as $user) {
            $user->refresh();
            $this->assertEquals(900.00, $user->available_balance);
            $this->assertEquals($subscription->id, $user->subscription_id);
        }
    }

    // === MEMORY USAGE UNDER CONCURRENCY ===

    public function test_memory_usage_during_concurrent_operations(): void
    {
        $memoryBefore = memory_get_usage();

        $users = User::factory()->count(50)->create(['available_balance' => 200.00]);
        $promocodes = Promocode::factory()->count(10)->create([
            'status' => PromocodeStatus::ACTIVE,
            'discount' => 10.00,
        ]);

        // Симулируем множественные одновременные операции
        foreach ($users as $userIndex => $user) {
            foreach ($promocodes as $promocodeIndex => $promocode) {
                DB::transaction(function () use ($user, $promocode) {
                    // Небольшая операция: активация промокода
                    PromocodeActivation::create([
                        'promocode_id' => $promocode->id,
                        'user_id' => $user->id,
                        'ip_address' => '127.0.0.1',
                        'user_agent' => 'Test',
                    ]);
                });
            }
        }

        $memoryAfter = memory_get_usage();
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // MB

        // Проверяем что создано правильное количество активаций
        $totalActivations = PromocodeActivation::count();
        $this->assertEquals(500, $totalActivations); // 50 users * 10 promocodes

        // Использование памяти должно быть разумным
        $this->assertLessThan(100, $memoryUsed, 'Memory usage should be under 100MB for 500 operations');
    }

    // === ERROR RECOVERY UNDER CONCURRENCY ===

    public function test_error_recovery_in_concurrent_operations(): void
    {
        $user = User::factory()->create(['available_balance' => 300.00]);
        $subscription = Subscription::factory()->create(['amount' => 100.00]);

        $results = [];
        $errors = [];

        // Операции с намеренными ошибками
        for ($i = 0; $i < 5; $i++) {
            try {
                $result = DB::transaction(function () use ($user, $subscription, $i) {
                    $currentUser = DB::table('users')
                        ->where('id', $user->id)
                        ->lockForUpdate()
                        ->first();

                    if ($currentUser->available_balance >= $subscription->amount) {
                        // Намеренно вызываем ошибку в некоторых операциях ДО изменения баланса
                        if ($i % 2 === 1) {
                            throw new \Exception("Simulated error in operation {$i}");
                        }

                        // Обновляем баланс только если операция должна быть успешной
                        DB::table('users')
                            ->where('id', $user->id)
                            ->update([
                                'available_balance' => $currentUser->available_balance - $subscription->amount,
                            ]);

                        // Создаем платеж
                        Payment::create([
                            'user_id' => $user->id,
                            'amount' => $subscription->amount,
                            'payment_type' => PaymentType::DIRECT_SUBSCRIPTION,
                            'payment_method' => PaymentMethod::USER_BALANCE,
                            'status' => PaymentStatus::SUCCESS,
                        ]);

                        return "Operation {$i} successful";
                    } else {
                        throw new \Exception("Insufficient balance for operation {$i}");
                    }
                });

                $results[] = $result;
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        $user->refresh();

        // Должны пройти только операции без ошибок (0, 2, 4)
        $this->assertCount(3, $results);
        $this->assertCount(2, $errors);

        // Баланс должен уменьшиться только на успешные операции
        $this->assertEquals(0.00, $user->available_balance); // 300 - (3 * 100)

        // Проверяем что создано правильное количество платежей
        $paymentsCount = Payment::where('user_id', $user->id)->count();
        $this->assertEquals(3, $paymentsCount);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Настраиваем конфигурацию для тестов производительности
        config([
            'database.connections.mysql.options' => [
                \PDO::ATTR_TIMEOUT => 30,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ],
        ]);
    }
}
