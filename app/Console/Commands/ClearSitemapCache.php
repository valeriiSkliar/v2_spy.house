<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearSitemapCache extends Command
{
    protected $signature = 'sitemap:clear-cache';

    protected $description = 'Clear all sitemap-related cache entries';

    public function handle(): int
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

        $cleared = 0;
        foreach ($cacheKeys as $key) {
            if (Cache::forget($key)) {
                $cleared++;
            }
        }

        $this->info("Cleared {$cleared} sitemap cache entries.");

        return Command::SUCCESS;
    }
}
