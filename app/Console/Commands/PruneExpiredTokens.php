<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\RefreshToken;
use Carbon\Carbon;

class PruneExpiredTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:prune {--hours=24 : How many hours of expired tokens to keep (for debugging)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune expired access and refresh tokens';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $cutoffHours = $this->option('hours');
        $cutoff = Carbon::now()->subHours($cutoffHours);
        
        // Prune expired access tokens
        $accessTokens = PersonalAccessToken::where('expires_at', '<', $cutoff)->delete();
        $this->info("Deleted {$accessTokens} expired access tokens");
        
        // Prune expired refresh tokens
        $refreshTokens = RefreshToken::where('expires_at', '<', $cutoff)->delete();
        $this->info("Deleted {$refreshTokens} expired refresh tokens");
        
        return Command::SUCCESS;
    }
}