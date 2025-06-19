<?php

namespace App\Http\Controllers\Frontend\Blog;

use App\Http\Controllers\Frontend\Blog\BaseBlogController;
use App\Models\Frontend\Blog\BlogPost;
use App\Models\Frontend\Blog\PostCategory;
use Illuminate\Http\Request;


class AlpinaBlogController extends BaseBlogController
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

        // Получаем данные для сайдбара
        $sidebarData = $this->getSidebarData();

        return view('pages.blog.index-alpina', [
            'breadcrumbs' => [],
            'heroArticle' => $paginatedArticles->first(),
            'articles' => $paginatedArticles,
            'categories' => $sidebarData,
            'currentCategory' => null,
            'filters' => $request->only(['search', 'category', 'sort']),
            'query' => $searchQuery,
            'currentPage' => $paginatedArticles->currentPage(),
            'totalPages' => $paginatedArticles->lastPage(),
            'totalCount' => $totalArticlesCount,
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
}
