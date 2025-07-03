<?php

namespace Database\Seeders;

use App\Models\Creative;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CreativeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Создаём тестовые креативы с полным набором полей:
        // - Базовые поля (format, status, связи с ISO, браузер, ОС, рекламная сеть)
        // - Контентные поля (external_id, is_adult, title, description, combined_hash, landing_url)
        // - Временные поля (start_date, end_date, last_seen_at)
        // - Обработка (is_processed)
        // - Медиа контент (has_video, video_url, video_duration, main_image_url, main_image_size, icon_url, icon_size)
        // - Социальные метрики (social_likes, social_comments, social_shares)

        // Обычные креативы (30 шт)
        Creative::factory(30)->create();

        // Креативы с видео (10 шт)
        Creative::factory(10)->withVideo()->create();

        // Креативы без видео (10 шт)
        Creative::factory(10)->withoutVideo()->create();
    }
}
