<?php

use App\Enums\Finance\PaymentMethod;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\PaymentType;
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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 15, 2)->comment('Payment amount');
            $table->enum('payment_type', PaymentType::values())->comment('Type of payment');
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->onDelete('set null');
            $table->enum('payment_method', PaymentMethod::values())->comment('Payment method used');
            $table->string('transaction_number')->unique()->nullable()->comment('Unique transaction ID from payment system');
            $table->unsignedBigInteger('promocode_id')->nullable()->comment('Reference to promocode (will be constrained when promocodes table is created)');
            $table->enum('status', PaymentStatus::values())->default(PaymentStatus::PENDING->value)->comment('Payment status');
            $table->string('webhook_token', 64)->unique()->nullable()->comment('Token for secure webhook processing');
            $table->timestamp('webhook_processed_at')->nullable()->comment('When webhook was processed');
            $table->string('idempotency_key')->unique()->nullable()->comment('Key to prevent duplicate payments');
            $table->foreignId('parent_payment_id')->nullable()->constrained('payments')->onDelete('set null')->comment('Reference to parent payment for refunds');
            $table->timestamps();

            // Indexes for performance optimization
            $table->index(['user_id', 'status'], 'payments_user_status_idx');
            $table->index(['subscription_id', 'status'], 'payments_subscription_status_idx');
            $table->index('transaction_number', 'payments_transaction_number_idx');
            $table->index('webhook_token', 'payments_webhook_token_idx');
            $table->index('created_at', 'payments_created_at_idx');

            // Note: Check constraint for positive amount (amount > 0) would be added here
            // but Blueprint::check() is not available in this Laravel version
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
