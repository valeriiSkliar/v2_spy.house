<?php

namespace App\Http\Controllers\Frontend\Blog;

use App\Enums\Frontend\CommentStatus;
use App\Models\Frontend\Blog\BlogComment;
use App\Models\Frontend\Blog\BlogPost;
use App\Models\Frontend\Blog\PostCategory;
use App\Models\Frontend\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlogController extends BaseBlogController
{
    public function index(Request $request)
    {

        $articlesQuery = BlogPost::query()
            ->with(['author', 'categories'])
            ->where('is_published', true);
        $searchQuery = $request->input('search');

        if ($searchQuery) {
            $searchQuery = $this->sanitizeInput($searchQuery);
            $articlesQuery->where('title', 'like', '%' . $searchQuery . '%');
        }

        $totalArticlesCount = $articlesQuery->count();

        $paginatedArticles = $articlesQuery->paginate(12)->appends($request->all());

        return view($this->indexView, [
            'breadcrumbs' => [],
            'heroArticle' => $paginatedArticles->first(),
            'articles' => $paginatedArticles,
            'categories' => $this->getSidebarData(),
            'currentCategory' => null,
            'filters' => $request->only(['search', 'category', 'sort']),
            'query' => $searchQuery,
            'currentPage' => $request->get('page', 1),
            'totalPages' => ceil($totalArticlesCount / 12),
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

    public function rateArticle(Request $request, string $slug)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
        ]);

        if (! Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => __('blog.errors.you_must_be_logged_in_to_rate_articles'),
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
                'message' => __('blog.errors.you_have_already_rated_this_article'),
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
