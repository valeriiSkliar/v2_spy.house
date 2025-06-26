<?php

use App\Enums\Frontend\BrowserType;
use App\Enums\Frontend\DeviceType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('browsers', function (Blueprint $table) {
            $table->id();

            // Основная информация о браузере (из Browscap)
            $table->string('browser', 100)
                ->comment('Название браузера (Chrome, Firefox, Safari и т.д.)');
            $table->enum('browser_type', BrowserType::values())
                ->default(BrowserType::BROWSER->value)
                ->comment('Тип браузера');

            // Информация об устройстве
            $table->enum('device_type', DeviceType::values())
                ->default(DeviceType::DESKTOP->value)
                ->comment('Тип устройства');

            $table->boolean('ismobiledevice')
                ->default(false)
                ->comment('Является ли мобильным устройством');
            $table->boolean('istablet')
                ->default(false)
                ->comment('Является ли планшетом');

            // User-Agent информация (из Intoli)
            $table->text('user_agent')
                ->comment('Полная строка User-Agent');
            $table->boolean('is_for_filter')
                ->default(true)
                ->comment('Используется ли для фильтрации запросов');

            // Дополнительные полезные поля
            $table->string('browser_version', 50)
                ->nullable()
                ->comment('Версия браузера');
            $table->string('platform', 50)
                ->nullable()
                ->comment('Операционная система');
            $table->boolean('is_active')
                ->default(true)
                ->comment('Активность записи');

            // Метаданные
            $table->timestamps();

            // Индексы для оптимизации запросов
            $table->index(['browser', 'browser_type'], 'idx_browser_type');
            $table->index(['device_type', 'ismobiledevice'], 'idx_device_mobile');
            $table->index(['is_for_filter', 'is_active'], 'idx_filter_active');
            $table->index('browser_type', 'idx_browser_type_only');
        });

        // Добавляем индекс для user_agent с ограничением длины после создания таблицы
        // Используем условную логику для разных СУБД
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE browsers ADD INDEX idx_user_agent_prefix (user_agent(255))');
        } else {
            // Для SQLite создаем обычный индекс без ограничения длины
            DB::statement('CREATE INDEX idx_user_agent_prefix ON browsers (user_agent)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('browsers');
    }
};
