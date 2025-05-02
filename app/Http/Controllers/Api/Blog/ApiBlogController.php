<?php

namespace App\Http\Controllers\Api\Blog;

use Illuminate\Http\Request;
use App\Http\Controllers\Frontend\Blog\BlogController;
use App\Enums\Frontend\CommentStatus;
use App\Http\Controllers\Frontend\Blog\BaseBlogController;
use App\Models\Frontend\Blog\BlogComment;
use App\Models\Frontend\Blog\BlogPost;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class ApiBlogController extends BaseBlogController
{
    use AuthorizesRequests;

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

    public function paginateComments(Request $request, $slug) {
        $page = $request->get('page', 1);

        $post = BlogPost::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();
            
            $comments = BlogComment::where('post_id', $post->id)
            ->where('status', CommentStatus::APPROVED->value)
        
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

            $user = Auth::user();
            // dd($user);
        $commentsHtml = '';
        if ($comments->isEmpty()) {
            $commentsHtml = '<div class="message _bg _with-border">No comments found.</div>';
        } else {
            // Получаем текущего пользователя
            $commentsHtml .= view('components.blog.comment.comment-form', [
                'article' => $post,
                'isReply' => false,
                'user' => $user,
                'errors' => app('view')->shared('errors', new \Illuminate\Support\ViewErrorBag),
                // 'replyTo' => $comment
            ])->render();
            foreach ($comments as $comment) {

                $commentsHtml .= view('components.blog.comment.comment', [
                    'comment' => $comment,
                    'slug' => $slug
                ])->render();
            }
        }
        
        // Generate pagination elements manually for the API context
        $elements = [];
        $lastPage = $comments->lastPage();
        $currentPage = $comments->currentPage();
        
        // Handle edge case where currentPage > lastPage
        if ($currentPage > $lastPage && $lastPage > 0) {
            $currentPage = $lastPage;
            // Reset the paginator to the correct page
            $comments = BlogComment::where('post_id', $post->id)
                ->where('status', CommentStatus::APPROVED->value)
                // ->with('replies')
                // ->withCount('replies')
                // ->orderBy('replies_count', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate(10, ['*'], 'page', $currentPage)
                ->withQueryString();
        }
        
        if ($lastPage > 0) {
            // Build page array for pagination
            $pages = [];
            for ($i = 1; $i <= $lastPage; $i++) {
                $pages[$i] = $i; // Use the page number itself as the value
            }
            $elements[0] = $pages;
        } else {
            // No pages, create empty array
            $elements[0] = [];
        }
        
        $paginationHtml = view('components.blog.comment.async-pagination', [
            'paginator' => $comments,
            'elements' => $elements
        ])->render();
        
        return response()->json([
            'success' => true,
            'commentsHtml' => $commentsHtml,
            'paginationHtml' => $paginationHtml,
            'currentPage' => $comments->currentPage(),
            'lastPage' => $comments->lastPage(),
            'total' => $comments->total()
        ]);
    }
}
