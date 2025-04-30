<?php

namespace Database\Seeders;

use App\Models\Frontend\Service\Service;
use App\Models\Frontend\Service\ServiceCategories;
use Illuminate\Database\Seeder;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ServiceSeeder extends Seeder
{

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // First delete all services, then categories to respect foreign key constraints
        DB::table('services')->delete();
        DB::table('service_categories')->delete();

        $categories = [
            'advertising_networks' => [
                'name' => ['en' => 'Advertising Networks', 'ru' => 'Рекламные сети'],
                'description' => ['en' => 'Advertising Networks Services', 'ru' => 'Услуги рекламных сетей'],
            ],
            'affiliate_programs' => [
                'name' => ['en' => 'Affiliate Programs', 'ru' => 'Партнёрские программы'],
                'description' => ['en' => 'Affiliate Program Services', 'ru' => 'Услуги партнёрских программ'],
            ],
            'trackers' => [
                'name' => ['en' => 'Trackers', 'ru' => 'Трекеры'],
                'description' => ['en' => 'Tracking Services', 'ru' => 'Услуги трекинга'],
            ],
            'hosting' => [
                'name' => ['en' => 'Hosting', 'ru' => 'Хостинг'],
                'description' => ['en' => 'Hosting Services', 'ru' => 'Услуги хостинга'],
            ],
            'domain_registrars' => [
                'name' => ['en' => 'Domain Registrars', 'ru' => 'Регистраторы доменов'],
                'description' => ['en' => 'Domain Registration Services', 'ru' => 'Услуги регистрации доменов'],
            ],
            'spy_services' => [
                'name' => ['en' => 'SPY Services', 'ru' => 'Шпионские услуги'],
                'description' => ['en' => 'SPY Services', 'ru' => 'Шпионские услуги'],
            ],
            'proxy_vpn_services' => [
                'name' => ['en' => 'Proxy and VPN Services', 'ru' => 'Услуги прокси и VPN'],
                'description' => ['en' => 'Proxy and VPN Services', 'ru' => 'Услуги прокси и VPN'],
            ],
            'anti_detection_browsers' => [
                'name' => ['en' => 'Anti-detection Browsers', 'ru' => 'Браузеры антидетект'],
                'description' => ['en' => 'Anti-detection Browser Services', 'ru' => 'Услуги браузеров антидетект'],
            ],
            'account_purchase_rental' => [
                'name' => ['en' => 'Account Purchase and Rental', 'ru' => 'Покупка и аренда аккаунтов'],
                'description' => ['en' => 'Account Purchase and Rental Services', 'ru' => 'Услуги покупки и аренды аккаунтов'],
            ],
            'app_purchase_rental' => [
                'name' => ['en' => 'Purchase and Rental of Applications', 'ru' => 'Покупка и аренда приложений'],
                'description' => ['en' => 'Application Purchase and Rental Services', 'ru' => 'Услуги покупки и аренды приложений'],
            ],
            'notification_newsletter_services' => [
                'name' => ['en' => 'Notification and Newsletter Services', 'ru' => 'Услуги уведомлений и рассылок'],
                'description' => ['en' => 'Notification and Newsletter Services', 'ru' => 'Услуги уведомлений и рассылок'],
            ],
            'payment_services' => [
                'name' => ['en' => 'Payment Services', 'ru' => 'Платёжные услуги'],
                'description' => ['en' => 'Payment Processing Services', 'ru' => 'Услуги обработки платежей'],
            ],
            'other_services_utilities' => [
                'name' => ['en' => 'Other Services and Utilities', 'ru' => 'Другие услуги и утилиты'],
                'description' => ['en' => 'Various Services and Utilities', 'ru' => 'Различные услуги и утилиты'],
            ],
        ];
        $mockServices = [
            'advertising_networks' => [
                [
                    'name' => ['en' => 'PropellerAds', 'ru' => 'PropellerAds'],
                    'description' => ['en' => 'Global advertising network with advanced targeting', 'ru' => 'Глобальная рекламная сеть с расширенным таргетингом'],
                    'price' => 'From $100',
                    'image' => '/storage/assets/images/services/1.jpeg',
                    'url' => 'https://propellerads.com',
                    'status' => 'Active',
                ],
                [
                    'name' => ['en' => 'TrafficStars', 'ru' => 'TrafficStars'],
                    'description' => ['en' => 'Premium ad network with high-quality traffic', 'ru' => 'Премиум рекламная сеть с качественным трафиком'],
                    'price' => 'From $50',
                    'image' => '/storage/assets/images/services/2.jpeg',
                    'url' => 'https://trafficstars.com',
                    'status' => 'Active',
                ],
                [
                    'name' => ['en' => 'ExoClick', 'ru' => 'ExoClick'],
                    'description' => ['en' => 'Innovative advertising platform', 'ru' => 'Инновационная рекламная платформа'],
                    'price' => 'From $25',
                    'image' => '/storage/assets/images/services/3.jpeg',
                    'url' => 'https://exoclick.com',
                    'status' => 'Active',
                ],
            ],
            'affiliate_programs' => [
                [
                    'name' => ['en' => 'CPA.House', 'ru' => 'CPA.House'],
                    'description' => ['en' => 'Premium affiliate network', 'ru' => 'Премиальная партнерская сеть'],
                    'price' => 'Free registration',
                    'image' => '/storage/assets/images/services/4.jpeg',
                    'url' => 'https://cpa.house',
                    'status' => 'Active',
                ],
                [
                    'name' => ['en' => 'AdCombo', 'ru' => 'AdCombo'],
                    'description' => ['en' => 'Global CPA network', 'ru' => 'Глобальная CPA сеть'],
                    'price' => 'Free',
                    'image' => '/storage/assets/images/services/5.jpeg',
                    'url' => 'https://adcombo.com',
                    'status' => 'Active',
                ],
            ],
            'trackers' => [
                [
                    'name' => ['en' => 'Voluum', 'ru' => 'Voluum'],
                    'description' => ['en' => 'Advanced tracking platform', 'ru' => 'Продвинутая платформа трекинга'],
                    'price' => 'From $299/month',
                    'image' => '/storage/assets/images/services/6.jpeg',
                    'url' => 'https://voluum.com',
                    'status' => 'Active',
                ],
                [
                    'name' => ['en' => 'Binom', 'ru' => 'Binom'],
                    'description' => ['en' => 'Self-hosted tracking solution', 'ru' => 'Селф-хостед решение для трекинга'],
                    'price' => '$99/month',
                    'image' => '/storage/assets/images/services/7.jpeg',
                    'url' => 'https://binom.org',
                    'status' => 'Active',
                ],
                [
                    'name' => ['en' => 'RedTrack', 'ru' => 'RedTrack'],
                    'description' => ['en' => 'AI-powered tracking platform', 'ru' => 'Трекинг-платформа с ИИ'],
                    'price' => 'From $199/month',
                    'image' => '/storage/assets/images/services/8.jpeg',
                    'url' => 'https://redtrack.io',
                    'status' => 'Active',
                ],
            ],
            'hosting' => [
                [
                    'name' => ['en' => 'DigitalOcean', 'ru' => 'DigitalOcean'],
                    'description' => ['en' => 'Cloud hosting provider', 'ru' => 'Облачный хостинг провайдер'],
                    'price' => 'From $5/month',
                    'image' => '/storage/assets/images/services/9.jpeg',
                    'url' => 'https://digitalocean.com',
                    'status' => 'Active',
                ],
                [
                    'name' => ['en' => 'Vultr', 'ru' => 'Vultr'],
                    'description' => ['en' => 'High-performance cloud hosting', 'ru' => 'Высокопроизводительный облачный хостинг'],
                    'price' => 'From $2.50/month',
                    'image' => '/storage/assets/images/services/10.jpeg',
                    'url' => 'https://vultr.com',
                    'status' => 'Active',
                ],
            ],
            'domain_registrars' => [
                [
                    'name' => ['en' => 'Namecheap', 'ru' => 'Namecheap'],
                    'description' => ['en' => 'Domain registration and hosting', 'ru' => 'Регистрация доменов и хостинг'],
                    'price' => 'From $8.88/year',
                    'image' => '/storage/assets/images/services/11.jpeg',
                    'url' => 'https://namecheap.com',
                    'status' => 'Active',
                ],
                [
                    'name' => ['en' => 'Cloudflare', 'ru' => 'Cloudflare'],
                    'description' => ['en' => 'Domain registration with protection', 'ru' => 'Регистрация доменов с защитой'],
                    'price' => 'From $9/year',
                    'image' => '/storage/assets/images/services/12.jpeg',
                    'url' => 'https://cloudflare.com',
                    'status' => 'Active',
                ],
            ],
            'spy_services' => [
                [
                    'name' => ['en' => 'SpyTools Pro', 'ru' => 'SpyTools Pro'],
                    'description' => ['en' => 'Professional spy tools suite', 'ru' => 'Профессиональный набор шпионских инструментов'],
                    'price' => '$199/month',
                    'image' => '/storage/assets/images/services/13.jpeg',
                    'url' => 'https://spytools.pro',
                    'status' => 'Active',
                ],
                [
                    'name' => ['en' => 'AdSpy', 'ru' => 'AdSpy'],
                    'description' => ['en' => 'Advertising intelligence platform', 'ru' => 'Платформа рекламной разведки'],
                    'price' => '$149/month',
                    'image' => '/storage/assets/images/services/14.jpeg',
                    'url' => 'https://adspy.com',
                    'status' => 'Active',
                ],
            ],
            'proxy_vpn_services' => [
                [
                    'name' => ['en' => 'Bright Data', 'ru' => 'Bright Data'],
                    'description' => ['en' => 'Enterprise proxy network', 'ru' => 'Корпоративная прокси-сеть'],
                    'price' => 'Custom pricing',
                    'image' => '/storage/assets/images/services/15.jpeg',
                    'url' => 'https://brightdata.com',
                    'status' => 'Active',
                ],
                [
                    'name' => ['en' => 'IPRoyal', 'ru' => 'IPRoyal'],
                    'description' => ['en' => 'Residential and datacenter proxies', 'ru' => 'Резидентские и серверные прокси'],
                    'price' => 'From $2/GB',
                    'image' => '/storage/assets/images/services/16.jpeg',
                    'url' => 'https://iproyal.com',
                    'status' => 'Active',
                ],
                [
                    'name' => ['en' => 'NordVPN', 'ru' => 'NordVPN'],
                    'description' => ['en' => 'Secure VPN service', 'ru' => 'Безопасный VPN сервис'],
                    'price' => 'From $3.99/month',
                    'image' => '/storage/assets/images/services/17.jpeg',
                    'url' => 'https://nordvpn.com',
                    'status' => 'Active',
                ],
            ],
            'anti_detection_browsers' => [
                [
                    'name' => ['en' => 'Dolphin', 'ru' => 'Dolphin'],
                    'description' => ['en' => 'Advanced anti-detection browser', 'ru' => 'Продвинутый антидетект браузер'],
                    'price' => 'From $99/month',
                    'image' => '/storage/assets/images/services/18.jpeg',
                    'url' => 'https://dolphin.ru',
                    'status' => 'Active',
                ],
                [
                    'name' => ['en' => 'Octo Browser', 'ru' => 'Octo Browser'],
                    'description' => ['en' => 'Professional anti-detect browser', 'ru' => 'Профессиональный антидетект браузер'],
                    'price' => 'From $149/month',
                    'image' => '/storage/assets/images/services/19.jpeg',
                    'url' => 'https://octobrowser.net',
                    'status' => 'Active',
                ],
            ],
            'account_purchase_rental' => [
                [
                    'name' => ['en' => 'AccsMarket', 'ru' => 'AccsMarket'],
                    'description' => ['en' => 'Social media accounts marketplace', 'ru' => 'Маркетплейс аккаунтов соцсетей'],
                    'price' => 'Varies',
                    'image' => '/storage/assets/images/services/20.jpeg',
                    'url' => 'https://accsmarket.com',
                    'status' => 'Active',
                ],
                [
                    'name' => ['en' => 'AccFarm', 'ru' => 'AccFarm'],
                    'description' => ['en' => 'Account rental service', 'ru' => 'Сервис аренды аккаунтов'],
                    'price' => 'From $10/day',
                    'image' => '/storage/assets/images/services/21.jpeg',
                    'url' => 'https://accfarm.com',
                    'status' => 'Active',
                ],
            ],
            'app_purchase_rental' => [
                [
                    'name' => ['en' => 'AppRent', 'ru' => 'AppRent'],
                    'description' => ['en' => 'Software rental platform', 'ru' => 'Платформа аренды ПО'],
                    'price' => 'From $29/month',
                    'image' => '/storage/assets/images/services/22.jpeg',
                    'url' => 'https://apprent.io',
                    'status' => 'Active',
                ],
                [
                    'name' => ['en' => 'SoftwarePass', 'ru' => 'SoftwarePass'],
                    'description' => ['en' => 'Software subscription service', 'ru' => 'Сервис подписки на ПО'],
                    'price' => 'From $49/month',
                    'image' => '/storage/assets/images/services/23.jpeg',
                    'url' => 'https://softwarepass.com',
                    'status' => 'Active',
                ],
            ],
            'notification_newsletter_services' => [
                [
                    'name' => ['en' => 'SendGrid', 'ru' => 'SendGrid'],
                    'description' => ['en' => 'Email delivery service', 'ru' => 'Сервис доставки email'],
                    'price' => 'From $14.95/month',
                    'image' => '/storage/assets/images/services/24.jpeg',
                    'url' => 'https://sendgrid.com',
                    'status' => 'Active',
                ],
                [
                    'name' => ['en' => 'Mailchimp', 'ru' => 'Mailchimp'],
                    'description' => ['en' => 'Marketing email platform', 'ru' => 'Платформа email-маркетинга'],
                    'price' => 'Free tier available',
                    'image' => '/storage/assets/images/services/25.jpeg',
                    'url' => 'https://mailchimp.com',
                    'status' => 'Active',
                ],
                [
                    'name' => ['en' => 'PushWoosh', 'ru' => 'PushWoosh'],
                    'description' => ['en' => 'Push notification service', 'ru' => 'Сервис push-уведомлений'],
                    'price' => 'From $49/month',
                    'image' => '/storage/assets/images/services/26.jpeg',
                    'url' => 'https://pushwoosh.com',
                    'status' => 'Active',
                ],
            ],
            'payment_services' => [
                [
                    'name' => ['en' => 'Stripe', 'ru' => 'Stripe'],
                    'description' => ['en' => 'Online payment processing', 'ru' => 'Обработка онлайн-платежей'],
                    'price' => '2.9% + $0.30 per transaction',
                    'image' => '/storage/assets/images/services/27.jpeg',
                    'url' => 'https://stripe.com',
                    'status' => 'Active',
                ],
                [
                    'name' => ['en' => 'PayPal', 'ru' => 'PayPal'],
                    'description' => ['en' => 'Global payment system', 'ru' => 'Глобальная платежная система'],
                    'price' => 'Varies by country',
                    'image' => '/storage/assets/images/services/28.jpeg',
                    'url' => 'https://paypal.com',
                    'status' => 'Active',
                ],
                [
                    'name' => ['en' => 'Wise', 'ru' => 'Wise'],
                    'description' => ['en' => 'International money transfers', 'ru' => 'Международные денежные переводы'],
                    'price' => 'From 0.41%',
                    'image' => '/storage/assets/images/services/29.jpeg',
                    'url' => 'https://wise.com',
                    'status' => 'Active',
                ],
            ],
            'other_services_utilities' => [
                [
                    'name' => ['en' => 'Ahrefs', 'ru' => 'Ahrefs'],
                    'description' => ['en' => 'SEO tools and analytics', 'ru' => 'SEO инструменты и аналитика'],
                    'price' => 'From $99/month',
                    'image' => '/storage/assets/images/services/30.jpeg',
                    'url' => 'https://ahrefs.com',
                    'status' => 'Active',
                ],
                [
                    'name' => ['en' => 'SemRush', 'ru' => 'SemRush'],
                    'description' => ['en' => 'Marketing analytics platform', 'ru' => 'Платформа маркетинговой аналитики'],
                    'price' => 'From $119.95/month',
                    'image' => '/storage/assets/images/services/31.jpeg',
                    'url' => 'https://semrush.com',
                    'status' => 'Active',
                ],
            ],
        ];

        foreach ($categories as $key => $categoryData) {
            $category = ServiceCategories::create([
                'name' => $categoryData['name'],
                'description' => $categoryData['description'],
                'status' => 'Active',
            ]);

            foreach ($mockServices[$key] as $serviceData) {
                $discount = $serviceData['discount'] ?? null;
                Service::create([
                    'name' => $serviceData['name'],
                    'description' => $serviceData['description'],
                    'logo' => $serviceData['image'],
                    'url' => $serviceData['url'],
                    'status' => $serviceData['status'],
                    'category_id' => $category->id,
                    'code' => $discount ? $discount['code'] : strtoupper(Str::random(8)),
                    'code_description' => [
                        'en' => $discount ? $discount['text'] : '10% off',
                        'ru' => $discount ? $discount['text'] : '10% off',
                    ],
                    'is_active_code' => true,
                    'code_valid_from' => now(),
                    'code_valid_until' => now()->addYear(),
                ]);
            }
        }
    }
}
