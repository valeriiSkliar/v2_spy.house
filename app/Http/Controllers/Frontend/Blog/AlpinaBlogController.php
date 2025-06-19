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
        $categorySlug = $request->input('category');
        $sort = $request->input('sort', 'latest');
        $direction = $request->input('direction', 'desc');

        // Применяем фильтр поиска
        if ($searchQuery) {
            $searchQuery = $this->sanitizeInput($searchQuery);
            $articlesQuery->where('title', 'like', '%' . $searchQuery . '%');
        }

        // Применяем фильтр категории
        $currentCategory = null;
        if ($categorySlug) {
            $currentCategory = \App\Models\Frontend\Blog\PostCategory::where('slug', $categorySlug)->first();
            if ($currentCategory) {
                $articlesQuery->whereHas('categories', function ($q) use ($currentCategory) {
                    $q->where('post_categories.id', $currentCategory->id);
                });
            }
        }

        // Применяем сортировку
        switch ($sort) {
            case 'popular':
                $articlesQuery->orderBy('views_count', $direction);
                break;
            case 'views':
                $articlesQuery->orderBy('views_count', $direction);
                break;
            case 'latest':
            default:
                $articlesQuery->orderBy('created_at', $direction);
                break;
        }

        $totalArticlesCount = $articlesQuery->count();

        // Проверяем валидность пагинации ПЕРЕД выполнением запроса
        $currentPage = (int) $request->get('page', 1);
        $maxPages = max(1, ceil($totalArticlesCount / 12));

        if ($currentPage > $maxPages && $totalArticlesCount > 0) {
            // Редиректим на последнюю доступную страницу
            $redirectParams = $request->except('page');
            if ($maxPages > 1) {
                $redirectParams['page'] = $maxPages;
            }
            return redirect()->route('blog.index', $redirectParams);
        }

        $paginatedArticles = $articlesQuery->paginate(12)->appends($request->all());

        // Получаем данные для сайдбара
        $sidebarData = $this->getSidebarData();

        return view('pages.blog.index-alpina', [
            'breadcrumbs' => [],
            'heroArticle' => $paginatedArticles->first(),
            'articles' => $paginatedArticles,
            'categories' => $sidebarData,
            'currentCategory' => $currentCategory,
            'filters' => [
                'search' => $searchQuery,
                'category' => $categorySlug,
                'sort' => $sort,
                'direction' => $direction
            ],
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
