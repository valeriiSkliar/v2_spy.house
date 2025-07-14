<?php

use App\Enums\Frontend\AdvertisingFormat;
use App\Enums\Frontend\AdvertisingStatus;
use App\Enums\Frontend\OperationSystem;
use App\Enums\Frontend\Platform;
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
            $table->enum('format', AdvertisingFormat::values())->nullable();
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

            // Браузер и операционная система
            $table->foreignId('browser_id')
                ->nullable()
                ->constrained('browsers')
                ->onDelete('set null')
                ->comment('Ссылка на браузер');

            $table->enum('operation_system', OperationSystem::values())
                ->nullable()
                ->comment('Операционная система ');

            // Добавляем связь с рекламной сетью (необязательная)
            $table->foreignId('advertisment_network_id')
                ->nullable()
                ->constrained('advertisment_networks')
                ->onDelete('set null')
                ->comment('Ссылка на рекламную сеть');

            // Добавляем индекс для быстрого поиска по рекламной сети
            $table->index('advertisment_network_id', 'idx_advertisment_network');

            // Добавляем составной индекс для поиска по статусу и рекламной сети
            $table->index(['status', 'advertisment_network_id'], 'idx_status_network');

            // Добавляем составной индекс для поиска по формату и рекламной сети
            $table->index(['format', 'advertisment_network_id'], 'idx_format_network');

            // Индексы для производительности
            $table->index(['country_id', 'language_id'], 'idx_country_language');
            $table->index(['status', 'country_id'], 'idx_status_country');
            $table->index(['format', 'language_id'], 'idx_format_language');
            $table->index(['browser_id', 'operation_system'], 'idx_browser_os');
            $table->index(['status', 'browser_id'], 'idx_status_browser');
            $table->index(['format', 'operation_system'], 'idx_format_os');

            // Основные поля
            $table->unsignedBigInteger('external_id')->unique()->index();
            $table->boolean('is_adult')->default(false);
            $table->string('title', 128)->default('')->comment('Заголовок');
            $table->string('description', 256)->default('')->comment('Описание');
            $table->char('combined_hash', 64)->unique()->nullable()->index()->comment('Хеш');
            $table->text('landing_url')->nullable()->comment('Ссылка на лендинг');
            $table->timestamp('start_date')->nullable()->comment('Дата начала показа');
            $table->timestamp('end_date')->nullable()->comment('Дата окончания показа');
            $table->foreignId('source_id')
                ->nullable()
                ->constrained('ad_sources')
                ->onDelete('set null')
                ->comment('Ссылка на источник креатива');
            $table->enum('platform', Platform::values())->nullable()->comment('Платформа креатива mobile/desktop');
            // set true if creative is processed ( by queue)
            $table->boolean('is_processed')->default(false)->comment('Флаг обработки креатива');
            $table->timestamp('processed_at')->nullable()->comment('Дата обработки креатива');
            $table->boolean('is_valid')->default(false)->comment('Флаг валидности креатива');
            $table->string('validation_error')->nullable()->comment('Ошибка валидации креатива');
            $table->string('processing_error')->nullable()->comment('Ошибка обработки креатива');


            $table->boolean('has_video')->default(false)->comment('Флаг наличия видео');
            $table->text('video_url')->nullable()->comment('Ссылка на видео');
            $table->integer('video_duration')->nullable()->comment('Длительность видео');
            $table->text('main_image_url')->nullable()->comment('Ссылка на главное изображение');
            $table->string('main_image_size')->nullable()->comment('Размер главного изображения');
            $table->text('icon_url')->nullable()->comment('Ссылка на иконку');
            $table->string('icon_size')->nullable()->comment('Размер иконки');

            $table->integer('social_likes')->nullable()->comment('Лайки в соц. сетях');
            $table->integer('social_comments')->nullable()->comment('Комментарии в соц. сетях');
            $table->integer('social_shares')->nullable()->comment('Репосты в соц. сетях');
            $table->timestamp('last_seen_at')->nullable()->comment('Дата последнего обновления');
            $table->timestamp('external_created_at')->nullable()->comment('Дата создания в источнике');

            $table->softDeletes();
            $table->timestamps();
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
