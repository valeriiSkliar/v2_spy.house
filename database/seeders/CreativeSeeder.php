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
        // Создаём тестовые креативы с привязкой к ISO сущностям
        Creative::factory(50)->create();
    }
}
