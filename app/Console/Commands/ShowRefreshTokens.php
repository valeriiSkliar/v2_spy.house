<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\RefreshToken;

class ShowRefreshTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:show {user_id? : The ID of the user to show tokens for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display refresh tokens for testing and debugging';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        
        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return Command::FAILURE;
            }
            
            $refreshTokens = RefreshToken::where('user_id', $userId)->get();
        } else {
            $refreshTokens = RefreshToken::with('user')->get();
        }
        
        if ($refreshTokens->isEmpty()) {
            $this->info("No refresh tokens found.");
            return Command::SUCCESS;
        }
        
        $this->info("Refresh Tokens:");
        $this->table(
            ['ID', 'User', 'Created At', 'Expires At', 'Status'],
            $refreshTokens->map(function ($token) {
                return [
                    'id' => $token->id,
                    'user' => $token->user ? $token->user->name . ' (ID: ' . $token->user_id . ')' : 'User ID: ' . $token->user_id,
                    'created' => $token->created_at->format('Y-m-d H:i:s'),
                    'expires' => $token->expires_at->format('Y-m-d H:i:s'),
                    'status' => $token->expires_at > now() ? 'Valid' : 'Expired'
                ];
            })
        );
        
        return Command::SUCCESS;
    }
}