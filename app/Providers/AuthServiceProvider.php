<?php

namespace App\Providers;

use App\Models\Frontend\Landings\WebsiteDownloadMonitor;
use App\Policies\Frontend\WebsiteDownloadMonitorPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        WebsiteDownloadMonitor::class => WebsiteDownloadMonitorPolicy::class,
    ];
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
