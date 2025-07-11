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
        Schema::create('main_page_comments', function (Blueprint $table) {
            $table->id();
            $table->json('heading');
            $table->json('user_position');
            $table->json('user_name');
            $table->string('thumbnail_src')->nullable();
            $table->string('email');
            $table->json('text');
            $table->json('content');
            $table->integer('rating')->default(0);
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('main_page_comments');
    }
};
