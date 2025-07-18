<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Добавляем индекс для idempotency_key для быстрого поиска дубликатов
            $table->index('idempotency_key', 'payments_idempotency_key_index');
            
            // Составной индекс для поиска недавних платежей
            $table->index(['user_id', 'subscription_id', 'payment_method', 'created_at'], 'payments_recent_search_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_idempotency_key_index');
            $table->dropIndex('payments_recent_search_index');
        });
    }
};
