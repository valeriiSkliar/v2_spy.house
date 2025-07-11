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
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('creative_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Составной уникальный ключ для предотвращения дублирования
            $table->unique(['user_id', 'creative_id']);

            // Индексы для оптимизации запросов
            $table->index('user_id');
            $table->index('creative_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
