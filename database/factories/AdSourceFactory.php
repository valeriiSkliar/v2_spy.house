<?php

namespace Database\Factories;

use App\Models\AdSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdSource>
 */
class AdSourceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = AdSource::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sources = [
            'push_house' => 'Push House',
            'tiktok' => 'TikTok Ads',
            'facebook' => 'Facebook Ads',
            'feed_house' => 'Feed House',
            'google_ads' => 'Google Ads',
            'telegram' => 'Telegram Ads',
            'vk_ads' => 'VK Ads',
            'yandex_direct' => 'Yandex Direct',
        ];

        $sourceName = $this->faker->randomElement(array_keys($sources));

        return [
            'source_name' => $sourceName,
            'source_display_name' => $sources[$sourceName],
        ];
    }

    /**
     * Создать конкретный источник
     */
    public function forSource(string $sourceName, string $displayName): static
    {
        return $this->state(fn(array $attributes) => [
            'source_name' => $sourceName,
            'source_display_name' => $displayName,
        ]);
    }

    /**
     * Создать Push House источник
     */
    public function pushHouse(): static
    {
        return $this->forSource('push_house', 'Push House');
    }

    /**
     * Создать TikTok источник
     */
    public function tiktok(): static
    {
        return $this->forSource('tiktok', 'TikTok Ads');
    }

    /**
     * Создать Facebook источник
     */
    public function facebook(): static
    {
        return $this->forSource('facebook', 'Facebook Ads');
    }

    /**
     * Создать Feed House источник
     */
    public function feedHouse(): static
    {
        return $this->forSource('feed_house', 'Feed House');
    }
}
