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
        Schema::create('ad_sources', function (Blueprint $table) {
            $table->id();
            $table->string('source_name', 50)->unique()->comment('Системное имя источника');
            $table->string('source_display_name', 100)->comment('Отображаемое имя источника');
            $table->string('parser_status')->default('inactive')->comment('Статус парсера');
            $table->json('parser_state')->nullable()->comment('Состояние парсера');
            $table->json('parser_last_error')->nullable()->comment('Детали последней ошибки');
            $table->timestamp('parser_last_error_at')->nullable()->comment('Время последней ошибки');
            $table->integer('parser_last_error_code')->nullable()->comment('Код последней ошибки');
            $table->text('parser_last_error_message')->nullable()->comment('Сообщение последней ошибки');
            $table->text('parser_last_error_trace')->nullable()->comment('Трассировка последней ошибки');
            $table->string('parser_last_error_file')->nullable()->comment('Файл последней ошибки');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_sources');
    }
};
