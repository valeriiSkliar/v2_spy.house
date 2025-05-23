<?php

use App\Enums\Frontend\WebSiteDownloadMonitorStatus;
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
        Schema::create('website_download_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->enum('status', WebSiteDownloadMonitorStatus::values())->default(WebSiteDownloadMonitorStatus::PENDING);
            $table->text('error')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('website_download_monitors', function (Blueprint $table) {
            $table->id();
            $table->string('url', 1024);
            $table->string('output_path');
            $table->enum('status', WebSiteDownloadMonitorStatus::values())->default(WebSiteDownloadMonitorStatus::PENDING);
            $table->integer('progress')->default(0);
            $table->text('error')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('url', 'website_download_monitors_url_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_download_monitors');
        Schema::dropIfExists('website_download_notifications');
    }
};
