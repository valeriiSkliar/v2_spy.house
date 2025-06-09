<?php

namespace Tests\Feature\Finance;

use App\Enums\Finance\PaymentMethod;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\PaymentType;
use App\Finance\Models\Payment;
use App\Finance\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BalanceOperationsTest extends TestCase
{
    use RefreshDatabase;

    // === OPTIMISTIC LOCKING TESTS ===

    public function test_optimistic_locking_prevents_concurrent_balance_modifications(): void
    {
        $user = User::factory()->create([
            'available_balance' => 100.00,
            'balance_version' => 1,
        ]);

        // Симулируем две одновременные операции
        $user1 = User::find($user->id);
        $user2 = User::find($user->id);

        // Первая операция - списание 50
        DB::transaction(function () use ($user1) {
            $updatedRows = DB::table('users')
                ->where('id', $user1->id)
                ->where('balance_version', $user1->balance_version)
                ->update([
                    'available_balance' => $user1->available_balance - 50.00,
                    'balance_version' => $user1->balance_version + 1,
                ]);

            if ($updatedRows === 0) {
                throw new \Exception('Optimistic locking conflict');
            }
        });

        // Вторая операция пытается списать 60 с устаревшей версией
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Optimistic locking conflict');

        DB::transaction(function () use ($user2) {
            $updatedRows = DB::table('users')
                ->where('id', $user2->id)
                ->where('balance_version', $user2->balance_version) // Устаревшая версия
                ->update([
                    'available_balance' => $user2->available_balance - 60.00,
                    'balance_version' => $user2->balance_version + 1,
                ]);

            if ($updatedRows === 0) {
                throw new \Exception('Optimistic locking conflict');
            }
        });
    }

    public function test_successful_balance_update_with_version_increment(): void
    {
        $user = User::factory()->create([
            'available_balance' => 200.00,
            'balance_version' => 5,
        ]);

        $originalVersion = $user->balance_version;

        // Правильное обновление с проверкой версии
        DB::transaction(function () use ($user) {
            $updatedRows = DB::table('users')
                ->where('id', $user->id)
                ->where('balance_version', $user->balance_version)
                ->update([
                    'available_balance' => $user->available_balance + 100.00,
                    'balance_version' => $user->balance_version + 1,
                ]);

            if ($updatedRows === 0) {
                throw new \Exception('Optimistic locking conflict');
            }
        });

        $user->refresh();
        $this->assertEquals(300.00, $user->available_balance);
        $this->assertEquals($originalVersion + 1, $user->balance_version);
    }

    public function test_balance_audit_trail_creation(): void
    {
        $user = User::factory()->create([
            'available_balance' => 150.00,
            'balance_version' => 1,
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'amount' => 75.00,
            'payment_type' => PaymentType::DEPOSIT,
            'payment_method' => PaymentMethod::USDT,
        ]);

        // Создаем запись в аудите баланса
        DB::table('balance_audit')->insert([
            'user_id' => $user->id,
            'amount' => 75.00,
            'operation_type' => 'DEPOSIT',
            'payment_id' => $payment->id,
            'balance_before' => 150.00,
            'balance_after' => 225.00,
            'transaction_hash' => hash('sha256', $user->id . $payment->id . now()),
            'created_at' => now(),
        ]);

        $this->assertDatabaseHas('balance_audit', [
            'user_id' => $user->id,
            'amount' => 75.00,
            'operation_type' => 'DEPOSIT',
            'payment_id' => $payment->id,
            'balance_before' => 150.00,
            'balance_after' => 225.00,
        ]);
    }

    // === BALANCE OPERATIONS TESTS ===

    public function test_successful_deposit_operation(): void
    {
        $user = User::factory()->create([
            'available_balance' => 100.00,
            'balance_version' => 1,
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'amount' => 250.00,
            'payment_type' => PaymentType::DEPOSIT,
            'payment_method' => PaymentMethod::USDT,
            'status' => PaymentStatus::SUCCESS,
        ]);

        $this->processDepositOperation($user, $payment);

        $user->refresh();
        $this->assertEquals(350.00, $user->available_balance);
        $this->assertEquals(2, $user->balance_version);

        // Проверяем аудит
        $this->assertDatabaseHas('balance_audit', [
            'user_id' => $user->id,
            'amount' => 250.00,
            'operation_type' => 'DEPOSIT',
            'balance_before' => 100.00,
            'balance_after' => 350.00,
        ]);
    }

    public function test_subscription_purchase_from_balance(): void
    {
        $user = User::factory()->create([
            'available_balance' => 500.00,
            'balance_version' => 1,
        ]);

        $subscription = Subscription::factory()->create([
            'amount' => 300.00,
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'amount' => 300.00,
            'payment_type' => PaymentType::DIRECT_SUBSCRIPTION,
            'payment_method' => PaymentMethod::USER_BALANCE,
            'subscription_id' => $subscription->id,
            'status' => PaymentStatus::PENDING,
        ]);

        $this->processSubscriptionPurchaseFromBalance($user, $payment, $subscription);

        $user->refresh();
        $payment->refresh();

        $this->assertEquals(200.00, $user->available_balance);
        $this->assertEquals(2, $user->balance_version);
        $this->assertEquals(PaymentStatus::SUCCESS, $payment->status);
        $this->assertEquals($subscription->id, $user->subscription_id);

        // Проверяем аудит
        $this->assertDatabaseHas('balance_audit', [
            'user_id' => $user->id,
            'amount' => -300.00,
            'operation_type' => 'SUBSCRIPTION_PAYMENT',
            'balance_before' => 500.00,
            'balance_after' => 200.00,
        ]);
    }

    public function test_insufficient_balance_prevents_subscription_purchase(): void
    {
        $user = User::factory()->create([
            'available_balance' => 100.00,
            'balance_version' => 1,
        ]);

        $subscription = Subscription::factory()->create([
            'amount' => 300.00,
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'amount' => 300.00,
            'payment_type' => PaymentType::DIRECT_SUBSCRIPTION,
            'payment_method' => PaymentMethod::USER_BALANCE,
            'subscription_id' => $subscription->id,
            'status' => PaymentStatus::PENDING,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient balance');

        $this->processSubscriptionPurchaseFromBalance($user, $payment, $subscription);
    }

    // === CONCURRENT OPERATIONS TESTS ===

    public function test_concurrent_balance_operations_with_locking(): void
    {
        $user = User::factory()->create([
            'available_balance' => 1000.00,
            'balance_version' => 1,
        ]);

        $results = [];
        $exceptions = [];

        // Симулируем 10 одновременных операций списания по 100
        for ($i = 0; $i < 10; $i++) {
            try {
                DB::transaction(function () use ($user, $i, &$results) {
                    // SELECT FOR UPDATE для блокировки
                    $currentUser = DB::table('users')
                        ->where('id', $user->id)
                        ->lockForUpdate()
                        ->first();

                    if ($currentUser->available_balance >= 100.00) {
                        $newBalance = $currentUser->available_balance - 100.00;
                        $newVersion = $currentUser->balance_version + 1;

                        DB::table('users')
                            ->where('id', $user->id)
                            ->where('balance_version', $currentUser->balance_version)
                            ->update([
                                'available_balance' => $newBalance,
                                'balance_version' => $newVersion,
                            ]);

                        $results[] = "Operation {$i} successful";
                    } else {
                        throw new \Exception("Insufficient balance for operation {$i}");
                    }
                });
            } catch (\Exception $e) {
                $exceptions[] = $e->getMessage();
            }
        }

        $user->refresh();

        // Должно быть обработано максимум 10 операций (1000 / 100 = 10)
        $this->assertEquals(0.00, $user->available_balance);
        $this->assertEquals(11, $user->balance_version); // 1 + 10 операций
        $this->assertCount(10, $results);
        $this->assertEmpty($exceptions);
    }

    public function test_rollback_on_failed_subscription_activation(): void
    {
        $user = User::factory()->create([
            'available_balance' => 500.00,
            'balance_version' => 1,
        ]);

        $subscription = Subscription::factory()->create([
            'amount' => 300.00,
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'amount' => 300.00,
            'payment_type' => PaymentType::DIRECT_SUBSCRIPTION,
            'payment_method' => PaymentMethod::USER_BALANCE,
            'subscription_id' => $subscription->id,
            'status' => PaymentStatus::PENDING,
        ]);

        try {
            DB::transaction(function () use ($user, $payment, $subscription) {
                // 1. Списываем средства
                $this->deductBalance($user, 300.00, 'SUBSCRIPTION_PAYMENT', $payment->id);

                // 2. Симулируем ошибку при активации подписки перед обновлением платежа
                throw new \Exception('Subscription activation failed');

                // 3. Обновление платежа и активация подписки (не выполнится из-за исключения)
                $payment->update(['status' => PaymentStatus::SUCCESS]);
                $user->update([
                    'subscription_id' => $subscription->id,
                    'subscription_time_start' => now(),
                    'subscription_time_end' => now()->addMonth(),
                ]);
            });
        } catch (\Exception $e) {
            // Транзакция должна откатиться
        }

        $user->refresh();
        $payment->refresh();

        // Баланс и платеж должны остаться неизменными
        $this->assertEquals(500.00, $user->available_balance);
        $this->assertEquals(1, $user->balance_version);
        $this->assertEquals(PaymentStatus::PENDING, $payment->status);
        $this->assertNull($user->subscription_id);

        // Аудит не должен содержать записей об операции
        $this->assertDatabaseMissing('balance_audit', [
            'user_id' => $user->id,
            'payment_id' => $payment->id,
        ]);
    }

    // === LARGE AMOUNT OPERATIONS TESTS ===

    public function test_large_amount_deposit_precision(): void
    {
        $user = User::factory()->create([
            'available_balance' => 0.00,
            'balance_version' => 1,
        ]);

        $largeAmount = 999999999.99;

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'amount' => $largeAmount,
            'payment_type' => PaymentType::DEPOSIT,
            'payment_method' => PaymentMethod::USDT,
        ]);

        $this->processDepositOperation($user, $payment);

        $user->refresh();
        $this->assertEquals($largeAmount, $user->available_balance);
    }

    public function test_decimal_precision_in_balance_operations(): void
    {
        $user = User::factory()->create([
            'available_balance' => 100.50,
            'balance_version' => 1,
        ]);

        $preciseAmount = 33.33;

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'amount' => $preciseAmount,
            'payment_type' => PaymentType::DEPOSIT,
            'payment_method' => PaymentMethod::USDT,
        ]);

        $this->processDepositOperation($user, $payment);

        $user->refresh();
        $this->assertEquals(133.83, $user->available_balance);

        // Проверяем точность в аудите
        $this->assertDatabaseHas('balance_audit', [
            'user_id' => $user->id,
            'amount' => $preciseAmount,
            'balance_after' => 133.83,
        ]);
    }

    // === AUDIT TRAIL INTEGRITY TESTS ===

    public function test_audit_trail_transaction_hash_uniqueness(): void
    {
        $user = User::factory()->create([
            'available_balance' => 100.00,
        ]);

        $payment1 = Payment::factory()->create(['user_id' => $user->id]);
        $payment2 = Payment::factory()->create(['user_id' => $user->id]);

        $hash1 = $this->createAuditRecord($user, $payment1, 50.00, 'DEPOSIT');
        $hash2 = $this->createAuditRecord($user, $payment2, 75.00, 'DEPOSIT');

        $this->assertNotEquals($hash1, $hash2);

        // Проверяем уникальность в БД
        $hashes = DB::table('balance_audit')
            ->where('user_id', $user->id)
            ->pluck('transaction_hash')
            ->toArray();

        $this->assertEquals(count($hashes), count(array_unique($hashes)));
    }

    public function test_audit_trail_completeness(): void
    {
        $user = User::factory()->create([
            'available_balance' => 200.00,
        ]);

        // Серия операций
        $operations = [
            ['amount' => 100.00, 'type' => 'DEPOSIT'],
            ['amount' => -50.00, 'type' => 'SUBSCRIPTION_PAYMENT'],
            ['amount' => 25.00, 'type' => 'REFUND'],
            ['amount' => -75.00, 'type' => 'WITHDRAWAL'],
        ];

        $expectedBalance = 200.00;

        foreach ($operations as $operation) {
            $payment = Payment::factory()->create(['user_id' => $user->id]);
            $balanceBefore = $expectedBalance;
            $expectedBalance += $operation['amount'];

            $this->createAuditRecord(
                $user,
                $payment,
                $operation['amount'],
                $operation['type'],
                $balanceBefore,
                $expectedBalance
            );
        }

        // Проверяем целостность цепочки операций
        $auditRecords = DB::table('balance_audit')
            ->where('user_id', $user->id)
            ->orderBy('created_at')
            ->get();

        $this->assertCount(4, $auditRecords);

        // Проверяем что balance_before каждой операции равен balance_after предыдущей
        for ($i = 1; $i < count($auditRecords); $i++) {
            $this->assertEquals(
                $auditRecords[$i - 1]->balance_after,
                $auditRecords[$i]->balance_before
            );
        }
    }

    // === PERFORMANCE TESTS ===

    public function test_balance_operations_performance(): void
    {
        $user = User::factory()->create([
            'available_balance' => 10000.00,
            'balance_version' => 1,
        ]);

        $startTime = microtime(true);

        // Выполняем 100 операций с балансом
        for ($i = 0; $i < 100; $i++) {
            $payment = Payment::factory()->make([
                'user_id' => $user->id,
                'amount' => 10.00,
            ]);

            DB::transaction(function () use ($user, $payment) {
                $this->deductBalance($user, 10.00, 'TEST_OPERATION', $payment->id ?? null);
            });
        }

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $user->refresh();
        $this->assertEquals(9000.00, $user->available_balance);
        $this->assertEquals(101, $user->balance_version);

        // 100 операций должны выполняться менее чем за 2 секунды
        $this->assertLessThan(2000, $executionTime);
    }

    // === HELPER METHODS ===

    private function processDepositOperation(User $user, Payment $payment): void
    {
        DB::transaction(function () use ($user, $payment) {
            $balanceBefore = $user->available_balance;
            $newBalance = $balanceBefore + $payment->amount;

            // Обновляем баланс с optimistic locking
            $updatedRows = DB::table('users')
                ->where('id', $user->id)
                ->where('balance_version', $user->balance_version)
                ->update([
                    'available_balance' => $newBalance,
                    'balance_version' => $user->balance_version + 1,
                ]);

            if ($updatedRows === 0) {
                throw new \Exception('Optimistic locking conflict');
            }

            // Создаем аудит запись
            $this->createAuditRecord(
                $user,
                $payment,
                $payment->amount,
                'DEPOSIT',
                $balanceBefore,
                $newBalance
            );

            // Обновляем статус платежа
            $payment->update(['status' => PaymentStatus::SUCCESS]);
        });
    }

    private function processSubscriptionPurchaseFromBalance(User $user, Payment $payment, Subscription $subscription): void
    {
        DB::transaction(function () use ($user, $payment, $subscription) {
            if ($user->available_balance < $subscription->amount) {
                throw new \Exception('Insufficient balance');
            }

            $this->deductBalance($user, $subscription->amount, 'SUBSCRIPTION_PAYMENT', $payment->id);

            $payment->update(['status' => PaymentStatus::SUCCESS]);

            $user->update([
                'subscription_id' => $subscription->id,
                'subscription_time_start' => now(),
                'subscription_time_end' => now()->addMonth(),
                'subscription_is_expired' => false,
            ]);
        });
    }

    private function deductBalance(User $user, float $amount, string $operationType, ?int $paymentId): void
    {
        $balanceBefore = $user->available_balance;
        $newBalance = $balanceBefore - $amount;

        if ($newBalance < 0) {
            throw new \Exception('Insufficient balance');
        }

        $updatedRows = DB::table('users')
            ->where('id', $user->id)
            ->where('balance_version', $user->balance_version)
            ->update([
                'available_balance' => $newBalance,
                'balance_version' => $user->balance_version + 1,
            ]);

        if ($updatedRows === 0) {
            throw new \Exception('Optimistic locking conflict');
        }

        $this->createAuditRecord(
            $user,
            null,
            -$amount,
            $operationType,
            $balanceBefore,
            $newBalance,
            $paymentId
        );

        // Обновляем объект в памяти
        $user->available_balance = $newBalance;
        $user->balance_version += 1;
    }

    private function createAuditRecord(
        User $user,
        ?Payment $payment,
        float $amount,
        string $operationType,
        ?float $balanceBefore = null,
        ?float $balanceAfter = null,
        ?int $paymentId = null
    ): string {
        $balanceBefore = $balanceBefore ?? $user->available_balance;
        $balanceAfter = $balanceAfter ?? ($balanceBefore + $amount);
        $paymentId = $paymentId ?? $payment?->id;

        $transactionHash = hash('sha256', implode('|', [
            $user->id,
            $amount,
            $operationType,
            $balanceBefore,
            $balanceAfter,
            $paymentId,
            now()->timestamp,
            mt_rand()
        ]));

        DB::table('balance_audit')->insert([
            'user_id' => $user->id,
            'amount' => $amount,
            'operation_type' => $operationType,
            'payment_id' => $paymentId,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'transaction_hash' => $transactionHash,
            'created_at' => now(),
        ]);

        return $transactionHash;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Создаем таблицу для аудита баланса
        if (!Schema::hasTable('balance_audit')) {
            Schema::create('balance_audit', function ($table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->decimal('amount', 15, 2);
                $table->string('operation_type', 50);
                $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null');
                $table->decimal('balance_before', 15, 2);
                $table->decimal('balance_after', 15, 2);
                $table->string('transaction_hash', 64)->unique();
                $table->timestamp('created_at');

                $table->index(['user_id', 'created_at']);
                $table->index('transaction_hash');
            });
        }
    }
}
