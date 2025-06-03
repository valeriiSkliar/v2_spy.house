<?php

namespace App\Http\Controllers\Api\Blog;

use App\Enums\Frontend\CommentStatus;
use App\Http\Controllers\Frontend\Blog\BaseBlogController;
use App\Http\Controllers\Frontend\Blog\BlogController;
use App\Models\Frontend\Blog\BlogComment;
use App\Models\Frontend\Blog\BlogPost;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
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

        $results = $articles->take($limit);
        $totalResults = $articles->count();

        // Set the locale for rendering
        $currentLocale = App::getLocale();
        // No need to explicitly set locale if it's already the intended one,
        // but let's assume the API context might differ. If middleware handles locale,
        // this might be redundant, but it ensures correctness.
        // App::setLocale($currentLocale); // Example if needed, often handled by middleware

        // Render the Blade view with the results
        $html = view('partials.blog.search-results', [
            'articles' => $results,
            'total' => $totalResults,
            'query' => $query,
        ])->render();

        // Restore locale if it was changed, though typically not needed if middleware handles it
        // App::setLocale(config('app.locale')); // Example restore if needed

        return response()->json([
            'success' => true,
            'message' => __('blog.success.search_results_fetched_successfully'),
            'data' => [
                'html' => $html,
                'total' => $totalResults,
                'query' => $query,
            ],
        ]);
    }

    /**
     * Store a new comment for a blog post
     *
     * @param  string  $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeComment(Request $request, $slug)
    {
        $request->validate([
            'content' => 'required|min:2|max:1000',
        ]);

        $post = BlogPost::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $user = Auth::user();

        $comment = new BlogComment([
            'post_id' => $post->id,
            'author_name' => $user->name,
            'email' => $user->email,
            'content' => $this->sanitizeInput($request->content),
            'status' => CommentStatus::APPROVED, // Auto-approve for authenticated users
            'is_spam' => false,
        ]);

        $comment->save();

        // Fetch the latest comments including the new one
        return $this->refreshComments($slug);
    }

    /**
     * Get reply form for a specific comment
     *
     * @param  string  $slug
     * @param  int  $comment_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReplyForm(Request $request, $slug, $comment_id)
    {
        $post = BlogPost::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $comment = BlogComment::where('id', $comment_id)
            ->where('post_id', $post->id)
            ->firstOrFail();

        $replyFormHtml = view('components.blog.comment.reply-form', [
            'article' => [
                'slug' => $slug,
            ],
            'isReply' => true,
            'replyTo' => [
                'id' => $comment->id,
                'author' => $comment->author_name,
            ],
            'errors' => app('view')->shared('errors', new \Illuminate\Support\ViewErrorBag),
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $replyFormHtml,
        ]);
    }

    /**
     * Store a reply to an existing comment
     *
     * @param  string  $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeReply(Request $request, $slug)
    {
        $request->validate([
            'content' => 'required|min:2|max:1000',
            'parent_id' => 'required|exists:blog_comments,id',
        ]);

        $post = BlogPost::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $parentComment = BlogComment::findOrFail($request->parent_id);

        // Verify parent comment belongs to this post
        if ($parentComment->post_id != $post->id) {
            return response()->json([
                'success' => false,
                'message' => __('blog.errors.invalid_parent_comment'),
            ], 422);
        }

        $user = Auth::user();

        // Prevent users from replying to their own comments
        if ($parentComment->email === $user->email) {
            return response()->json([
                'success' => false,
                'message' => __('blog.errors.cannot_reply_to_own_comment'),
            ], 422);
        }

        $reply = new BlogComment([
            'post_id' => $post->id,
            'parent_id' => $parentComment->id,
            'author_name' => $user->name,
            'email' => $user->email,
            'content' => $this->sanitizeInput($request->content),
            'status' => CommentStatus::APPROVED, // Auto-approve for authenticated users
            'is_spam' => false,
        ]);

        $reply->save();

        // Fetch the latest comments including the new reply
        return $this->refreshComments($slug);
    }

    public function paginateComments(Request $request, $slug)
    {
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
        $commentsHtml = '';
        if ($comments->isEmpty()) {
            $commentsHtml = '<div class="message _bg _with-border">' . __('blog.errors.no_comments_found') . '</div>';
        } else {
            $commentsHtml .= view('components.blog.comment.reply-form', [
                'article' => $post,
                'isReply' => false,
                'errors' => app('view')->shared('errors', new \Illuminate\Support\ViewErrorBag),
            ])->render();
            foreach ($comments as $comment) {
                $commentsHtml .= view('components.blog.comment.comment', [
                    'comment' => $comment,
                    'slug' => $slug,
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
            'elements' => $elements,
        ])->render();

        return response()->json([
            'success' => true,
            'commentsHtml' => $commentsHtml,
            'paginationHtml' => $paginationHtml,
            'currentPage' => $comments->currentPage(),
            'lastPage' => $comments->lastPage(),
            'total' => $comments->total(),
        ]);
    }

    public function ajaxList(Request $request)
    {
        // Build query directly instead of calling BlogController
        $query = BlogPost::query()
            ->with(['author', 'categories'])
            ->where('is_published', true);

        $search = $request->input('search');
        if ($search) {
            $search = $this->sanitizeInput($search);
            $query->where('title', 'like', '%' . $search . '%');
        }

        // Get hero article only for first page
        $heroArticle = null;
        if ($request->get('page', 1) == 1) {
            $heroArticle = $query->orderBy('created_at', 'desc')->first();
        }

        // Get paginated articles, exclude hero article to avoid duplication
        $articlesQuery = clone $query;
        if ($heroArticle) {
            $articlesQuery->where('id', '!=', $heroArticle->id);
        }
        $articles = $articlesQuery->orderBy('created_at', 'desc')->paginate(12)->appends($request->all());

        if ($request->ajax()) {
            // Generate articles HTML with empty state handling
            $articlesHtml = '';
            if ($articles->count() > 0 || $heroArticle) {
                $articlesHtml .= view('components.blog.list.articles-list', compact('articles', 'heroArticle'))->render();
            } else {
                $searchQuery = $request->get('search', '');
                $articlesHtml = view('components.blog.blog-no-results-found', ['query' => $searchQuery])->render();
            }

            // Generate pagination HTML
            $paginationHtml = '';
            if ($articles->hasPages()) {
                // Set the path for pagination links to the regular blog route instead of API route
                $articles->withPath(route('blog.index'));
                $paginationHtml = $articles->links()->toHtml();
            }

            return response()->json([
                'html' => $articlesHtml,
                'pagination' => $paginationHtml,
                'hasPagination' => $articles->hasPages(),
                'currentPage' => $articles->currentPage(),
                'totalPages' => $articles->lastPage(),
                'count' => $articles->count(),
            ]);
        }

        // For non-AJAX requests, redirect to regular blog controller
        return redirect()->route('blog.index', $request->all());
    }

    /**
     * Helper method to refresh comments after adding a new one
     *
     * @param  string  $slug
     * @return \Illuminate\Http\JsonResponse
     */
    private function refreshComments($slug)
    {
        $request = new Request;
        $request->merge(['page' => 1]);

        return $this->paginateComments($request, $slug);
    }
}
