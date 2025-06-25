<?php

namespace Database\Seeders;

use App\Models\Browser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrowserSeeder extends Seeder
{
    /**
     * Предопределенные популярные браузеры с актуальными User-Agent строками
     */
    private array $browsersData = [
        // === DESKTOP BROWSERS ===
        [
            'browser' => 'Chrome',
            'browser_type' => 'Browser',
            'device_type' => 'Desktop',
            'ismobiledevice' => false,
            'istablet' => false,
            'browser_version' => '120.0.6099.199',
            'platform' => 'Windows 10',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'is_for_filter' => true,
        ],
        [
            'browser' => 'Firefox',
            'browser_type' => 'Browser',
            'device_type' => 'Desktop',
            'ismobiledevice' => false,
            'istablet' => false,
            'browser_version' => '121.0',
            'platform' => 'Windows 10',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
            'is_for_filter' => true,
        ],
        [
            'browser' => 'Safari',
            'browser_type' => 'Browser',
            'device_type' => 'Desktop',
            'ismobiledevice' => false,
            'istablet' => false,
            'browser_version' => '17.2',
            'platform' => 'macOS 14',
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
            'is_for_filter' => true,
        ],
        [
            'browser' => 'Edge',
            'browser_type' => 'Browser',
            'device_type' => 'Desktop',
            'ismobiledevice' => false,
            'istablet' => false,
            'browser_version' => '120.0.2210.91',
            'platform' => 'Windows 10',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.2210.91',
            'is_for_filter' => true,
        ],
        [
            'browser' => 'Opera',
            'browser_type' => 'Browser',
            'device_type' => 'Desktop',
            'ismobiledevice' => false,
            'istablet' => false,
            'browser_version' => '106.0.4998.70',
            'platform' => 'Windows 10',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 OPR/106.0.0.0',
            'is_for_filter' => true,
        ],

        // === MOBILE BROWSERS ===
        [
            'browser' => 'Chrome Mobile',
            'browser_type' => 'Browser',
            'device_type' => 'Mobile',
            'ismobiledevice' => true,
            'istablet' => false,
            'browser_version' => '120.0.6099.193',
            'platform' => 'Android 13',
            'user_agent' => 'Mozilla/5.0 (Linux; Android 13; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
            'is_for_filter' => true,
        ],
        [
            'browser' => 'Safari Mobile',
            'browser_type' => 'Browser',
            'device_type' => 'Mobile',
            'ismobiledevice' => true,
            'istablet' => false,
            'browser_version' => '17.2',
            'platform' => 'iOS 17',
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
            'is_for_filter' => true,
        ],
        [
            'browser' => 'Samsung Browser',
            'browser_type' => 'Browser',
            'device_type' => 'Mobile',
            'ismobiledevice' => true,
            'istablet' => false,
            'browser_version' => '23.0.1.2',
            'platform' => 'Android 13',
            'user_agent' => 'Mozilla/5.0 (Linux; Android 13; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/23.0 Chrome/115.0.0.0 Mobile Safari/537.36',
            'is_for_filter' => true,
        ],
        [
            'browser' => 'Firefox Mobile',
            'browser_type' => 'Browser',
            'device_type' => 'Mobile',
            'ismobiledevice' => true,
            'istablet' => false,
            'browser_version' => '121.0',
            'platform' => 'Android 13',
            'user_agent' => 'Mozilla/5.0 (Mobile; rv:121.0) Gecko/121.0 Firefox/121.0',
            'is_for_filter' => true,
        ],

        // === TABLET BROWSERS ===
        [
            'browser' => 'Safari',
            'browser_type' => 'Browser',
            'device_type' => 'Tablet',
            'ismobiledevice' => false,
            'istablet' => true,
            'browser_version' => '17.2',
            'platform' => 'iPadOS 17',
            'user_agent' => 'Mozilla/5.0 (iPad; CPU OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
            'is_for_filter' => true,
        ],
        [
            'browser' => 'Chrome',
            'browser_type' => 'Browser',
            'device_type' => 'Tablet',
            'ismobiledevice' => false,
            'istablet' => true,
            'browser_version' => '120.0.6099.193',
            'platform' => 'Android 13',
            'user_agent' => 'Mozilla/5.0 (Linux; Android 13; SM-T870) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'is_for_filter' => true,
        ],

        // === BOTS AND CRAWLERS ===
        [
            'browser' => 'Googlebot',
            'browser_type' => 'Bot/Crawler',
            'device_type' => 'unknown',
            'ismobiledevice' => false,
            'istablet' => false,
            'browser_version' => '2.1',
            'platform' => 'Linux',
            'user_agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'is_for_filter' => false,
        ],
        [
            'browser' => 'Bingbot',
            'browser_type' => 'Bot/Crawler',
            'device_type' => 'unknown',
            'ismobiledevice' => false,
            'istablet' => false,
            'browser_version' => '2.0',
            'platform' => 'Windows',
            'user_agent' => 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
            'is_for_filter' => false,
        ],
        [
            'browser' => 'YandexBot',
            'browser_type' => 'Bot/Crawler',
            'device_type' => 'unknown',
            'ismobiledevice' => false,
            'istablet' => false,
            'browser_version' => '3.0',
            'platform' => 'Linux',
            'user_agent' => 'Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)',
            'is_for_filter' => false,
        ],
        [
            'browser' => 'FacebookBot',
            'browser_type' => 'Bot/Crawler',
            'device_type' => 'unknown',
            'ismobiledevice' => false,
            'istablet' => false,
            'browser_version' => '1.0',
            'platform' => 'Linux',
            'user_agent' => 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
            'is_for_filter' => false,
        ],
        [
            'browser' => 'WhatsApp',
            'browser_type' => 'Bot/Crawler',
            'device_type' => 'unknown',
            'ismobiledevice' => false,
            'istablet' => false,
            'browser_version' => '2.0',
            'platform' => 'unknown',
            'user_agent' => 'WhatsApp/2.23.24.76 A',
            'is_for_filter' => false,
        ],

        // === ALTERNATIVE BROWSERS ===
        [
            'browser' => 'Brave',
            'browser_type' => 'Browser',
            'device_type' => 'Desktop',
            'ismobiledevice' => false,
            'istablet' => false,
            'browser_version' => '1.61.109',
            'platform' => 'Windows 10',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'is_for_filter' => true,
        ],
        [
            'browser' => 'Vivaldi',
            'browser_type' => 'Browser',
            'device_type' => 'Desktop',
            'ismobiledevice' => false,
            'istablet' => false,
            'browser_version' => '6.5.3206.63',
            'platform' => 'Windows 10',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Vivaldi/6.5.3206.63',
            'is_for_filter' => true,
        ],

        // === LEGACY BROWSERS ===
        [
            'browser' => 'Internet Explorer',
            'browser_type' => 'Browser',
            'device_type' => 'Desktop',
            'ismobiledevice' => false,
            'istablet' => false,
            'browser_version' => '11.0',
            'platform' => 'Windows 10',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko',
            'is_for_filter' => false,
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Очистка таблицы
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Browser::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Заполнение таблицы browsers...');

        // Заполнение предопределенными данными
        foreach ($this->browsersData as $index => $browserData) {
            Browser::create(array_merge($browserData, [
                'is_active' => true,
            ]));

            // Прогресс
            if (($index + 1) % 5 === 0) {
                $this->command->info("Добавлено браузеров: " . ($index + 1));
            }
        }

        $this->command->info('Основные браузеры добавлены: ' . count($this->browsersData));

        // Генерация дополнительных тестовых данных
        $this->command->info('Генерация дополнительных тестовых данных...');

        // Добавляем дополнительные десктопные браузеры
        Browser::factory()->desktop()->active()->forFilter()->count(10)->create();

        // Добавляем дополнительные мобильные браузеры
        Browser::factory()->mobile()->active()->forFilter()->count(10)->create();

        // Добавляем дополнительные планшетные браузеры
        Browser::factory()->tablet()->active()->forFilter()->count(5)->create();

        // Добавляем дополнительных ботов
        Browser::factory()->bot()->active()->count(5)->create();

        $totalCount = Browser::count();
        $activeCount = Browser::active()->count();
        $forFilterCount = Browser::forFilter()->count();
        $mobileCount = Browser::mobile()->count();
        $tabletCount = Browser::tablet()->count();
        $botCount = Browser::bots()->count();

        $this->command->info("Заполнение завершено!");
        $this->command->info("Статистика:");
        $this->command->info("  Всего браузеров: {$totalCount}");
        $this->command->info("  Активных: {$activeCount}");
        $this->command->info("  Для фильтрации: {$forFilterCount}");
        $this->command->info("  Мобильных: {$mobileCount}");
        $this->command->info("  Планшетных: {$tabletCount}");
        $this->command->info("  Ботов/краулеров: {$botCount}");
    }
}
