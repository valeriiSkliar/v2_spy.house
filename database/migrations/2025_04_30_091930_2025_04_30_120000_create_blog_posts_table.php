<?php

use App\Enums\Frontend\CommentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create authors table
        Schema::create('authors', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('avatar')->nullable();
            $table->json('bio')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            // Add index for faster default author lookup
            $table->index('is_default');
        });

        // Create post categories table
        Schema::create('post_categories', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('slug')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('post_categories')->nullOnDelete();
            $table->integer('posts_count')->default(0);
            $table->unsignedInteger('_lft')->default(0);
            $table->unsignedInteger('_rgt')->default(0);
            $table->timestamps();
        });

        // Add generated columns for JSON fields in post_categories
        DB::statement('ALTER TABLE post_categories ADD COLUMN name_en VARCHAR(255) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(name, "$.en"))) STORED');
        DB::statement('ALTER TABLE post_categories ADD COLUMN name_ru VARCHAR(255) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(name, "$.ru"))) STORED');

        // Add indexes on generated columns in post_categories
        DB::statement('CREATE INDEX idx_name_en ON post_categories (name_en)');
        DB::statement('CREATE INDEX idx_name_ru ON post_categories (name_ru)');

        // Create blog posts table
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('summary');
            $table->json('content');
            $table->string('slug')->unique();
            $table->integer('views_count')->default(0);
            $table->foreignId('author_id')->constrained()->cascadeOnDelete();
            $table->string('featured_image')->nullable();
            $table->boolean('is_published')->default(false);
            $table->decimal('average_rating', 2, 1)->nullable()->default(null);
            $table->timestamps();

            // Add indexes for performance
            $table->index('views_count');
            $table->index('is_published');
            $table->index('created_at');
            $table->index('average_rating');
        });

        // Add generated columns for JSON fields in blog_posts
        DB::statement('ALTER TABLE blog_posts ADD COLUMN title_en VARCHAR(255) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(title, "$.en"))) STORED');
        DB::statement('ALTER TABLE blog_posts ADD COLUMN title_ru VARCHAR(255) GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(title, "$.ru"))) STORED');

        // Add indexes on generated columns in blog_posts
        DB::statement('CREATE INDEX idx_title_en ON blog_posts (title_en)');
        DB::statement('CREATE INDEX idx_title_ru ON blog_posts (title_ru)');

        // Create blog comments table
        Schema::create('blog_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('blog_posts')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('blog_comments')->nullOnDelete();
            $table->string('author_name');
            $table->string('email');
            $table->string('avatar')->nullable();
            $table->text('content');
            $table->integer('rating')->nullable();
            $table->boolean('is_spam')->default(false);
            $table->enum('status', CommentStatus::values())->default(CommentStatus::PENDING->value);
            $table->timestamps();
        });

        // Create post_category pivot table
        Schema::create('post_category', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained('blog_posts')->cascadeOnDelete();
            $table->foreignId('post_category_id')->constrained()->cascadeOnDelete();
            $table->primary(['post_id', 'post_category_id']);
        });

        // Create related posts table
        Schema::create('related_posts', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained('blog_posts')->cascadeOnDelete();
            $table->foreignId('related_post_id')->constrained('blog_posts')->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->primary(['post_id', 'related_post_id']);
        });

        // Create blog post categories table
        Schema::create('blog_post_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('blog_posts')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('post_categories')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['post_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_post_categories');
        Schema::dropIfExists('related_posts');
        Schema::dropIfExists('post_category');
        Schema::dropIfExists('blog_comments');
        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('post_categories');
        Schema::dropIfExists('authors');
    }
};
