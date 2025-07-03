<?php

namespace Database\Seeders;

use App\Models\AdSource;
use Illuminate\Database\Seeder;

class AdSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sources = [
            [
                'source_name' => 'push_house',
                'source_display_name' => 'Push House',
            ],
            [
                'source_name' => 'tiktok',
                'source_display_name' => 'TikTok Ads',
            ],
            [
                'source_name' => 'facebook',
                'source_display_name' => 'Facebook Ads',
            ],
            [
                'source_name' => 'feed_house',
                'source_display_name' => 'Feed House',
            ],
            [
                'source_name' => 'google_ads',
                'source_display_name' => 'Google Ads',
            ],
            [
                'source_name' => 'telegram',
                'source_display_name' => 'Telegram Ads',
            ],
            [
                'source_name' => 'vk_ads',
                'source_display_name' => 'VK Ads',
            ],
            [
                'source_name' => 'yandex_direct',
                'source_display_name' => 'Yandex Direct',
            ],
        ];

        foreach ($sources as $source) {
            AdSource::firstOrCreate(
                ['source_name' => $source['source_name']],
                $source
            );
        }

        $this->command->info('Ad sources seeded successfully!');
    }
}
