<?php

namespace App\Http\Controllers\Frontend\Blog;

use App\Http\Controllers\Frontend\Blog\BaseBlogController;
use App\Models\Frontend\Blog\BlogPost;
use App\Models\Frontend\Blog\PostCategory;
use App\Traits\Frontend\BlogQueryTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class AlpinaBlogController extends BaseBlogController
{
    use BlogQueryTrait;

    public function index(Request $request)
    {
        // Получаем параметры (валидация уже выполнена в middleware)
        $currentPage = (int) $request->get('page', 1);
        $categorySlug = $request->input('category');
        $search = $this->sanitizeInput($request->input('search', ''));
        $sort = $request->input('sort', 'latest');
        $direction = $request->input('direction', 'desc');

        // Строим основной запрос
        $queryResult = $this->buildArticlesQuery($search, $categorySlug, $sort, $direction);
        $query = $queryResult['query'];
        $currentCategory = $queryResult['category'];
        $totalCount = $queryResult['total'];

        // Проверяем валидность пагинации
        $paginationCheck = $this->validatePagination($totalCount, $currentPage, $request);
        if ($paginationCheck) {
            return $paginationCheck;
        }

        // Получаем данные для текущей страницы
        $articlesData = $this->getArticlesForPage($query, $currentPage, $totalCount);
        $heroArticle = $articlesData['heroArticle'];
        $articles = $articlesData['articles'];

        // Получаем данные для сайдбара
        $sidebarData = $this->getSidebarData();

        return view('pages.blog.index-alpina', [
            'breadcrumbs' => [],
            'heroArticle' => $heroArticle,
            'articles' => $articles,
            'categories' => $sidebarData,
            'currentCategory' => $currentCategory,
            'filters' => [
                'search' => $search,
                'category' => $categorySlug,
                'sort' => $sort,
                'direction' => $direction
            ],
            'query' => $search,
            'currentPage' => $articles->currentPage(),
            'totalPages' => $articles->lastPage(),
            'totalCount' => $totalCount,
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
