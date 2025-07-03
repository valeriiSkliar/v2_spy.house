<?php

use App\Enums\Frontend\AdvertisingFormat;
use App\Enums\Frontend\AdvertisingStatus;
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
        Schema::create('creatives', function (Blueprint $table) {
            $table->id();

            // Enums
            $table->enum('format', AdvertisingFormat::values());
            $table->enum('status', AdvertisingStatus::values())
                ->default(AdvertisingStatus::Active->value);

            // ISO связи
            $table->foreignId('country_id')
                ->nullable()
                ->constrained('iso_entities')
                ->onDelete('set null')
                ->comment('Ссылка на страну из ISO справочника');

            $table->foreignId('language_id')
                ->nullable()
                ->constrained('iso_entities')
                ->onDelete('set null')
                ->comment('Ссылка на язык из ISO справочника');

            $table->timestamps();

            // Индексы для производительности
            $table->index(['country_id', 'language_id'], 'idx_country_language');
            $table->index(['status', 'country_id'], 'idx_status_country');
            $table->index(['format', 'language_id'], 'idx_format_language');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creatives');
    }
};
