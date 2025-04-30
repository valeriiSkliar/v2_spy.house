<?php

namespace Database\Seeders;

use App\Models\Frontend\Blog\Author;
use App\Models\Frontend\Blog\BlogPost;
use App\Models\Frontend\Blog\PostCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BlogPostSeeder extends Seeder
{
    public function run()
    {
        // Disable foreign key checks before truncating
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Truncate all related tables
        DB::table('blog_comments')->truncate();
        DB::table('related_posts')->truncate();
        DB::table('blog_post_categories')->truncate();
        DB::table('blog_posts')->truncate();
        DB::table('post_categories')->truncate();
        DB::table('authors')->truncate();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $authors = Author::factory(1)->create();

        // Create main categories
        $webmasterCategory = PostCategory::create([
            'name' => [
                'en' => 'Webmaster',
                'ru' => 'Вебмастеру'
            ],
            'slug' => 'webmaster',
            'parent_id' => null
        ]);

        $arbitrageCategory = PostCategory::create([
            'name' => [
                'en' => 'Arbitrage',
                'ru' => 'Арбитражнику'
            ],
            'slug' => 'arbitrage',
            'parent_id' => null
        ]);

        // Webmaster subcategories
        $webmasterSubcategories = [
            ['en' => 'Monetization', 'ru' => 'Монетизация', 'slug' => 'monetization'],
            ['en' => 'Webmaster Guides', 'ru' => 'Гайды для вебмастеров', 'slug' => 'webmaster-guides'],
            ['en' => 'Useful for Webmaster', 'ru' => 'Полезное для вебмастера', 'slug' => 'useful-for-webmaster']
        ];

        foreach ($webmasterSubcategories as $subcategory) {
            PostCategory::create([
                'name' => [
                    'en' => $subcategory['en'],
                    'ru' => $subcategory['ru']
                ],
                'slug' => $subcategory['slug'],
                'parent_id' => $webmasterCategory->id
            ]);
        }

        // Arbitrage subcategories
        $arbitrageSubcategories = [
            ['en' => 'Copywriting', 'ru' => 'Копирайтинг', 'slug' => 'copywriting'],
            ['en' => 'Creatives', 'ru' => 'Креативы', 'slug' => 'creatives'],
            ['en' => 'Guides', 'ru' => 'Гайды', 'slug' => 'guides'],
            ['en' => 'Training', 'ru' => 'Обучение', 'slug' => 'training'],
            ['en' => 'Cases', 'ru' => 'Кейсы', 'slug' => 'cases'],
            ['en' => 'Useful', 'ru' => 'Полезное', 'slug' => 'useful'],
            ['en' => 'About Service', 'ru' => 'О сервисе', 'slug' => 'about-service']
        ];

        foreach ($arbitrageSubcategories as $subcategory) {
            PostCategory::create([
                'name' => [
                    'en' => $subcategory['en'],
                    'ru' => $subcategory['ru']
                ],
                'slug' => $subcategory['slug'],
                'parent_id' => $arbitrageCategory->id
            ]);
        }

        // Create sample blog posts
        $categories = PostCategory::all();
        foreach ($categories as $category) {
            BlogPost::factory(3)->create([
                'author_id' => $authors->random()->id,
            ])->each(function ($post) use ($category) {
                $post->categories()->attach($category->id);
            });
        }

        // Attach related posts
        BlogPost::all()->each(function ($post) {
            $relatedPosts = BlogPost::where('id', '!=', $post->id)
                ->whereDoesntHave('relatedPosts', function ($query) use ($post) {
                    $query->where('related_post_id', $post->id);
                })
                ->inRandomOrder()
                ->take(3)
                ->get();

            if ($relatedPosts->count() > 0) {
                $post->relatedPosts()->attach($relatedPosts);
            }
        });

        PostCategory::all()->each(function ($category) {
            $category->update([
                'posts_count' => $category->posts()->count()
            ]);
        });
    }
}
