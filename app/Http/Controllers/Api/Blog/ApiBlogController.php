<?php

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\FrontendController;
use Illuminate\Http\Request;
use App\Http\Controllers\Frontend\Blog\BlogController;


class ApiBlogController extends FrontendController
{
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $query = $this->sanitizeInput($query);
        $limit = $request->get('limit', 5); // Limit for live search results

        // Reuse the existing getArticles method from BlogController
        $blogController = app(BlogController::class);
        $articles = $blogController->searchApiResults($query);



        // Format results for the view, taking the limit for live search
        // $results = $articles->take($limit)->map(function ($article) {
        //     return [
        //         'id' => $article->id,
        //         'title' => $article->title,
        //         'slug' => $article->slug,
        //         'image' => $article->featured_image ?? asset('images/default-article.jpg'), // Add default image
        //         'date' => $article->created_at->format('d.m.y'),
        //         'views' => $article->views_count ?? 0,
        //         'rating' => $article->rating ?? 0,
        //         'category' => $article->category ?? ['name' => 'Uncategorized', 'slug' => 'uncategorized', 'color' => '#cccccc'], // Default category
        //         'comments_count' => isset($article->comments) ? count($article->comments) : 0
        //     ];
        // })->values(); // Use values() to reset keys for the loop in the view

        $results = $articles->take($limit);
        $totalResults = $articles->count();

        // Render the Blade view with the results
        $html = view('blog._partials.search-results', [
            'articles' => $results,
            'total' => $totalResults,
            'query' => $query
        ])->render();



        return response()->json([
            'success' => true,
            'message' => 'Search results fetched successfully',
            'data' => [
                'html' => $html,
                'total' => $totalResults,
                'query' => $query
            ]
        ]);
    }
}
