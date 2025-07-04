<?php

namespace Database\Factories;

use App\Enums\Frontend\AdvertisingFormat;
use App\Enums\Frontend\AdvertisingStatus;
use App\Enums\Frontend\OperationSystem;
use App\Models\Browser;
use App\Models\Frontend\IsoEntity;
use App\Models\AdvertismentNetwork;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Creative>
 */
class CreativeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'format' => $this->faker->randomElement(AdvertisingFormat::cases())->value,
            'status' => $this->faker->randomElement(AdvertisingStatus::cases())->value,
            'country_id' => IsoEntity::where('type', 'country')->inRandomOrder()->first()?->id,
            'language_id' => IsoEntity::where('type', 'language')->inRandomOrder()->first()?->id,
            'browser_id' => Browser::active()->forFilter()->inRandomOrder()->first()?->id,
            'operation_system' => $this->faker->randomElement(OperationSystem::cases())->value,
            'advertisment_network_id' => $this->faker->randomElement(
                AdvertismentNetwork::active()->pluck('id')->toArray()
            ),
            'external_id' => $this->faker->unique()->randomNumber(8), // 8-значный уникальный ID
            'is_adult' => $this->faker->boolean(20), // 20% вероятность adult контента
            'title' => $this->faker->sentence(3, true), // Короткий заголовок (до 128 символов)
            'description' => $this->faker->text(200), // Описание (до 256 символов)
            'combined_hash' => hash('sha256', $this->faker->uuid()), // 64-символьный хеш
            'landing_url' => $this->faker->optional(0.8)->url(), // 80% вероятность наличия URL
            'start_date' => $this->faker->optional(0.7)->dateTimeBetween('-60 days', 'now'), // Дата начала показа
            'end_date' => $this->faker->optional(0.5)->dateTimeBetween('now', '+90 days'), // Дата окончания показа
            'is_processed' => $this->faker->boolean(70), // 70% вероятность обработки
            // media fields
            'has_video' => $this->faker->boolean(30), // 30% вероятность наличия видео
            'video_url' => null, // Будет установлено в withVideo() state
            'video_duration' => null, // Будет установлено в withVideo() state
            'main_image_url' => $this->getRandomImageUrl(640, 480, 'movie'),
            'main_image_size' => $this->faker->optional(0.8)->randomElement(['640x480', '800x600', '1024x768', '1200x900']),
            'icon_url' => $this->getRandomImageUrl(64, 64, 'album'),
            'icon_size' => $this->faker->optional(0.6)->randomElement(['32x32', '48x48', '64x64', '96x96']),
            // social fields
            'social_likes' => $this->faker->optional(0.6)->numberBetween(0, 10000), // Лайки в соц. сетях
            'social_comments' => $this->faker->optional(0.4)->numberBetween(0, 1000), // Комментарии в соц. сетях
            'social_shares' => $this->faker->optional(0.3)->numberBetween(0, 500), // Репосты в соц. сетях
            'last_seen_at' => $this->faker->optional(0.9)->dateTimeBetween('-30 days', 'now'), // Последняя активность
        ];
    }

    private function getRandomImageUrl($width, $height, $selectedType = 'random'): string
    {
        $imageTypes = [
            'game' => 'https://via.assets.so/game.png?id=' . random_int(1, 50) . '&q=95&w={width}&h={height}&fit=fill',
            'album' => 'https://via.assets.so/album.png?id=' . random_int(1, 50) . '&q=95&w={width}&h={height}&fit=fill',
            'movie' => 'https://via.assets.so/movie.png?id=' . random_int(1, 50) . '&q=95&w={width}&h={height}&fit=fill'
        ];
        if ($selectedType === 'random') {
            $selectedType = $this->faker->randomElement(array_keys($imageTypes));
        }
        $template = $imageTypes[$selectedType];

        return str_replace(['{width}', '{height}'], [$width, $height], $template);
    }

    /**
     * Состояние для креативов с видео
     */
    public function withVideo(): static
    {
        return $this->state(fn(array $attributes) => [
            'has_video' => true,
            'video_url' => $this->faker->url(),
            'video_duration' => $this->faker->randomElement(['0:15', '0:30', '1:00', '1:30', '2:00']),
        ]);
    }

    /**
     * Состояние для креативов без видео
     */
    public function withoutVideo(): static
    {
        return $this->state(fn(array $attributes) => [
            'has_video' => false,
            'video_url' => null,
            'video_duration' => null,
        ]);
    }
}
