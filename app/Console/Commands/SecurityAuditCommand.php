<?php

namespace App\Console\Commands;

use App\Services\SecurityAuditService;
use Illuminate\Console\Command;

class SecurityAuditCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:audit 
                            {action : Action to perform (report|cleanup)}
                            {--days=7 : Number of days for report or cleanup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Security audit for password reset functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $days = (int) $this->option('days');

        switch ($action) {
            case 'report':
                $this->generateReport($days);
                break;
            case 'cleanup':
                $this->cleanup($days);
                break;
            default:
                $this->error('Invalid action. Use "report" or "cleanup".');
        }
    }

    private function generateReport(int $days)
    {
        $this->info("Security Audit Report for the last {$days} days");
        $this->info(str_repeat('=', 50));

        $activity = SecurityAuditService::getSuspiciousActivity($days);

        // Множественные запросы с одного IP
        if ($activity['multiple_requests_from_ip']->isNotEmpty()) {
            $this->warn("\nMultiple password reset requests from same IP:");
            $this->table(
                ['IP Address', 'Number of Different Emails'],
                $activity['multiple_requests_from_ip']->map(function ($item) {
                    return [$item->request_ip, $item->email_count];
                })->toArray()
            );
        } else {
            $this->info("\nNo suspicious multiple requests from same IP detected.");
        }

        // IP несовпадения
        $this->info("\nIP Address Mismatches: ".$activity['ip_mismatches']);

        // Неиспользованные токены
        $this->info('Unused tokens (older than 2 hours): '.$activity['unused_tokens']);

        // Успешные сбросы паролей
        $this->info('Successful password resets: '.$activity['successful_resets']);
    }

    private function cleanup(int $days)
    {
        $this->info("Cleaning up records older than {$days} days...");

        $deleted = SecurityAuditService::cleanupOldRecords($days);

        $this->info("Deleted {$deleted} old password reset records.");
    }
}
