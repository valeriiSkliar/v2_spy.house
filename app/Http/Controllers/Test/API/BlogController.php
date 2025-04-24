<?php
// Add to app/Http/Controllers/Api/BlogController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Test\BlogController as BlogControllerTest;


class BlogController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $limit = $request->get('limit', 5);

        // Reuse the existing getArticles method from BlogController
        $blogController = new BlogControllerTest();
        $articles = $blogController->getArticles();

        // Filter articles by search query
        $filtered = collect($articles)->filter(function ($article) use ($query) {
            return stripos($article['title'], $query) !== false ||
                stripos($article['excerpt'], $query) !== false ||
                stripos($article['content'], $query) !== false;
        });

        // Format results
        $results = $filtered->take($limit)->map(function ($article) {
            return [
                'id' => $article['id'],
                'title' => $article['title'],
                'slug' => $article['slug'],
                'image' => $article['image'],
                'excerpt' => $article['excerpt'],
                'date' => $article['date'],
                'views' => $article['views'],
                'rating' => $article['rating'],
                'category' => $article['category'],
                'comments_count' => count($article['comments'])
            ];
        });

        return response()->json([
            'success' => true,
            'total' => $filtered->count(),
            'articles' => $results
        ]);
    }
}
