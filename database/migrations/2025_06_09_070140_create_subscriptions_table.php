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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Subscription plan name');
            $table->decimal('amount', 10, 2)->comment('Subscription price');
            $table->decimal('amount_yearly', 10, 2)->nullable()->comment('Subscription price for yearly payment');
            $table->decimal('early_discount', 5, 2)->nullable()->comment('Early bird discount percentage');
            $table->integer('api_request_count')->default(0)->comment('Monthly API request limit');
            $table->integer('search_request_count')->default(0)->comment('Monthly search request limit');
            $table->enum('status', ['active', 'inactive', 'deprecated'])->default('active')->comment('Subscription status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
