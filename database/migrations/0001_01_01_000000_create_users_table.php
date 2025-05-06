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
            $table->string('login')->nullable();
            $table->string('name')->nullable();
            $table->string('surname')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('preferred_locale', 10)->nullable()->comment('User preferred localization, e.g., en, ru');
            $table->string('whatsapp_phone', 20)->nullable()->comment('WhatsApp phone number');
            $table->string('viber_phone', 20)->nullable()->comment('Viber phone number');
            $table->string('telegram', 255)->nullable()->comment('Telegram username');
            $table->enum('scope_of_activity', UserScopeOfActivity::values())->nullable()->comment('User\'s business activity');
            $table->enum('experience', UserExperience::values())->nullable()->comment('User\'s experience level');
            $table->text('personal_greeting')->nullable()->comment('For anti-phishing protection');
            $table->json('ip_restrictions')->nullable()->comment('Allowed IP addresses');
            $table->boolean('google_2fa_enabled')->default(false)->comment('2FA status');
            $table->text('google_2fa_secret')->nullable()->comment('2FA secret key');
            $table->date('date_of_birth')->nullable()->comment('User\'s date of birth');
            $table->string('user_avatar', 255)->nullable()->comment('User\'s avatar');
            $table->rememberToken();
            $table->timestamps();
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
