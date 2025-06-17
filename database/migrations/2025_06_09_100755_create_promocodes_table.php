<?php

use App\Enums\Finance\PromocodeStatus;
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
        Schema::create('promocodes', function (Blueprint $table) {
            $table->id();
            $table->string('promocode', 50)->unique()->comment('Unique promocode string');
            $table->decimal('discount', 5, 2)->comment('Discount percentage (0.00-100.00)');
            $table->enum('status', PromocodeStatus::values())->default(PromocodeStatus::ACTIVE->value)->comment('Promocode status');
            $table->timestamp('date_start')->nullable()->comment('Start date for promocode validity');
            $table->timestamp('date_end')->nullable()->comment('End date for promocode validity');
            $table->integer('count_activation')->default(0)->comment('Total number of activations');
            $table->integer('max_per_user')->default(1)->comment('Maximum uses per user');
            $table->foreignId('created_by_user_id')->constrained('users')->onDelete('cascade')->comment('User who created this promocode');
            $table->timestamps();

            // Indexes for performance optimization
            $table->index('promocode', 'promocodes_promocode_idx');
            $table->index(['status', 'date_start', 'date_end'], 'promocodes_validity_idx');
            $table->index('created_by_user_id', 'promocodes_creator_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promocodes');
    }
};
