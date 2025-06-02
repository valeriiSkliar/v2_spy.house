<?php

namespace App\Console\Commands;

use App\Listeners\DebugRegistrationListener;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Console\Command;

class TestRegistrationDebug extends Command
{
    protected $signature = 'debug:registration-test {--reset : Reset call counter}';
    protected $description = 'Test registration debug listener';

    public function handle(): int
    {
        if ($this->option('reset')) {
            DebugRegistrationListener::resetCallCount();
            $this->info('Call counter reset to 0');
            return 0;
        }

        $this->info('Testing registration debug listener...');
        $this->info('Current call count: ' . DebugRegistrationListener::getCallCount());

        // Создаем тестового пользователя
        $user = new User([
            'id' => 999,
            'name' => 'Test Debug User',
            'email' => 'debug@test.com',
            'created_at' => now(),
        ]);

        // Запускаем событие
        event(new Registered($user));

        $this->info('Event fired. New call count: ' . DebugRegistrationListener::getCallCount());
        $this->info('Check logs for detailed output');

        return 0;
    }
}
