<?php

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Test\BlogController as BlogControllerTest;


class ApiBlogController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $limit = $request->get('limit', 5); // Limit for live search results

        // Reuse the existing getArticles method from BlogController
        $blogController = new BlogControllerTest();
        $articles = $blogController->getArticles();

        // Filter articles by search query
        $filtered = collect($articles)->filter(function ($article) use ($query) {
            // Case-insensitive search in title, excerpt, and content
            return stripos($article['title'], $query) !== false ||
                (isset($article['excerpt']) && stripos($article['excerpt'], $query) !== false) ||
                (isset($article['content']) && stripos($article['content'], $query) !== false);
        });

        $totalResults = $filtered->count();

        // Format results for the view, taking the limit for live search
        $results = $filtered->take($limit)->map(function ($article) {
            return [
                'id' => $article['id'],
                'title' => $article['title'],
                'slug' => $article['slug'],
                'image' => $article['image'] ?? asset('images/default-article.jpg'), // Add default image
                'date' => $article['date'],
                'views' => $article['views'] ?? 0,
                'rating' => $article['rating'] ?? 0,
                'category' => $article['category'] ?? ['name' => 'Uncategorized', 'slug' => 'uncategorized', 'color' => '#cccccc'], // Default category
                'comments_count' => isset($article['comments']) ? count($article['comments']) : 0
            ];
        })->values(); // Use values() to reset keys for the loop in the view

        // Render the Blade view with the results
        $html = view('blog._partials.search-results', [
            'articles' => $results,
            'total' => $totalResults,
            'query' => $query
        ])->render();

        return response($html);
    }
}
