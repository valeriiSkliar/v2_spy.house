<?php

namespace App\Http\Controllers\Frontend\Blog;

use App\Http\Controllers\Frontend\Blog\BaseBlogController;
use App\Models\Frontend\Blog\BlogPost;
use App\Models\Frontend\Blog\PostCategory;
use App\Traits\Frontend\BlogQueryTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


class AlpinaBlogController extends BaseBlogController
{
    use BlogQueryTrait;

    const CACHE_TTL = 300; // 5 minutes

    public function index(Request $request)
    {
        // Валидация выполняется через middleware BlogParametersValidation

        // Получаем параметры
        $currentPage = (int) $request->get('page', 1);
        $categorySlug = $request->input('category');
        $search = $this->sanitizeInput($request->input('search', ''));
        $sort = $request->input('sort', 'latest');
        $direction = $request->input('direction', 'desc');

        // Создаем ключ кеша для запроса (только для поиска и категорий)
        $cacheKey = null;
        if ($search || $categorySlug) {
            $cacheKey = $this->generateCacheKey($currentPage, $categorySlug, $search, $sort, $direction);

            // Пытаемся получить данные из кеша для AJAX запросов
            if ($request->ajax()) {
                $cachedResult = Cache::get($cacheKey);
                if ($cachedResult) {
                    return response()->json($cachedResult);
                }
            }
        }

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

        // Если это AJAX запрос, возвращаем JSON
        if ($request->ajax()) {
            $response = $this->buildAjaxResponse($articlesData, $currentCategory, $totalCount, $search);

            // Кешируем результат если это поиск или категория
            if ($cacheKey) {
                Cache::put($cacheKey, $response, self::CACHE_TTL);
            }

            return response()->json($response);
        }

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


    /**
     * Генерация ключа кеша
     */
    private function generateCacheKey(int $page, ?string $category, ?string $search, string $sort = 'latest', string $direction = 'desc'): string
    {
        return sprintf(
            'blog_ajax_%s_%s_%s_%s_%d',
            $category ?: 'all',
            md5($search ?: ''),
            $sort,
            $direction,
            $page
        );
    }

    /**
     * Построение AJAX ответа
     */
    private function buildAjaxResponse(array $articlesData, $currentCategory, int $totalCount, ?string $search): array
    {
        $heroArticle = $articlesData['heroArticle'];
        $articles = $articlesData['articles'];

        // ИСПРАВЛЕНИЕ: Получаем текущие фильтры из request для синхронизации с фронтендом
        $currentFilters = [
            'page' => $articles->currentPage(),
            'category' => request()->input('category', ''),
            'search' => request()->input('search', ''),
            'sort' => request()->input('sort', 'latest'),
            'direction' => request()->input('direction', 'desc'),
        ];

        // Если нет статей
        if ($articles->count() === 0 && !$heroArticle) {
            $queryText = $this->getQueryText($currentCategory, $search);
            $html = view('components.blog.blog-no-results-found', ['query' => $queryText])->render();

            return [
                'html' => $html,
                'pagination' => '',
                'hasPagination' => false,
                'currentPage' => 1,
                'totalPages' => 0,
                'count' => 0,
                'currentCategory' => $this->formatCategoryResponse($currentCategory),
                'totalCount' => 0,
                'filters' => $currentFilters, // ДОБАВЛЕНО: фильтры для синхронизации состояния
            ];
        }

        // Генерируем HTML статей
        $articlesHtml = view('components.blog.list.articles-list', compact('articles', 'heroArticle'))->render();

        // Генерируем пагинацию
        $shouldShowPagination = $this->shouldShowPagination($totalCount, $heroArticle);
        $paginationHtml = '';

        if ($shouldShowPagination && $articles->hasPages()) {
            $articles->withPath(route('blog.index'));
            $paginationHtml = $articles->links()->toHtml();
        }

        return [
            'html' => $articlesHtml,
            'pagination' => $paginationHtml,
            'hasPagination' => $shouldShowPagination && $articles->hasPages(),
            'currentPage' => $articles->currentPage(),
            'totalPages' => $articles->lastPage(),
            'count' => $articles->count(),
            'currentCategory' => $this->formatCategoryResponse($currentCategory),
            'totalCount' => $totalCount,
            'filters' => $currentFilters, // ДОБАВЛЕНО: фильтры для синхронизации состояния
        ];
    }

    /**
     * Определение нужно ли показывать пагинацию
     */
    private function shouldShowPagination(int $totalCount, $heroArticle): bool
    {
        return ($totalCount > self::ARTICLES_PER_PAGE) ||
            ($heroArticle && $totalCount > self::ARTICLES_PER_PAGE + 1);
    }

    /**
     * Получение текста запроса для отображения
     */
    private function getQueryText($currentCategory, ?string $search): string
    {
        if ($currentCategory && !$search) {
            return $currentCategory->name;
        }

        return $search ?: '';
    }

    /**
     * Форматирование ответа категории
     */
    private function formatCategoryResponse($currentCategory): ?array
    {
        if (!$currentCategory) {
            return null;
        }

        return [
            'id' => $currentCategory->id,
            'name' => $currentCategory->name,
            'slug' => $currentCategory->slug,
        ];
    }

    /**
     * Валидация пагинации для AJAX запросов (переопределяет метод из трейта)
     */
    protected function validatePagination(int $totalCount, int $currentPage, Request $request)
    {
        // Если нет статей, но есть поисковый запрос или категория - показываем "нет результатов"
        if ($totalCount === 0) {
            $hasSearchOrCategory = $request->filled('search') || $request->filled('category');

            if ($hasSearchOrCategory) {
                // Не делаем редирект, позволяем показать "нет результатов"
                return null;
            }

            // Только если нет фильтров - редиректим на главную
            if ($request->ajax()) {
                return response()->json([
                    'redirect' => true,
                    'url' => $this->buildRedirectUrl($request, 1, true),
                ]);
            }

            return redirect()->route('blog.index');
        }

        // Вычисляем максимальное количество страниц
        $maxPages = $this->calculateMaxPages($totalCount, $currentPage);

        // Если текущая страница превышает максимум
        if ($currentPage > $maxPages && $maxPages > 0) {
            $redirectPage = $maxPages;
            if ($request->ajax()) {
                return response()->json([
                    'redirect' => true,
                    'url' => $this->buildRedirectUrl($request, $redirectPage),
                ]);
            }

            return redirect()->route('blog.index', $this->buildRedirectParams($request, $redirectPage));
        }

        return null;
    }

    /**
     * Построение URL для редиректа
     */
    private function buildRedirectUrl(Request $request, int $page, bool $resetAll = false): string
    {
        if ($resetAll) {
            return route('blog.index');
        }

        $params = $this->buildRedirectParams($request, $page);

        return route('blog.index', $params);
    }
}
