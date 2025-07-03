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
        // - Дополнительные поля (is_adult, external_id, title, description, combined_hash, landing_url, last_seen_at)
        Creative::factory(50)->create();
    }
}
