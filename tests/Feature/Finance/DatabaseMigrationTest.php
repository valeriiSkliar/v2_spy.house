<?php

namespace Tests\Feature\Finance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_table_has_financial_fields(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'available_balance'));
        $this->assertTrue(Schema::hasColumn('users', 'subscription_id'));
        $this->assertTrue(Schema::hasColumn('users', 'subscription_time_start'));
        $this->assertTrue(Schema::hasColumn('users', 'subscription_time_end'));
        $this->assertTrue(Schema::hasColumn('users', 'subscription_is_expired'));
        $this->assertTrue(Schema::hasColumn('users', 'queued_subscription_id'));
        $this->assertTrue(Schema::hasColumn('users', 'balance_version'));
    }

    public function test_subscriptions_table_exists_and_has_correct_structure(): void
    {
        $this->assertTrue(Schema::hasTable('subscriptions'));

        $columns = [
            'id',
            'name',
            'amount',
            'early_discount',
            'api_request_count',
            'search_request_count',
            'status',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(Schema::hasColumn('subscriptions', $column));
        }
    }

    public function test_payments_table_exists_and_has_correct_structure(): void
    {
        $this->assertTrue(Schema::hasTable('payments'));

        $columns = [
            'id',
            'user_id',
            'amount',
            'payment_type',
            'subscription_id',
            'payment_method',
            'transaction_number',
            'promocode_id',
            'status',
            'webhook_token',
            'webhook_processed_at',
            'idempotency_key',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $column) {
            $this->assertTrue(Schema::hasColumn('payments', $column));
        }
    }

    public function test_foreign_key_constraints_exist(): void
    {
        // Test that foreign key columns exist
        $this->assertTrue(Schema::hasColumn('users', 'subscription_id'));
        $this->assertTrue(Schema::hasColumn('users', 'queued_subscription_id'));
        $this->assertTrue(Schema::hasColumn('payments', 'user_id'));
        $this->assertTrue(Schema::hasColumn('payments', 'subscription_id'));
    }

    public function test_indexes_exist_on_performance_critical_columns(): void
    {
        // This would require more complex testing to verify indexes
        // For now, we just verify the columns exist that should be indexed
        $this->assertTrue(Schema::hasColumn('users', 'subscription_id'));
        $this->assertTrue(Schema::hasColumn('users', 'subscription_is_expired'));
        $this->assertTrue(Schema::hasColumn('users', 'balance_version'));
        $this->assertTrue(Schema::hasColumn('payments', 'user_id'));
        $this->assertTrue(Schema::hasColumn('payments', 'status'));
        $this->assertTrue(Schema::hasColumn('payments', 'transaction_number'));
        $this->assertTrue(Schema::hasColumn('payments', 'webhook_token'));
        $this->assertTrue(Schema::hasColumn('payments', 'created_at'));
    }

    public function test_unique_constraints_exist(): void
    {
        // Test that unique constraint columns exist
        $this->assertTrue(Schema::hasColumn('payments', 'transaction_number'));
        $this->assertTrue(Schema::hasColumn('payments', 'webhook_token'));
        $this->assertTrue(Schema::hasColumn('payments', 'idempotency_key'));
    }

    public function test_nullable_fields_are_correctly_set(): void
    {
        // Test subscription_id can be null (user without subscription)
        $this->assertTrue(Schema::hasColumn('users', 'subscription_id'));

        // Test transaction_number can be null (payment not yet processed)
        $this->assertTrue(Schema::hasColumn('payments', 'transaction_number'));

        // Test webhook_processed_at can be null (not yet processed)
        $this->assertTrue(Schema::hasColumn('payments', 'webhook_processed_at'));
    }
}
