<?php

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
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->string('request_ip', 45)->nullable()->after('token');
            $table->string('access_ip', 45)->nullable()->after('request_ip');
            $table->timestamp('used_at')->nullable()->after('access_ip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('password_reset_tokens', function (Blueprint $table) {
            $table->dropColumn(['request_ip', 'access_ip', 'used_at']);
        });
    }
};
