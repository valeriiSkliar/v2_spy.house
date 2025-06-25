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
        // Создание таблицы ISO сущностей (страны и языки)
        Schema::create('iso_entities', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['country', 'language'])
                ->comment('Тип сущности: страна или язык');
            $table->char('iso_code_2', 2)
                ->nullable()
                ->comment('ISO код (2 символа)');
            $table->char('iso_code_3', 3)
                ->comment('ISO код (3 символа)');
            $table->char('numeric_code', 3)
                ->nullable()
                ->comment('Числовой ISO код (только для стран)');
            $table->string('name', 100)
                ->comment('Название на английском языке');
            $table->boolean('is_active')
                ->default(true)
                ->comment('Активность записи');
            $table->timestamps();

            // Индексы
            $table->unique(['type', 'iso_code_3'], 'unique_type_iso3');
            $table->index(['type', 'iso_code_2'], 'idx_type_iso2');
            $table->index(['type', 'is_active'], 'idx_type_active');
        });

        // Создание таблицы переводов
        Schema::create('iso_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')
                ->constrained('iso_entities')
                ->onDelete('cascade')
                ->comment('Ссылка на сущность');
            $table->string('language_code', 5)
                ->comment('Код языка перевода (en, ru, de и т.д.)');
            $table->string('translated_name', 200)
                ->comment('Переведенное название');
            $table->timestamps();

            // Индексы и ограничения
            $table->unique(['entity_id', 'language_code'], 'unique_entity_lang');
            $table->index('language_code', 'idx_language_code');
            $table->index('translated_name', 'idx_translated_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iso_translations');
        Schema::dropIfExists('iso_entities');
    }
};
