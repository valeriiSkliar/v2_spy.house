<?php

namespace Database\Seeders;

use App\Models\MainPageComments;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MainPageCommentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reviews = [
            [
                'heading' => [
                    'ru' => 'С ребятами из AdvanceTS работаем давно и продуктивно',
                    'en' => 'We have been working with AdvanceTS team for a long time and productively'
                ],
                'text' => [
                    'ru' => 'Основные 3 кита рекламного бизнеса в интернете, это: контент, креативы и аналитика. В AdvanceTS эти составляющие собраны воедино, причем как интерфейс, так и команда проекта исключительно userfriendly эти составляющие собраны воедино, причем как интерфейс, так и команда проекта исключительно userfriendly',
                    'en' => 'The main 3 pillars of online advertising business are: content, creatives and analytics. In AdvanceTS, these components are brought together, and both the interface and the project team are exceptionally user-friendly'
                ],
                'user_name' => [
                    'ru' => 'Команда Conversion',
                    'en' => 'Conversion Team'
                ],
                'user_position' => [
                    'ru' => '',
                    'en' => ''
                ],
                'thumbnail_src' => '/storage/assets/images/mainpage/review/ava1.jpg',
                'email' => 'conversion@example.com'
            ],
            [
                'heading' => [
                    'ru' => 'Обслуживание на высоте, как и техническая реализация',
                    'en' => 'Service is excellent, as is the technical implementation'
                ],
                'text' => [
                    'ru' => 'Использую сервис AdvanceTS сколько себя помню...',
                    'en' => 'I have been using AdvanceTS service for as long as I can remember...'
                ],
                'user_name' => [
                    'ru' => 'Константин Котов',
                    'en' => 'Konstantin Kotov'
                ],
                'user_position' => [
                    'ru' => 'Moneycrafter',
                    'en' => 'Moneycrafter'
                ],
                'thumbnail_src' => '/storage/assets/images/mainpage/review/ava2.jpg',
                'email' => 'konstantin@moneycrafter.com'
            ],
            [
                'heading' => [
                    'ru' => 'Работаем с AdvanceTs не первый год...',
                    'en' => 'We have been working with AdvanceTs for years...'
                ],
                'text' => [
                    'ru' => 'Ребята зарекомендовали себя не только как мощный сервис...',
                    'en' => 'The team has proven themselves not only as a powerful service...'
                ],
                'user_name' => [
                    'ru' => 'Команда Everad',
                    'en' => 'Everad Team'
                ],
                'user_position' => [
                    'ru' => '',
                    'en' => ''
                ],
                'thumbnail_src' => '/storage/assets/images/mainpage/review/ava3.jpg',
                'email' => 'team@everad.com'
            ],
            [
                'heading' => [
                    'ru' => 'С ребятами из AdvanceTS работаем давно и продуктивно',
                    'en' => 'We have been working with AdvanceTS team for a long time and productively'
                ],
                'text' => [
                    'ru' => 'Сервис постоянно улучшается и совершенствуется...',
                    'en' => 'The service is constantly improving and evolving...'
                ],
                'user_name' => [
                    'ru' => 'Команда Conversion',
                    'en' => 'Conversion Team'
                ],
                'user_position' => [
                    'ru' => '',
                    'en' => ''
                ],
                'thumbnail_src' => '/storage/assets/images/mainpage/review/ava4.jpg',
                'email' => 'conversion2@example.com'
            ],
            [
                'heading' => [
                    'ru' => 'Обслуживание на высоте, как и техническая реализация',
                    'en' => 'Service is excellent, as is the technical implementation'
                ],
                'text' => [
                    'ru' => 'Использую сервис AdvanceTS сколько себя помню...',
                    'en' => 'I have been using AdvanceTS service for as long as I can remember...'
                ],
                'user_name' => [
                    'ru' => 'Константин Котов',
                    'en' => 'Konstantin Kotov'
                ],
                'user_position' => [
                    'ru' => 'Moneycrafter',
                    'en' => 'Moneycrafter'
                ],
                'thumbnail_src' => '/storage/assets/images/mainpage/review/ava5.jpg',
                'email' => 'konstantin2@moneycrafter.com'
            ],
            [
                'heading' => [
                    'ru' => 'Работаем с AdvanceTs не первый год...',
                    'en' => 'We have been working with AdvanceTs for years...'
                ],
                'text' => [
                    'ru' => 'Ребята зарекомендовали себя не только как мощный сервис...',
                    'en' => 'The team has proven themselves not only as a powerful service...'
                ],
                'user_name' => [
                    'ru' => 'Команда Everad',
                    'en' => 'Everad Team'
                ],
                'user_position' => [
                    'ru' => '',
                    'en' => ''
                ],
                'thumbnail_src' => '/storage/assets/images/mainpage/review/ava6.jpg',
                'email' => 'team2@everad.com'
            ],
            [
                'heading' => [
                    'ru' => 'С ребятами из AdvanceTS работаем давно и продуктивно',
                    'en' => 'We have been working with AdvanceTS team for a long time and productively'
                ],
                'text' => [
                    'ru' => 'Сервис постоянно улучшается и совершенствуется...',
                    'en' => 'The service is constantly improving and evolving...'
                ],
                'user_name' => [
                    'ru' => 'Команда Conversion',
                    'en' => 'Conversion Team'
                ],
                'user_position' => [
                    'ru' => '',
                    'en' => ''
                ],
                'thumbnail_src' => '/storage/assets/images/mainpage/review/ava7.jpg',
                'email' => 'conversion3@example.com'
            ],
            [
                'heading' => [
                    'ru' => 'Обслуживание на высоте, как и техническая реализация',
                    'en' => 'Service is excellent, as is the technical implementation'
                ],
                'text' => [
                    'ru' => 'Использую сервис AdvanceTS сколько себя помню...',
                    'en' => 'I have been using AdvanceTS service for as long as I can remember...'
                ],
                'user_name' => [
                    'ru' => 'Константин Котов',
                    'en' => 'Konstantin Kotov'
                ],
                'user_position' => [
                    'ru' => 'Moneycrafter',
                    'en' => 'Moneycrafter'
                ],
                'thumbnail_src' => '/storage/assets/images/mainpage/review/ava8.jpg',
                'email' => 'konstantin3@moneycrafter.com'
            ],
            [
                'heading' => [
                    'ru' => 'Работаем с AdvanceTs не первый год...',
                    'en' => 'We have been working with AdvanceTs for years...'
                ],
                'text' => [
                    'ru' => 'Ребята зарекомендовали себя не только как мощный сервис...',
                    'en' => 'The team has proven themselves not only as a powerful service...'
                ],
                'user_name' => [
                    'ru' => 'Команда Everad',
                    'en' => 'Everad Team'
                ],
                'user_position' => [
                    'ru' => '',
                    'en' => ''
                ],
                'thumbnail_src' => '/storage/assets/images/mainpage/review/ava9.jpg',
                'email' => 'team3@everad.com'
            ],
            [
                'heading' => [
                    'ru' => 'С ребятами из AdvanceTS работаем давно и продуктивно',
                    'en' => 'We have been working with AdvanceTS team for a long time and productively'
                ],
                'text' => [
                    'ru' => 'Сервис постоянно улучшается и совершенствуется...',
                    'en' => 'The service is constantly improving and evolving...'
                ],
                'user_name' => [
                    'ru' => 'Команда Conversion',
                    'en' => 'Conversion Team'
                ],
                'user_position' => [
                    'ru' => '',
                    'en' => ''
                ],
                'thumbnail_src' => '/storage/assets/images/mainpage/review/ava10.jpg',
                'email' => 'conversion4@example.com'
            ],
            [
                'heading' => [
                    'ru' => 'Обслуживание на высоте, как и техническая реализация',
                    'en' => 'Service is excellent, as is the technical implementation'
                ],
                'text' => [
                    'ru' => 'Использую сервис AdvanceTS сколько себя помню...',
                    'en' => 'I have been using AdvanceTS service for as long as I can remember...'
                ],
                'user_name' => [
                    'ru' => 'Константин Котов',
                    'en' => 'Konstantin Kotov'
                ],
                'user_position' => [
                    'ru' => 'Moneycrafter',
                    'en' => 'Moneycrafter'
                ],
                'thumbnail_src' => '/storage/assets/images/mainpage/review/ava11.jpg',
                'email' => 'konstantin4@moneycrafter.com'
            ],
            [
                'heading' => [
                    'ru' => 'Работаем с AdvanceTs не первый год...',
                    'en' => 'We have been working with AdvanceTs for years...'
                ],
                'text' => [
                    'ru' => 'Ребята зарекомендовали себя не только как мощный сервис...',
                    'en' => 'The team has proven themselves not only as a powerful service...'
                ],
                'user_name' => [
                    'ru' => 'Команда Everad',
                    'en' => 'Everad Team'
                ],
                'user_position' => [
                    'ru' => '',
                    'en' => ''
                ],
                'thumbnail_src' => '/storage/assets/images/mainpage/review/ava12.jpg',
                'email' => 'team4@everad.com'
            ]
        ];

        // Clear existing records
        DB::table('main_page_comments')->truncate();

        // Create records
        foreach ($reviews as $index => $review) {
            $data = [
                'heading' => json_encode($review['heading']),
                'text' => json_encode($review['text']),
                'user_name' => json_encode($review['user_name']),
                'user_position' => json_encode($review['user_position']),
                'thumbnail_src' => $review['thumbnail_src'],
                'email' => $review['email'],
                'display_order' => $index + 1,
                'is_active' => true,
                'content' => json_encode([]),
                'created_at' => now(),
                'updated_at' => now()
            ];

            DB::table('main_page_comments')->insert($data);
        }
    }
}
