<?php

use App\Enums\Frontend\ServiceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('description');
            $table->string('logo')->nullable();
            $table->string('url');
            $table->string('redirect_url');
            $table->enum('status', [ServiceStatus::values()])->default(ServiceStatus::ACTIVE->value);
            $table->foreignId('category_id')->constrained('service_categories')->cascadeOnDelete();
            $table->integer('views')->default(0);
            $table->integer('transitions')->default(0);
            $table->integer('reviews_count')->default(0);
            $table->float('rating', 2, 1)->default(0);
            $table->string('code')->nullable()->default('');
            $table->json('code_description')->nullable();
            $table->timestamp('code_valid_from')->nullable();
            $table->timestamp('code_valid_until')->nullable();
            $table->boolean('is_active_code')->default(true);
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('pinned_until')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
