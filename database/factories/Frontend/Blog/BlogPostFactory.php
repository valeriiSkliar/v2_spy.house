<?php

namespace Database\Factories\Frontend\Blog;

use App\Models\Frontend\Blog\Author;
use App\Models\Frontend\Blog\BlogPost;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Faker\Generator;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Frontend\Blog\BlogPost>
 */
class BlogPostFactory extends Factory
{
    protected $model = BlogPost::class;

    /**
     * Russian title components for localized generation
     */
    private array $russianTitleComponents = [
        'topics' => [
            'арбитраж трафика',
            'монетизация сайтов',
            'веб-разработка',
            'цифровой маркетинг',
            'контекстная реклама',
            'социальные сети',
            'email маркетинг',
            'SEO оптимизация',
            'контент маркетинг',
            'партнерские программы',
            'криптовалюта',
            'блокчейн технологии',
            'мобильная разработка',
            'интернет магазин',
            'конверсия продаж'
        ],
        'actions' => [
            'как увеличить',
            'способы улучшения',
            'секреты эффективного',
            'гайд по',
            'пошаговое руководство',
            'лучшие практики',
            'ошибки в',
            'тренды в',
            'будущее',
            'инновации в',
            'стратегии для',
            'инструменты для'
        ],
        'results' => [
            'доходность',
            'прибыль',
            'конверсию',
            'трафик',
            'продажи',
            'эффективность',
            'рентабельность',
            'результативность',
            'успех'
        ],
        'contexts' => [
            'в 2024 году',
            'для новичков',
            'для профессионалов',
            'на практике',
            'без вложений',
            'с минимальными затратами',
            'за 30 дней',
            'поэтапно'
        ]
    ];

    /**
     * Generate localized title with specific length range
     *
     * @param string $locale
     * @param int $minLength
     * @param int $maxLength
     * @return string
     */
    private function generateLocalizedTitle(string $locale = 'en_US', int $minLength = 60, int $maxLength = 100): string
    {
        if ($locale === 'ru_RU') {
            return $this->generateRussianTitle($minLength, $maxLength);
        }

        // Create faker instance with specific locale for English
        $localeFaker = \Faker\Factory::create($locale);

        do {
            $title = $localeFaker->realText($maxLength);
            // Remove trailing punctuation and trim
            $title = rtrim(trim($title), '.');
            $length = mb_strlen($title);
        } while ($length < $minLength || $length > $maxLength);

        return $title;
    }

    /**
     * Generate Russian title using predefined components
     *
     * @param int $minLength
     * @param int $maxLength
     * @return string
     */
    private function generateRussianTitle(int $minLength = 60, int $maxLength = 100): string
    {
        $components = $this->russianTitleComponents;

        do {
            $titleParts = [];

            // Generate different title patterns
            $pattern = rand(1, 4);

            switch ($pattern) {
                case 1:
                    // "Как увеличить доходность арбитража трафика в 2024 году"
                    $titleParts[] = ucfirst($this->faker->randomElement($components['actions']));
                    $titleParts[] = $this->faker->randomElement($components['results']);
                    $titleParts[] = $this->faker->randomElement($components['topics']);
                    $titleParts[] = $this->faker->randomElement($components['contexts']);
                    break;

                case 2:
                    // "Секреты эффективного email маркетинга для профессионалов"
                    $titleParts[] = ucfirst($this->faker->randomElement($components['actions']));
                    $titleParts[] = $this->faker->randomElement($components['topics']);
                    $titleParts[] = $this->faker->randomElement($components['contexts']);
                    break;

                case 3:
                    // "Монетизация сайтов: лучшие практики и инструменты для успеха"
                    $titleParts[] = ucfirst($this->faker->randomElement($components['topics']));
                    $titleParts[] = ': ' . $this->faker->randomElement($components['actions']);
                    $titleParts[] = 'и инструменты для';
                    $titleParts[] = $this->faker->randomElement($components['results']);
                    break;

                case 4:
                    // "Тренды в цифровом маркетинге: как увеличить конверсию без вложений"
                    $titleParts[] = ucfirst($this->faker->randomElement($components['actions']));
                    $titleParts[] = $this->faker->randomElement($components['topics']);
                    $titleParts[] = ': ' . $this->faker->randomElement($components['actions']);
                    $titleParts[] = $this->faker->randomElement($components['results']);
                    $titleParts[] = $this->faker->randomElement($components['contexts']);
                    break;
            }

            $title = implode(' ', $titleParts);

            // Ensure proper length
            if (mb_strlen($title) < $minLength) {
                // Add additional context if too short
                $additionalContext = ' ' . $this->faker->randomElement([
                    'с примерами и кейсами',
                    'пошаговая инструкция',
                    'практические советы',
                    'от экспертов индустрии',
                    'проверенные методы'
                ]);
                $title .= $additionalContext;
            }

            // Trim if too long
            if (mb_strlen($title) > $maxLength) {
                $title = mb_substr($title, 0, $maxLength);
                $lastSpace = mb_strrpos($title, ' ');
                if ($lastSpace !== false) {
                    $title = mb_substr($title, 0, $lastSpace);
                }
            }

            $length = mb_strlen($title);
        } while ($length < $minLength || $length > $maxLength);

        return $title;
    }

    /**
     * Generate Russian content (summary or full content)
     *
     * @param string $type 'summary' or 'content'
     * @return string
     */
    private function generateRussianContent(string $type = 'summary'): string
    {
        $sentences = [
            'В современном мире цифрового маркетинга важно использовать проверенные стратегии.',
            'Арбитраж трафика позволяет получать стабильный доход при правильном подходе.',
            'Монетизация веб-ресурсов требует глубокого понимания целевой аудитории.',
            'Эффективная реклама в социальных сетях может значительно увеличить конверсию.',
            'SEO оптимизация остается одним из ключевых факторов успеха онлайн-бизнеса.',
            'Email маркетинг показывает высокую рентабельность при корректной настройке.',
            'Контент маркетинг помогает строить долгосрочные отношения с клиентами.',
            'Аналитика данных позволяет принимать обоснованные бизнес-решения.',
            'Автоматизация процессов существенно повышает эффективность работы.',
            'Тестирование различных подходов помогает найти оптимальную стратегию.',
            'Работа с партнерскими программами может стать дополнительным источником дохода.',
            'Изучение поведения пользователей дает ценную информацию для оптимизации.',
            'Качественный контент всегда привлекает больше органического трафика.',
            'Регулярный анализ конкурентов помогает оставаться на шаг впереди.',
            'Инвестиции в образование команды окупаются повышением производительности.'
        ];

        if ($type === 'summary') {
            // For summary, return 2-3 sentences
            $selectedSentences = $this->faker->randomElements($sentences, rand(2, 3));
            return implode(' ', $selectedSentences);
        } else {
            // For content, return 5-8 sentences
            $selectedSentences = $this->faker->randomElements($sentences, rand(5, 8));
            return implode(' ', $selectedSentences);
        }
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $titleEn = $this->generateLocalizedTitle('en_US');
        $titleRu = $this->generateLocalizedTitle('ru_RU');

        return [
            'title' => [
                'en' => $titleEn,
                'ru' => $titleRu,
            ],
            'summary' => [
                'en' => $this->faker->paragraph(2),
                'ru' => $this->generateRussianContent('summary'),
            ],
            'content' => [
                'en' => $this->faker->paragraphs(5, true),
                'ru' => $this->generateRussianContent('content'),
            ],
            'slug' => Str::slug($titleEn),
            'views_count' => $this->faker->numberBetween(0, 10000),
            'author_id' => Author::factory(),
            'featured_image' => 'https://picsum.photos/300/200',
            'is_published' => $this->faker->boolean(80),
        ];
    }
}
