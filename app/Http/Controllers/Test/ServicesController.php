<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServicesController extends Controller
{
    /**
     * Display all available services
     */
    public function index(Request $request)
    {
        // Get all services
        $services = $this->getServices();

        // Get all categories for filter
        $categories = $this->getCategories();

        // Apply filters if any
        if ($request->has('category')) {
            $categoryId = $request->category;
            $services = collect($services)->filter(function ($service) use ($categoryId) {
                return $service['category_id'] == $categoryId;
            })->values()->all();
        }

        if ($request->has('search')) {
            $search = strtolower($request->search);
            $services = collect($services)->filter(function ($service) use ($search) {
                return str_contains(strtolower($service['name']), $search) ||
                    str_contains(strtolower($service['description']), $search);
            })->values()->all();
        }

        if ($request->has('sort')) {
            $sort = $request->sort;
            if ($sort == 'views-high') {
                $services = collect($services)->sortByDesc('views')->values()->all();
            } elseif ($sort == 'views-low') {
                $services = collect($services)->sortBy('views')->values()->all();
            } elseif ($sort == 'rating-high') {
                $services = collect($services)->sortByDesc('rating')->values()->all();
            } elseif ($sort == 'rating-low') {
                $services = collect($services)->sortBy('rating')->values()->all();
            } elseif ($sort == 'transitions-high') {
                $services = collect($services)->sortByDesc('transitions')->values()->all();
            } elseif ($sort == 'transitions-low') {
                $services = collect($services)->sortBy('transitions')->values()->all();
            }
        }

        return view('services.index', [
            'services' => $services,
            'categories' => $categories,
        ]);
    }

    /**
     * Display a specific service
     */
    public function show($id)
    {
        $service = collect($this->getServices())->firstWhere('id', $id);

        if (! $service) {
            abort(404);
        }

        // Get related services (for "Offers from other companies" section)
        $relatedServices = collect($this->getServices())
            ->filter(function ($item) use ($service) {
                return $item['id'] != $service['id'] && $item['category_id'] == $service['category_id'];
            })
            ->take(5)
            ->values()
            ->all();

        return view('services.show', [
            'service' => $service,
            'relatedServices' => $relatedServices,
        ]);
    }

    /**
     * Rate a service
     */
    public function rate(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|numeric|min:1|max:5',
        ]);

        // In a real app, you would save the rating in the database

        return response()->json([
            'success' => true,
            'message' => 'Rating saved successfully',
            'rating' => $request->rating,
        ]);
    }

    /**
     * Mock data for services
     */
    private function getServices()
    {
        return [
            [
                'id' => 1,
                'name' => 'Serpstat',
                'slug' => 'serpstat',
                'description' => 'Многофункциональная SEO-платформа',
                'full_description' => 'Serpstat — многофункциональная SEO-платформа для профессионалов. С помощью платформы специалисты могут анализировать сайты, строить стратегии продвижения, подбирать ключевые слова и многое другое.',
                'image' => 'https://spy.house/files/services_images/1.jpeg',
                'site_url' => 'https://serpstat.com',
                'promo_code' => 'spyhouse',
                'discount' => '10%',
                'category_id' => 1,
                'views' => 12500,
                'transitions' => 1400,
                'rating' => 4.7,
            ],
            [
                'id' => 2,
                'name' => 'Sem [2seo.pro]',
                'slug' => 'sem-2seo-pro',
                'description' => 'Создание семантических ядер под ключ и продажа готовых семантических ядер',
                'full_description' => 'Sem [2seo.pro] — сервис для создания семантических ядер под ключ и продажа готовых семантических ядер. Профессиональные SEO-специалисты помогут собрать релевантное семантическое ядро для вашего сайта.',
                'image' => 'https://spy.house/files/services_images/2.jpeg',
                'site_url' => 'https://2seo.pro',
                'promo_code' => 'spyhouse',
                'discount' => '15%',
                'category_id' => 1,
                'views' => 9800,
                'transitions' => 950,
                'rating' => 4.5,
            ],
            [
                'id' => 3,
                'name' => 'Yagla',
                'slug' => 'yagla',
                'description' => 'Подмена контента под ключевые фразы и таргетинги',
                'full_description' => 'Yagla — сервис для подмены контента под ключевые фразы и таргетинги. Платформа помогает увеличить конверсию за счет динамической подмены контента в зависимости от источника трафика и запросов пользователей.',
                'image' => 'https://spy.house/files/services_images/3.jpeg',
                'site_url' => 'https://yagla.ru',
                'promo_code' => 'spyhouse',
                'discount' => '20%',
                'category_id' => 2,
                'views' => 7500,
                'transitions' => 890,
                'rating' => 4.6,
            ],
            [
                'id' => 4,
                'name' => 'Keys.so',
                'slug' => 'keys-so',
                'description' => 'Сервис для анализа сайтов и запросов конкурентов в поиске и в контексте',
                'full_description' => 'Keys.so — сервис анализа конкурентов в SEO и PPC. С помощью платформы специалисты могут составлять списки конкурентов своего сайта, подбирать ключевые запросы для продвижения ресурса или контекстной рекламы. Партнеркин сделает подробный обзор сервиса и рассмотрит полный функционал площадки. Keys.so — сервис анализа конкурентов в SEO и PPC. С помощью платформы специалисты могут составлять списки конкурентов своего сайта, подбирать ключевые запросы для продвижения ресурса или контекстной рекламы. Партнеркин сделает подробный обзор сервиса и рассмотрит полный функционал площадки.',
                'image' => 'https://spy.house/files/services_images/4.jpeg',
                'site_url' => 'https://keys.so',
                'promo_code' => 'spyhouse',
                'discount' => '10%',
                'category_id' => 1,
                'views' => 12500,
                'transitions' => 1400,
                'rating' => 4.7,
            ],
            [
                'id' => 5,
                'name' => 'Trastik',
                'slug' => 'trastik',
                'description' => 'Вечные ссылки и статьи для продвижения сайтов любой тематики',
                'full_description' => 'Trastik — биржа вечных ссылок и статей для продвижения сайтов любой тематики. Сервис позволяет размещать статьи и ссылки на качественных площадках с хорошими показателями.',
                'image' => 'https://spy.house/files/services_images/5.jpeg',
                'site_url' => 'https://trastik.ru',
                'promo_code' => 'spyhouse',
                'discount' => '15%',
                'category_id' => 3,
                'views' => 8900,
                'transitions' => 1200,
                'rating' => 4.4,
            ],
            [
                'id' => 6,
                'name' => 'SpyWords',
                'slug' => 'spywords',
                'description' => 'Анализ конкурентов в контексте и поиске; сбор семантики',
                'full_description' => 'SpyWords — платформа для анализа конкурентов в контексте и поиске. Сервис позволяет изучать стратегии конкурентов, эффективно собирать семантику и определять наиболее перспективные направления продвижения.',
                'image' => 'https://spy.house/files/services_images/6.jpeg',
                'site_url' => 'https://spywords.ru',
                'promo_code' => 'spyhouse',
                'discount' => '10%',
                'category_id' => 1,
                'views' => 10200,
                'transitions' => 990,
                'rating' => 4.5,
            ],
            [
                'id' => 7,
                'name' => 'Rankinity',
                'slug' => 'rankinity',
                'description' => 'Проверка позиций сайта в реальном времени',
                'full_description' => 'Rankinity — сервис для проверки позиций сайта в реальном времени. Платформа позволяет отслеживать позиции сайта по ключевым запросам, анализировать динамику, получать уведомления об изменениях и формировать детальные отчеты.',
                'image' => 'https://spy.house/files/services_images/7.jpeg',
                'site_url' => 'https://rankinity.com',
                'promo_code' => 'spyhouse',
                'discount' => '25%',
                'category_id' => 1,
                'views' => 7600,
                'transitions' => 820,
                'rating' => 4.3,
            ],
            [
                'id' => 8,
                'name' => 'MOAB',
                'slug' => 'moab',
                'description' => 'Сбор семантики; перенос рекламных кампаний; увеличение конверсии',
                'full_description' => 'MOAB — сервис для сбора семантики, переноса рекламных кампаний и увеличения конверсии. Платформа предоставляет инструменты для анализа семантического ядра, эффективной настройки рекламных кампаний и оптимизации конверсии.',
                'image' => 'https://spy.house/files/services_images/8.jpeg',
                'site_url' => 'https://moab.pro',
                'promo_code' => 'spyhouse',
                'discount' => '15%',
                'category_id' => 2,
                'views' => 8300,
                'transitions' => 950,
                'rating' => 4.4,
            ],
            [
                'id' => 9,
                'name' => 'CheckTrust',
                'slug' => 'checktrust',
                'description' => 'Сервис оценки качества сайтов и ссылочных доноров',
                'full_description' => 'CheckTrust — сервис для оценки качества сайтов и ссылочных доноров. Платформа анализирует ключевые метрики сайтов, помогает оценить качество ссылочных доноров и выбрать наиболее эффективные площадки для размещения.',
                'image' => 'https://spy.house/files/services_images/9.jpeg',
                'site_url' => 'https://checktrust.ru',
                'promo_code' => 'spyhouse',
                'discount' => '10%',
                'category_id' => 3,
                'views' => 9100,
                'transitions' => 1100,
                'rating' => 4.6,
            ],
            [
                'id' => 10,
                'name' => 'SEO-reports',
                'slug' => 'seo-reports',
                'description' => 'Отчеты для SEO и рекламы',
                'full_description' => 'SEO-reports — сервис для создания отчетов для SEO и рекламы. Платформа помогает формировать понятные и информативные отчеты по SEO и рекламным кампаниям, которые можно использовать для анализа и презентации результатов.',
                'image' => 'https://spy.house/files/services_images/10.jpeg',
                'site_url' => 'https://seo-reports.ru',
                'promo_code' => 'spyhouse',
                'discount' => '20%',
                'category_id' => 1,
                'views' => 6800,
                'transitions' => 730,
                'rating' => 4.2,
            ],
        ];
    }

    /**
     * Mock data for categories
     */
    private function getCategories()
    {
        return [
            [
                'id' => 1,
                'name' => 'Advertising Networks',
                'slug' => 'advertising-networks',
            ],
            [
                'id' => 2,
                'name' => 'Affiliate Programs',
                'slug' => 'affiliate-programs',
            ],
            [
                'id' => 3,
                'name' => 'Trackers',
                'slug' => 'trackers',
            ],
            [
                'id' => 4,
                'name' => 'Hosting',
                'slug' => 'hosting',
            ],
            [
                'id' => 5,
                'name' => 'Domain Registrars',
                'slug' => 'domain-registrars',
            ],
            [
                'id' => 6,
                'name' => 'SPY Services',
                'slug' => 'spy-services',
            ],
            [
                'id' => 7,
                'name' => 'Proxy and VPN Services',
                'slug' => 'proxy-vpn-services',
            ],
            [
                'id' => 8,
                'name' => 'Anti-detection Browsers',
                'slug' => 'anti-detection-browsers',
            ],
            [
                'id' => 9,
                'name' => 'Account Purchase and Rental',
                'slug' => 'account-purchase-rental',
            ],
            [
                'id' => 10,
                'name' => 'Purchase and Rental of Applications',
                'slug' => 'application-purchase-rental',
            ],
            [
                'id' => 11,
                'name' => 'Notification and Newsletter Services',
                'slug' => 'notification-newsletter-services',
            ],
            [
                'id' => 12,
                'name' => 'Payment Services',
                'slug' => 'payment-services',
            ],
            [
                'id' => 13,
                'name' => 'Other Services and Utilities',
                'slug' => 'other-services-utilities',
            ],
        ];
    }
}
