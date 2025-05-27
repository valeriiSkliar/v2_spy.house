<?php

namespace App\Http\Controllers\Frontend\Blog;

use App\Enums\Frontend\CommentStatus;
use App\Models\Frontend\Blog\BlogComment;
use App\Models\Frontend\Blog\BlogPost;
use App\Models\Frontend\Blog\PostCategory;
use App\Models\Frontend\Rating;
use App\Services\Frontend\Toast;
use App\Traits\App\HasAntiFloodProtection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlogController extends BaseBlogController
{
    use HasAntiFloodProtection;

    public function index(Request $request)
    {

        $query = BlogPost::query()
            ->with(['author', 'categories'])
            ->where('is_published', true);
        $search = $request->input('search');

        if ($search) {
            $search = $this->sanitizeInput($search);
            $query->where('title', 'like', '%' . $search . '%');
        }

        return view($this->indexView, [
            'breadcrumbs' => [],
            'heroArticle' => $query->first(),
            'articles' => $query->paginate(12)->appends($request->all()),
            'categories' => $this->getSidebarData(),
            'currentCategory' => null,
            'filters' => $request->only(['search', 'category', 'sort']),
            'currentPage' => $request->get('page', 1),
            'totalPages' => ceil($query->count() / 12),
        ]);
    }

    public function show(string $slug, Request $request)
    {
        $locale = $request->get('locale') ?? app()->getLocale();
        $post = BlogPost::where('slug', $slug)
            ->where('is_published', true)
            ->with(['author', 'categories', 'ratings'])
            ->firstOrFail();

        // Increment view count
        $post->increment('views_count');

        $userRating = null;
        if (Auth::check()) {
            $userRating = $post->ratings()->where('user_id', Auth::id())->first();
        }

        $comments = BlogComment::where('post_id', $post->id)
            ->where('status', CommentStatus::APPROVED->value)
            ->whereNull('parent_id')
            ->with(['replies' => function ($query) {
                $query->where('status', CommentStatus::APPROVED->value)->orderBy('created_at', 'asc');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $commentsCount = BlogComment::where('post_id', $post->id)
            ->where('status', CommentStatus::APPROVED->value)
            ->count();

        $relatedPosts = $post->relatedPosts()->get();

        return view($this->showView, [
            'breadcrumbs' => [
                ['title' => __('blogs.breadcrumbs.blog'), 'url' => route('blog.index')],
                ['title' => $post->categories->first()->getTranslation('name', $locale), 'url' => route('blog.category', $post->categories->first()->slug)],
                ['title' => $post->title, 'url' => route('blog.show', $post->slug)],
            ],
            'article' => $post,
            'currentCategory' => $post->categories->first(),
            'comments' => $comments,
            'commentsCount' => $commentsCount,
            'categories' => $this->getSidebarData(),
            'relatedPosts' => $relatedPosts,
            'canModerate' => false,
            'averageRating' => $post->averageRating(),
            'userRating' => $userRating,
            'isRated' => Auth::check() && $post->ratings()->where('user_id', Auth::id())->exists(),
        ]);
    }

    public function byCategory(string $slug, Request $request)
    {
        $category = PostCategory::where('slug', $slug)
            ->with('children')
            ->firstOrFail();

        $posts = BlogPost::whereHas('categories', function ($query) use ($category) {
            $query->where('post_categories.id', $category->id)
                ->orWhere('post_categories.parent_id', $category->id);
        })
            ->where('is_published', true)
            ->with(['author', 'categories'])
            ->when($request->input('search'), function ($query, $search) {
                $search = $this->sanitizeInput($search);
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(12)
            ->withQueryString();

        $sidebar = $this->getSidebarData();

        return view($this->indexView, [
            'breadcrumbs' => [
                ['title' => __('blogs.header.title'), 'url' => route('blog.index')],
                ['title' => $category->name, 'url' => route('blog.category', $category->slug)],
            ],
            'heroArticle' => $posts->first(),
            'articles' => $posts,
            'sidebar' => $sidebar,
            'categories' => $this->getSidebarData(),
            'currentCategory' => $category,

            'filters' => [
                'search' => $request->input('search'),
                'category' => $category,
            ],
        ]);
    }

    private function getSidebarData(): array
    {
        $locale = app()->getLocale();

        $categories = PostCategory::query()
            ->withCount(['posts' => function ($query) {
                $query->where('is_published', true);
            }])
            ->having('posts_count', '>', 0)
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($category) use ($locale) {
                $translatedName = $category->getTranslation('name', $locale);
                $category->name = $translatedName;

                return $category;
            });

        $popularPosts = BlogPost::query()
            ->with(['author'])
            ->where('is_published', true)
            ->orderBy('views_count', 'desc')
            ->take(5)
            ->get();

        return [
            'categories' => $categories,
            'popularPosts' => $popularPosts,
        ];
    }

    public function search(Request $request)
    {
        $query = $request->input('q');
        if (! $query) {
            return redirect()->route('blog.index');
        }
        $search = $this->sanitizeInput($query);
        $results = $this->getSearchResults($search);

        return view($this->indexView, [
            'articles' => $results,
            'heroArticle' => $results->first(),
            'query' => $query,
            'categories' => $this->getSidebarData(),
            'currentCategory' => null,
            'filters' => $request->only(['search', 'category', 'sort']),
            'currentPage' => $request->get('page', 1),
            'totalPages' => ceil($results->count() / 12),
            'breadcrumbs' => [
                ['title' => __('blogs.breadcrumbs.blog'), 'url' => route('blog.index')],
                ['title' => __('blogs.breadcrumbs.search_results', ['query' => $query]), 'url' => route('blog.search', ['q' => $query])],
            ],
        ]);
    }

    private function getSearchResults(string $query)
    {

        $results = BlogPost::query()
            ->where('is_published', true)
            ->where('title', 'like', "%{$query}%")
            ->orderBy('created_at', 'desc')
            ->with(['author', 'categories'])
            ->paginate(12);

        return $results;
    }

    public function searchApiResults(string $query)
    {

        $results = $this->getSearchResults($query);

        return $results;
    }

    public function storeComment(Request $request, string $slug)
    {
        // Get user ID or IP for guests
        $userId = Auth::id() ?? $request->ip();

        // --- Anti-Flood Check ---
        $action = 'store_comment';
        $limit = 1; // 1 comment
        $window = 60; // per 60 seconds (1 minute)

        if (! $this->checkAntiFlood($userId, $action, $limit, $window)) {
            $errorMessage = __('blogs.comments.flood_protection_message');
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $errorMessage], 429); // 429 Too Many Requests
            }

            return redirect()->back()->withInput();
        }
        // --- Anti-Flood Check End ---

        $request->validate([
            'content' => 'required|min:2|max:1000',
            'parent_id' => 'nullable|exists:blog_comments,id',
        ], [
            'content.required' => __('blogs.comments.content_required'),
            'content.min' => __('blogs.comments.content_min'),
            'content.max' => __('blogs.comments.content_max'),
        ]);

        $post = BlogPost::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $user = Auth::user(); // Get user details AFTER validation and anti-flood check

        $comment = new BlogComment([
            'post_id' => $post->id,
            'author_name' => $user->name,
            'email' => $user->email,
            'content' => $this->sanitizeInput($request->content),
            'status' => CommentStatus::APPROVED, // Auto-approve for authenticated users
            'is_spam' => false,
        ]);

        if ($request->filled('parent_id')) {
            $comment->parent_id = $request->parent_id;
        }

        $comment->save();

        $successMessage = __('blogs.comments.comment_added_successfully');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'comment' => $comment->load('replies'), // Load replies if any
            ]);
        }

        return redirect()->route('blog.show', $post->slug);
    }

    public function paginateComments(Request $request, $slug)
    {
        $page = $request->get('page', 1);

        $post = BlogPost::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $comments = BlogComment::where('post_id', $post->id)
            ->where('status', CommentStatus::APPROVED->value)
            ->whereNull('parent_id')
            ->with(['replies' => function ($query) {
                $query->where('status', CommentStatus::APPROVED->value)->orderBy('created_at', 'asc');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $user = Auth::user();
        $commentsHtml = '';
        if ($comments->isEmpty()) {
            $commentsHtml = '<div class="message _bg _with-border">No comments found.</div>';
        } else {
            if ($user) {
                $commentsHtml .= view('components.blog.comment.reply-form', [
                    'article' => $post,
                    'isReply' => false,
                    'errors' => app('view')->shared('errors', new \Illuminate\Support\ViewErrorBag),
                ])->render();
            }
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

    public function rateArticle(Request $request, string $slug)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
        ]);

        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'You must be logged in to rate articles.',
            ], 401);
        }

        $user = Auth::user();

        $post = BlogPost::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        // Check if user has already rated
        $existingRating = Rating::where('user_id', $user->id)
            ->where('blog_id', $post->id)
            ->first();

        if ($existingRating) {
            return response()->json([
                'success' => false,
                'message' => 'You have already rated this article.',
            ], 422);
        }

        $rating = new Rating([
            'user_id' => $user->id,
            'blog_id' => $post->id,
            'rating' => $request->rating,
        ]);
        $rating->save();

        // Update average rating
        $averageRating = $post->averageRating();
        $averageRating = round($averageRating, 1);

        // Update rating in post model
        $post->average_rating = $averageRating;
        $post->save();

        // Return both average rating and user's rating
        return response()->json([
            'success' => true,
            'average_rating' => $averageRating,
            'user_rating' => $request->rating,
            'is_rated' => true,
        ]);
    }

    private function buildCategoryTree($categories, $parentId = null): array
    {
        return $categories
            ->where('parent_id', $parentId)
            ->map(function ($category) use ($categories) {
                $categoryData = $category->toArray();
                $children = $this->buildCategoryTree($categories, $category->id);

                if (! empty($children)) {
                    $categoryData['children'] = $children;
                }

                return $categoryData;
            })
            ->values()
            ->all();
    }
}
