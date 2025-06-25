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
        Schema::create('advertisment_networks', function (Blueprint $table) {
            $table->id();
            $table->string('network_name', 126)->unique();
            $table->string('network_display_name', 126)->nullable();
            $table->string('description', 512);
            $table->string('traffic_type_description', 256);
            $table->string('network_url', 512);
            $table->string('network_logo', 512)->nullable();
            $table->boolean('is_active')->default(false);
            $table->integer('total_clicks')->default(0);
            $table->boolean('is_adult')->default(false);
            $table->timestamps();

            // Indexes
            $table->index('network_name');
            $table->index('traffic_type_description');
            $table->index('is_adult');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advertisment_networks');
    }
};
