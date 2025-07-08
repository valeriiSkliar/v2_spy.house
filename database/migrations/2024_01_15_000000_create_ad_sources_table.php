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
        Schema::create('ad_sources', function (Blueprint $table) {
            $table->id();
            $table->string('source_name', 50)->unique()->comment('Системное имя источника');
            $table->string('source_display_name', 100)->comment('Отображаемое имя источника');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_sources');
    }
};
