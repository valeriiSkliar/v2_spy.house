<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SitemapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate 
                            {--section=* : Generate sitemap for specific sections only}
                            {--clear-cache : Clear sitemap cache before generation}
                            {--save-to-file : Save sitemap to public directory}';

    protected $description = 'Generate XML sitemap for the website';

    public function handle(SitemapService $sitemapService): int
    {
        if ($this->option('clear-cache')) {
            $this->clearCache();
        }

        $sections = $this->option('section');

        if (empty($sections)) {
            $this->info('Generating complete sitemap...');
            $xml = $sitemapService->generateXml();
            $filename = 'sitemap.xml';
        } else {
            $this->info('Generating sitemap for sections: '.implode(', ', $sections));
            $xml = $sitemapService->generateXml($sections);
            $filename = 'sitemap-'.implode('-', $sections).'.xml';
        }

        if ($this->option('save-to-file')) {
            $this->saveToFile($xml, $filename);
        }

        $this->info('Sitemap generated successfully!');
        $this->line('URLs found: '.substr_count($xml, '<url>'));

        if (! $this->option('save-to-file')) {
            $this->line('Preview (first 500 characters):');
            $this->line(substr($xml, 0, 500).'...');
        }

        return Command::SUCCESS;
    }

    private function clearCache(): void
    {
        $this->info('Clearing sitemap cache...');

        $cacheKeys = [
            'sitemap_main',
            'sitemap_index',
            'sitemap_section_static',
            'sitemap_section_blog',
            'sitemap_section_services',
            'sitemap_section_landings',
        ];

        foreach ($cacheKeys as $key) {
            cache()->forget($key);
        }

        $this->info('Cache cleared.');
    }

    private function saveToFile(string $xml, string $filename): void
    {
        $path = public_path($filename);
        File::put($path, $xml);
        $this->info("Sitemap saved to: {$path}");
    }
}
