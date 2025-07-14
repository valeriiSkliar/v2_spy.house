<?php

namespace Database\Factories\Frontend;

use App\Models\Frontend\MainPageComments;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Frontend\MainPageComments>
 */
class MainPageCommentsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MainPageComments::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $locales = ['en', 'ru'];
        $positions = [
            'en' => ['Media buyer', 'CPA Marketer', 'Affiliate Manager', 'Performance Marketer', 'Digital Marketer'],
            'ru' => ['Медиабайер', 'CPA-маркетолог', 'Аффилиат-менеджер', 'Перформанс-маркетолог', 'Диджитал-маркетолог']
        ];

        $reviews = [
            'en' => [
                'Great platform for media buyers! Excellent creatives and high conversion rates.',
                'Amazing service with professional support. Highly recommend for all affiliates.',
                'Best CPA network I\'ve worked with. Fast payments and quality offers.',
                'Outstanding performance and reliable tracking. Perfect for scaling campaigns.',
                'Excellent user experience and great variety of offers. Top-notch platform!'
            ],
            'ru' => [
                'Отличная платформа для медиабайеров! Превосходные креативы и высокие конверсии.',
                'Потрясающий сервис с профессиональной поддержкой. Настоятельно рекомендую всем аффилиатам.',
                'Лучшая CPA-сеть, с которой я работал. Быстрые выплаты и качественные офферы.',
                'Выдающаяся производительность и надежный трекинг. Идеально для масштабирования кампаний.',
                'Отличный пользовательский опыт и большое разнообразие офферов. Платформа высшего класса!'
            ]
        ];

        $headings = [
            'en' => ['Excellent Service', 'Great Results', 'Highly Recommended', 'Outstanding Platform', 'Perfect Choice'],
            'ru' => ['Отличный сервис', 'Великолепные результаты', 'Настоятельно рекомендую', 'Выдающаяся платформа', 'Идеальный выбор']
        ];

        $names = ['Alex', 'Maria', 'John', 'Anna', 'David', 'Elena', 'Michael', 'Sofia', 'Robert', 'Natasha'];

        $translatedHeading = [];
        $translatedPosition = [];
        $translatedName = [];
        $translatedContent = [];

        foreach ($locales as $locale) {
            $translatedHeading[$locale] = $this->faker->randomElement($headings[$locale]);
            $translatedPosition[$locale] = $this->faker->randomElement($positions[$locale]);
            $translatedName[$locale] = $this->faker->randomElement($names);
            $translatedContent[$locale] = $this->faker->randomElement($reviews[$locale]);
        }

        return [
            'heading' => $translatedHeading,
            'user_position' => $translatedPosition,
            'user_name' => $translatedName,
            'thumbnail_src' => null, // Будет генерироваться через UI Avatars
            'email' => $this->faker->unique()->safeEmail(),
            'text' => $translatedContent, // Для совместимости
            'content' => $translatedContent,
            'rating' => $this->faker->numberBetween(4, 5),
            'is_active' => true,
            'display_order' => $this->faker->numberBetween(1, 100),
        ];
    }

    /**
     * Indicate that the review is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the review has a custom thumbnail.
     */
    public function withThumbnail(): static
    {
        return $this->state(fn(array $attributes) => [
            'thumbnail_src' => $this->faker->imageUrl(100, 100, 'people'),
        ]);
    }
}
