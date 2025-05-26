<?php

namespace App\Console\Commands;

use Anhskohbo\NoCaptcha\Facades\NoCaptcha;
use Illuminate\Console\Command;

class TestRecaptcha extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recaptcha:test {token?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test reCAPTCHA configuration and token verification';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing reCAPTCHA configuration...');

        // Проверяем конфигурацию
        $sitekey = config('captcha.sitekey');
        $secret = config('captcha.secret');

        if (empty($sitekey)) {
            $this->error('❌ NOCAPTCHA_SITEKEY not configured');
            return 1;
        }

        if (empty($secret)) {
            $this->error('❌ NOCAPTCHA_SECRET not configured');
            return 1;
        }

        $this->info("✅ Site Key: {$sitekey}");
        $this->info("✅ Secret Key: " . str_repeat('*', strlen($secret) - 8) . substr($secret, -8));

        // Тестируем токен если предоставлен
        $token = $this->argument('token');
        if ($token) {
            $this->info("\nTesting token verification...");

            try {
                $result = NoCaptcha::verifyResponse($token, '127.0.0.1');

                if ($result) {
                    $this->info('✅ Token verification successful');
                } else {
                    $this->error('❌ Token verification failed');
                }
            } catch (\Exception $e) {
                $this->error("❌ Error during verification: {$e->getMessage()}");
                return 1;
            }
        } else {
            $this->warn('💡 To test token verification, provide a token: php artisan recaptcha:test YOUR_TOKEN');
        }

        $this->info("\n🎉 reCAPTCHA configuration test completed!");
        return 0;
    }
}
