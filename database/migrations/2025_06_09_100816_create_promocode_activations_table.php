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
        Schema::create('promocode_activations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promocode_id')->constrained('promocodes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null')->comment('Associated payment if applicable');
            $table->string('ip_address', 45)->comment('IP address of activation');
            $table->text('user_agent')->comment('User agent string');
            $table->timestamps();

            // Indexes for performance and constraints
            $table->index(['promocode_id', 'user_id'], 'promocode_activations_promo_user_idx');
            $table->index('payment_id', 'promocode_activations_payment_idx');
            $table->index('ip_address', 'promocode_activations_ip_idx');
            $table->index('created_at', 'promocode_activations_created_idx');

            // Unique constraint to prevent duplicate activations per user per promocode
            $table->unique(['promocode_id', 'user_id', 'payment_id'], 'unique_promocode_user_payment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promocode_activations');
    }
};
