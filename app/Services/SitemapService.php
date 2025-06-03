<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Frontend\Blog\BlogPost;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

class SitemapService
{
    private array $generators = [];

    public function __construct()
    {
        $this->registerDefaultGenerators();
    }

    /**
     * Register a sitemap generator for a specific section
     */
    public function registerGenerator(string $section, callable $generator): void
    {
        $this->generators[$section] = $generator;
    }

    /**
     * Generate XML sitemap for specific sections or all sections
     */
    public function generateXml(?array $sections = null): string
    {
        $urls = $this->getUrls($sections);

        return $this->buildXml($urls);
    }

    /**
     * Generate sitemap index XML for multiple sitemaps
     */
    public function generateSitemapIndex(array $sitemaps): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        foreach ($sitemaps as $sitemap) {
            $xml .= '  <sitemap>' . PHP_EOL;
            $xml .= '    <loc>' . htmlspecialchars($sitemap['url']) . '</loc>' . PHP_EOL;
            if (isset($sitemap['lastmod'])) {
                $xml .= '    <lastmod>' . $sitemap['lastmod'] . '</lastmod>' . PHP_EOL;
            }
            $xml .= '  </sitemap>' . PHP_EOL;
        }

        $xml .= '</sitemapindex>';

        return $xml;
    }

    /**
     * Get URLs for specified sections
     */
    public function getUrls(?array $sections = null): Collection
    {
        $urls = collect();

        $sectionsToProcess = $sections ?? array_keys($this->generators);

        foreach ($sectionsToProcess as $section) {
            if (isset($this->generators[$section])) {
                $sectionUrls = call_user_func($this->generators[$section]);
                $urls = $urls->merge($sectionUrls);
            }
        }

        return $urls;
    }

    /**
     * Register default sitemap generators
     */
    private function registerDefaultGenerators(): void
    {
        // Static pages generator
        $this->registerGenerator('static', function () {
            return collect([
                [
                    'url' => URL::to('/'),
                    'lastmod' => now()->format('Y-m-d'),
                    'changefreq' => 'daily',
                    'priority' => '1.0',
                ],
                [
                    'url' => URL::to('/terms'),
                    'lastmod' => now()->format('Y-m-d'),
                    'changefreq' => 'monthly',
                    'priority' => '0.5',
                ],
            ]);
        });

        // Blog generator (ready for future implementation)
        $this->registerGenerator('blog', function () {
            $urls = collect();

            // Add blog index page
            $urls->push([
                'url' => URL::to('/blog'),
                'lastmod' => now()->format('Y-m-d'),
                'changefreq' => 'daily',
                'priority' => '0.8',
            ]);

            $posts = BlogPost::published()->get();
            foreach ($posts as $post) {
                $urls->push([
                    'url' => URL::to('/blog/' . $post->slug),
                    'lastmod' => $post->updated_at->format('Y-m-d'),
                    'changefreq' => 'monthly',
                    'priority' => '0.6'
                ]);
            }

            return $urls;
        });

        // Services generator
        $this->registerGenerator('services', function () {
            $urls = collect();

            // Add services index page
            $urls->push([
                'url' => URL::to('/services'),
                'lastmod' => now()->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ]);

            // TODO: Add individual services when needed
            // Example:
            // $services = \App\Models\Service::active()->get();
            // foreach ($services as $service) {
            //     $urls->push([
            //         'url' => URL::to('/services/' . $service->slug),
            //         'lastmod' => $service->updated_at->format('Y-m-d'),
            //         'changefreq' => 'weekly',
            //         'priority' => '0.7'
            //     ]);
            // }

            return $urls;
        });

        // Landings generator
        $this->registerGenerator('landings', function () {
            $urls = collect();

            // TODO: Add landing pages when needed
            // Example:
            // $landings = \App\Models\Landing::published()->get();
            // foreach ($landings as $landing) {
            //     $urls->push([
            //         'url' => URL::to('/landing/' . $landing->slug),
            //         'lastmod' => $landing->updated_at->format('Y-m-d'),
            //         'changefreq' => 'monthly',
            //         'priority' => '0.7'
            //     ]);
            // }

            return $urls;
        });
    }

    /**
     * Build XML from URLs collection
     */
    private function buildXml(Collection $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        foreach ($urls as $url) {
            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . htmlspecialchars($url['url']) . '</loc>' . PHP_EOL;

            if (isset($url['lastmod'])) {
                $xml .= '    <lastmod>' . $url['lastmod'] . '</lastmod>' . PHP_EOL;
            }

            if (isset($url['changefreq'])) {
                $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . PHP_EOL;
            }

            if (isset($url['priority'])) {
                $xml .= '    <priority>' . $url['priority'] . '</priority>' . PHP_EOL;
            }

            $xml .= '  </url>' . PHP_EOL;
        }

        $xml .= '</urlset>';

        return $xml;
    }
}
