<?php

namespace Database\Factories;

use App\Enums\Frontend\BrowserType;
use App\Enums\Frontend\DeviceType;
use App\Models\Browser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Browser>
 */
class BrowserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $browsers = ['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera'];
        $platforms = ['Windows 10', 'macOS 14', 'Ubuntu 22.04'];

        return [
            'browser' => $this->faker->randomElement($browsers),
            'browser_type' => BrowserType::BROWSER,
            'device_type' => DeviceType::DESKTOP,
            'ismobiledevice' => false,
            'istablet' => false,
            'browser_version' => $this->faker->numerify('###.0.####.##'),
            'platform' => $this->faker->randomElement($platforms),
            'user_agent' => $this->generateUserAgent(),
            'is_for_filter' => true,
            'is_active' => true,
        ];
    }

    /**
     * Создать десктопный браузер
     */
    public function desktop(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'device_type' => DeviceType::DESKTOP,
                'ismobiledevice' => false,
                'istablet' => false,
                'user_agent' => $this->generateDesktopUserAgent(),
                'platform' => $this->faker->randomElement(['Windows 10', 'Windows 11', 'macOS 14', 'Ubuntu 22.04']),
            ];
        });
    }

    /**
     * Создать мобильный браузер
     */
    public function mobile(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'device_type' => DeviceType::MOBILE,
                'ismobiledevice' => true,
                'istablet' => false,
                'browser' => $this->faker->randomElement(['Chrome Mobile', 'Safari Mobile', 'Samsung Browser', 'Firefox Mobile']),
                'user_agent' => $this->generateMobileUserAgent(),
                'platform' => $this->faker->randomElement(['Android 13', 'Android 14', 'iOS 17', 'iOS 18']),
            ];
        });
    }

    /**
     * Создать планшетный браузер
     */
    public function tablet(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'device_type' => DeviceType::TABLET,
                'ismobiledevice' => false,
                'istablet' => true,
                'browser' => $this->faker->randomElement(['Safari', 'Chrome', 'Firefox']),
                'user_agent' => $this->generateTabletUserAgent(),
                'platform' => $this->faker->randomElement(['iPadOS 17', 'Android 13', 'Android 14']),
            ];
        });
    }

    /**
     * Создать бота/краулера
     */
    public function bot(): static
    {
        return $this->state(function (array $attributes) {
            $bots = [
                'Googlebot' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
                'Bingbot' => 'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)',
                'YandexBot' => 'Mozilla/5.0 (compatible; YandexBot/3.0; +http://yandex.com/bots)',
                'FacebookBot' => 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
            ];

            $botName = $this->faker->randomElement(array_keys($bots));

            return [
                'browser' => $botName,
                'browser_type' => BrowserType::BOT_CRAWLER,
                'device_type' => DeviceType::UNKNOWN,
                'ismobiledevice' => false,
                'istablet' => false,
                'user_agent' => $bots[$botName],
                'platform' => $this->faker->randomElement(['Linux', 'Windows', 'unknown']),
                'browser_version' => $this->faker->numerify('#.#'),
                'is_for_filter' => false,
            ];
        });
    }

    /**
     * Создать активный браузер
     */
    public function active(): static
    {
        return $this->state([
            'is_active' => true,
        ]);
    }

    /**
     * Создать браузер для фильтрации
     */
    public function forFilter(): static
    {
        return $this->state([
            'is_for_filter' => true,
        ]);
    }

    /**
     * Создать неактивный браузер
     */
    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }

    /**
     * Генерировать базовый User-Agent
     */
    private function generateUserAgent(): string
    {
        $browsers = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
        ];

        return $this->faker->randomElement($browsers);
    }

    /**
     * Генерировать десктопный User-Agent
     */
    private function generateDesktopUserAgent(): string
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.2210.91',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ];

        return $this->faker->randomElement($userAgents);
    }

    /**
     * Генерировать мобильный User-Agent
     */
    private function generateMobileUserAgent(): string
    {
        $userAgents = [
            'Mozilla/5.0 (Linux; Android 13; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Linux; Android 13; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/23.0 Chrome/115.0.0.0 Mobile Safari/537.36',
            'Mozilla/5.0 (Mobile; rv:121.0) Gecko/121.0 Firefox/121.0',
        ];

        return $this->faker->randomElement($userAgents);
    }

    /**
     * Генерировать планшетный User-Agent
     */
    private function generateTabletUserAgent(): string
    {
        $userAgents = [
            'Mozilla/5.0 (iPad; CPU OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Linux; Android 13; SM-T870) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Linux; Android 13; SM-T870) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ];

        return $this->faker->randomElement($userAgents);
    }
}
