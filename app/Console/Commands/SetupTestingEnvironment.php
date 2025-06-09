<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SetupTestingEnvironment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:setup 
                            {--fresh : Drop all tables and re-run all migrations}
                            {--seed : Run the testing seeder after migration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup testing environment with SQLite database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up testing environment...');

        // Check if we're in testing environment
        if (!app()->environment('testing')) {
            $this->warn('This command should be run in testing environment.');
            if (!$this->confirm('Do you want to continue anyway?', false)) {
                return 1;
            }
        }

        // Create database directory if it doesn't exist
        $databasePath = database_path();
        if (!File::exists($databasePath)) {
            File::makeDirectory($databasePath, 0755, true);
            $this->info('Created database directory.');
        }

        // Setup SQLite database file for testing (not in-memory)
        $testDbPath = database_path('testing.sqlite');
        if (!File::exists($testDbPath)) {
            File::put($testDbPath, '');
            $this->info('Created testing SQLite database file.');
        }

        // Run migrations
        if ($this->option('fresh')) {
            $this->info('Running fresh migrations...');
            Artisan::call('migrate:fresh', [
                '--database' => 'sqlite',
                '--force' => true,
            ]);
        } else {
            $this->info('Running migrations...');
            Artisan::call('migrate', [
                '--database' => 'sqlite',
                '--force' => true,
            ]);
        }

        $this->info('Migrations completed successfully.');

        // Run seeder if requested
        if ($this->option('seed')) {
            $this->info('Running testing seeder...');
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\TestingSeeder',
                '--database' => 'sqlite',
                '--force' => true,
            ]);
            $this->info('Testing seeder completed successfully.');
        }

        $this->info('🧪 Testing environment setup completed!');
        $this->line('');
        $this->line('You can now run tests with: <comment>php artisan test</comment>');
        $this->line('For parallel testing: <comment>php artisan test --parallel</comment>');
        $this->line('');

        return 0;
    }
}
