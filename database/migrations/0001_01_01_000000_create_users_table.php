<?php

use App\Enums\Frontend\UserExperience;
use App\Enums\Frontend\UserScopeOfActivity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('login')->unique()->nullable();
            $table->string('name')->nullable();
            $table->string('surname')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('preferred_locale', 10)->nullable()->comment('User preferred localization, e.g., en, ru');
            $table->string('messenger_type')->comment('Type of messenger, e.g., WhatsApp, Viber, Telegram');
            $table->string('messenger_contact')->comment('Contact of the user in the messenger');
            // $table->string('whatsapp_phone', 20)->nullable()->comment('WhatsApp phone number');
            // $table->string('viber_phone', 20)->nullable()->comment('Viber phone number');
            // $table->string('telegram', 255)->nullable()->comment('Telegram username');
            $table->enum('scope_of_activity', UserScopeOfActivity::names())->nullable()->comment('User\'s business activity');
            $table->enum('experience', UserExperience::names())->nullable()->comment('User\'s experience level');
            $table->text('personal_greeting')->nullable()->comment('For anti-phishing protection');
            $table->json('ip_restrictions')->nullable()->comment('Allowed IP addresses');
            $table->boolean('google_2fa_enabled')->default(false)->comment('2FA status');
            $table->text('google_2fa_secret')->nullable()->comment('2FA secret key');
            $table->date('date_of_birth')->nullable()->comment('User\'s date of birth');
            $table->string('user_avatar', 255)->nullable()->comment('User\'s avatar');
            $table->string('email_contact_id')->nullable()->comment('Email service contact ID for newsletters');
            $table->boolean('is_newsletter_subscribed')->default(false)->comment('Newsletter subscription status');
            $table->string('unsubscribe_hash')->unique()->nullable()->comment('Unique hash for newsletter unsubscribe');

            // Financial system fields
            $table->boolean('is_trial_period')->default(false)->comment('Flag for 7-day trial period activation');
            $table->boolean('trial_period_used')->default(false)->comment('Flag indicating if trial period was ever used');
            $table->decimal('available_balance', 10, 2)->default(0.00)->comment('User available balance for payments');
            $table->unsignedBigInteger('subscription_id')->nullable()->comment('Current active subscription');
            $table->timestamp('subscription_time_start')->nullable()->comment('Current subscription start time');
            $table->timestamp('subscription_time_end')->nullable()->comment('Current subscription end time');
            $table->boolean('subscription_is_expired')->default(false)->comment('Current subscription expiration status');
            $table->unsignedBigInteger('queued_subscription_id')->nullable()->comment('Queued subscription for activation');
            $table->integer('balance_version')->default(1)->comment('Version for optimistic locking on balance operations');

            $table->rememberToken();
            $table->timestamps();

            // Add unique constraint for messenger_type + messenger_contact combination
            $table->unique(['messenger_type', 'messenger_contact']);

            // Indexes for financial queries optimization
            $table->index(['subscription_id', 'subscription_is_expired'], 'users_subscription_status_idx');
            $table->index('balance_version', 'users_balance_version_idx');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
