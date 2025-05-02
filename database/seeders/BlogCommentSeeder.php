<?php

namespace Database\Seeders;

use App\Models\Frontend\Blog\BlogComment;
use App\Models\Frontend\Blog\BlogPost;
use Illuminate\Database\Seeder;

class BlogCommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all blog posts
        $posts = BlogPost::all();

        foreach ($posts as $post) {
            // Create 7-15 parent comments for each post
            $parentComments = BlogComment::factory()
                ->count(rand(7, 15))
                ->approved()
                ->create([
                    'post_id' => $post->id,
                ]);

            // For each parent comment, create 1-5 replies
            foreach ($parentComments as $parentComment) {
                $replyCount = rand(1, 5);
                if ($replyCount > 0) {
                    BlogComment::factory()
                        ->count($replyCount)
                        ->approved()
                        ->create([
                            'post_id' => $post->id,
                            'parent_id' => $parentComment->id,
                        ]);
                }
            }
        }
    }
}
