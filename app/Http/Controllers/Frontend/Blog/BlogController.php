<?php

namespace App\Http\Controllers\Frontend\Blog;

use App\Enums\Frontend\CommentStatus;
use App\Http\Controllers\FrontendController;
use App\Models\Frontend\Blog\BlogComment;
use App\Models\Frontend\Blog\BlogPost;
use App\Models\Frontend\Blog\PostCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

use function App\Helpers\sanitize_input;

class BlogController extends FrontendController
{
    public function index(Request $request)
    {

        $query = BlogPost::query()
            ->with(['author', 'categories'])
            ->where('is_published', true);
        $search = $request->input('search');

        if ($search) {
            $search = sanitize_input($search);
            $query->where('title', 'like', '%' . $search . '%');
        }

        return view('blog.index', [
            'breadcrumbs' => [],
            'heroArticle' => $query->first(),
            'articles' => $query->paginate(12)->appends($request->all()),
            'categories' => $this->getSidebarData(),
            'currentCategory' => null,
            'filters' => $request->only(['search', 'category', 'sort']),
            'currentPage' => $request->get('page', 1),
            'totalPages' => ceil($query->count() / 12)
        ]);
    }

    public function show(string $slug, Request $request)
    {
        $post = BlogPost::where('slug', $slug)
            ->where('is_published', true)
            ->with(['author', 'categories', 'relatedPosts' => function ($query) {
                $query->where('is_published', true);
            }, 'comments'])
            ->firstOrFail();

        // Increment view count
        $post->increment('views_count');

        $comments = BlogComment::where('post_id', $post->id)
            ->where('status', CommentStatus::APPROVED->value)
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->appends($request->all());

        return view('blog.show', [
            'breadcrumbs' => [
                ['title' => 'Blog', 'url' => route('blog.index')],
                ['title' => $post->categories->first()->name, 'url' => route('blog.category', $post->categories->first()->slug)],
                ['title' => $post->title, 'url' => route('blog.show', $post->slug)],
            ],
            'article' => $post,
            'currentCategory' => $post->categories->first(),
            'comments' => $comments,
            'categories' => $this->getSidebarData(),
            'relatedPosts' => $post->relatedPosts,
            'canModerate' => false
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
                $search = sanitize_input($search);
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(12)
            ->withQueryString();

        $sidebar = $this->getSidebarData();

        return view('blog.index', [
            'breadcrumbs' => [
                ['title' => 'Blog', 'url' => route('blog.index')],
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


    private function getSidebarData(string $locale = 'en'): array
    {


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

        return [
            'categories' => $categories,
            'popularPosts' => BlogPost::query()
                ->with(['author'])
                ->where('is_published', true)
                ->orderBy('views_count', 'desc')
                ->take(5)
                ->get()
        ];
    }

    private function buildCategoryTree($categories, $parentId = null): array
    {
        return $categories
            ->where('parent_id', $parentId)
            ->map(function ($category) use ($categories) {
                $categoryData = $category->toArray();
                $children = $this->buildCategoryTree($categories, $category->id);

                if (!empty($children)) {
                    $categoryData['children'] = $children;
                }

                return $categoryData;
            })
            ->values()
            ->all();
    }
}
