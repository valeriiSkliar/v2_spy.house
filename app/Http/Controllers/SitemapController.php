<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SitemapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

class SitemapController extends Controller
{
    public function __construct(
        private readonly SitemapService $sitemapService
    ) {}

    /**
     * Generate main sitemap or sitemap index
     */
    public function index(): Response
    {
        $xml = Cache::remember('sitemap_main', 3600, function () {
            // Check if we need to generate a sitemap index or single sitemap
            $sections = ['static', 'blog', 'services', 'landings'];

            // For now, generate a single sitemap with all sections
            // In the future, you can modify this logic to generate separate sitemaps
            // and return a sitemap index instead
            return $this->sitemapService->generateXml($sections);
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    /**
     * Generate sitemap for specific section
     */
    public function section(string $section): Response
    {
        $xml = Cache::remember("sitemap_section_{$section}", 3600, function () use ($section) {
            return $this->sitemapService->generateXml([$section]);
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    /**
     * Generate sitemap index (for future use with multiple sitemaps)
     */
    public function sitemapIndex(): Response
    {
        $xml = Cache::remember('sitemap_index', 3600, function () {
            $sitemaps = [
                [
                    'url' => URL::to('/sitemap/static.xml'),
                    'lastmod' => now()->format('Y-m-d\TH:i:sP'),
                ],
                [
                    'url' => URL::to('/sitemap/blog.xml'),
                    'lastmod' => now()->format('Y-m-d\TH:i:sP'),
                ],
                [
                    'url' => URL::to('/sitemap/services.xml'),
                    'lastmod' => now()->format('Y-m-d\TH:i:sP'),
                ],
                [
                    'url' => URL::to('/sitemap/landings.xml'),
                    'lastmod' => now()->format('Y-m-d\TH:i:sP'),
                ],
            ];

            return $this->sitemapService->generateSitemapIndex($sitemaps);
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    /**
     * Clear sitemap cache
     */
    public function clearCache(): JsonResponse
    {
        Cache::forget('sitemap_main');
        Cache::forget('sitemap_index');

        // Clear section caches
        $sections = ['static', 'blog', 'services', 'landings'];
        foreach ($sections as $section) {
            Cache::forget("sitemap_section_{$section}");
        }

        return response()->json(['message' => 'Sitemap cache cleared successfully']);
    }
}
