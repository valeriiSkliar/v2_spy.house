<?php

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Frontend\Blog\BaseBlogController;
use App\Models\Frontend\Blog\BlogPost;
use App\Models\Frontend\Blog\PostCategory;
use App\Models\Frontend\Blog\BlogComment;
use App\Enums\Frontend\CommentStatus;
use App\Traits\App\HasAntiFloodProtection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ApiBlogController extends BaseBlogController
{
    use HasAntiFloodProtection;

    const ARTICLES_PER_PAGE = 12;

    const CACHE_TTL = 300; // 5 минут

    /**
     * AJAX список статей с улучшенной валидацией и обработкой крайних случаев
     */
    public function ajaxList(Request $request)
    {
        // Валидация входных параметров
        $validator = Validator::make($request->all(), [
            'page' => 'integer|min:1|max:1000',
            'category' => 'string|max:255|alpha_dash',
            'search' => 'string|max:255',
            'sort' => 'string|in:latest,popular,views',
            'direction' => 'string|in:asc,desc',
        ]);

        if ($validator->fails()) {
            return $this->handleValidationError($request, $validator->errors()->first());
        }

        $currentPage = (int) $request->get('page', 1);
        $categorySlug = $request->input('category');
        $search = $this->sanitizeInput($request->input('search', ''));
        $sort = $request->input('sort', 'latest');
        $direction = $request->input('direction', 'desc');

        // Создаем ключ кеша для запроса
        $cacheKey = $this->generateCacheKey($currentPage, $categorySlug, $search, $sort, $direction);

        // Пытаемся получить данные из кеша (только для поиска и категорий)
        if ($search || $categorySlug) {
            $cachedResult = Cache::get($cacheKey);
            if ($cachedResult && $request->ajax()) {
                return response()->json($cachedResult);
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

        if (! $request->ajax()) {
            return redirect()->route('blog.index', $request->all());
        }

        // Формируем ответ для AJAX
        $response = $this->buildAjaxResponse($articlesData, $currentCategory, $totalCount, $search);

        // Кешируем результат если это поиск или категория
        if ($search || $categorySlug) {
            Cache::put($cacheKey, $response, self::CACHE_TTL);
        }

        return response()->json($response);
    }

    /**
     * Построение запроса статей с учетом фильтров
     */
    private function buildArticlesQuery(?string $search, ?string $categorySlug, string $sort = 'latest', string $direction = 'desc'): array
    {
        $query = BlogPost::query()
            ->with(['author', 'categories'])
            ->where('is_published', true);

        $currentCategory = null;

        // Применяем фильтр поиска
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Применяем фильтр категории
        if ($categorySlug) {
            $currentCategory = PostCategory::where('slug', $categorySlug)->first();
            if ($currentCategory) {
                $query->whereHas('categories', function ($q) use ($currentCategory) {
                    $q->where('post_categories.id', $currentCategory->id)
                        ->orWhere('post_categories.parent_id', $currentCategory->id);
                });
            } else {
                // Если категория не найдена, возвращаем пустой результат
                $query->whereRaw('1 = 0');
            }
        }

        // Применяем сортировку
        $this->applySorting($query, $sort, $direction);

        $totalCount = $query->count();

        return [
            'query' => $query,
            'category' => $currentCategory,
            'total' => $totalCount,
        ];
    }

    /**
     * Применение сортировки к запросу
     */
    private function applySorting($query, string $sort, string $direction): void
    {
        switch ($sort) {
            case 'popular':
                // Сортировка по популярности - используем субзапрос для избежания проблем с GROUP BY
                $query->addSelect([
                    'avg_rating' => DB::table('ratings')
                        ->selectRaw('COALESCE(AVG(rating), 0)')
                        ->whereColumn('ratings.blog_id', 'blog_posts.id')
                ])
                    ->orderByRaw('((SELECT COALESCE(AVG(rating), 0) FROM ratings WHERE ratings.blog_id = blog_posts.id) * 0.7 + LOG(blog_posts.views_count + 1) * 0.3) ' . $direction);
                break;

            case 'views':
                // Сортировка по количеству просмотров
                $query->orderBy('views_count', $direction);
                break;

            case 'latest':
            default:
                // Сортировка по дате создания
                $query->orderBy('created_at', $direction);
                break;
        }
    }

    /**
     * Валидация пагинации и обработка крайних случаев
     */
    private function validatePagination(int $totalCount, int $currentPage, Request $request)
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
     * Вычисление максимального количества страниц
     */
    private function calculateMaxPages(int $totalCount, int $currentPage): int
    {
        $heroTakesSlot = ($currentPage === 1 && $totalCount > 0) ? 1 : 0;
        $availableForPagination = $totalCount - $heroTakesSlot;

        if ($heroTakesSlot > 0) {
            return max(1, ceil($availableForPagination / self::ARTICLES_PER_PAGE));
        }

        return ceil($totalCount / self::ARTICLES_PER_PAGE);
    }

    /**
     * Получение статей для конкретной страницы
     */
    private function getArticlesForPage($query, int $currentPage, int $totalCount): array
    {
        // Получаем hero статью только для первой страницы
        $heroArticle = null;
        if ($currentPage === 1 && $totalCount > 0) {
            $heroArticle = (clone $query)->first();
        }

        // Получаем статьи для пагинации (исключаем hero если есть)
        $articlesQuery = clone $query;
        if ($heroArticle) {
            $articlesQuery->where('id', '!=', $heroArticle->id);
        }

        $articles = $articlesQuery
            ->paginate(self::ARTICLES_PER_PAGE)
            ->appends(request()->all());

        return [
            'heroArticle' => $heroArticle,
            'articles' => $articles,
        ];
    }

    /**
     * Построение AJAX ответа
     */
    private function buildAjaxResponse(array $articlesData, $currentCategory, int $totalCount, ?string $search): array
    {
        $heroArticle = $articlesData['heroArticle'];
        $articles = $articlesData['articles'];

        // Если нет статей (не должно происходить из-за валидации выше)
        if ($articles->count() === 0 && ! $heroArticle) {
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
     * Обработка ошибок валидации
     */
    private function handleValidationError(Request $request, string $error)
    {
        if ($request->ajax()) {
            return response()->json([
                'redirect' => true,
                'url' => route('blog.index'),
                'error' => $error,
            ], 422);
        }

        return redirect()->route('blog.index')->withErrors(['validation' => $error]);
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

    /**
     * Построение параметров для редиректа
     */
    private function buildRedirectParams(Request $request, int $page): array
    {
        $params = $request->except('page');
        if ($page > 1) {
            $params['page'] = $page;
        }

        return $params;
    }

    /**
     * Получение текста запроса для отображения
     */
    private function getQueryText($currentCategory, ?string $search): string
    {
        if ($currentCategory && ! $search) {
            return $currentCategory->name;
        }

        return $search ?: '';
    }

    /**
     * Форматирование ответа категории
     */
    private function formatCategoryResponse($currentCategory): ?array
    {
        if (! $currentCategory) {
            return null;
        }

        return [
            'id' => $currentCategory->id,
            'name' => $currentCategory->name,
            'slug' => $currentCategory->slug,
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
     * Поиск статей (оптимизированный)
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:3|max:255',
            'limit' => 'integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $query = $this->sanitizeInput($request->get('q'));
        $limit = min($request->get('limit', 5), 50); // Ограничиваем максимум

        // Проверяем кеш
        $cacheKey = 'blog_search_' . md5($query) . '_' . $limit;
        $cachedResult = Cache::get($cacheKey);

        if ($cachedResult) {
            return response()->json($cachedResult);
        }

        // Выполняем поиск
        $articles = BlogPost::query()
            ->where('is_published', true)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%");
            })
            ->with(['author', 'categories'])
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();

        $totalResults = BlogPost::query()
            ->where('is_published', true)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%");
            })
            ->count();

        // Рендерим HTML
        $html = view('partials.blog.search-results', [
            'articles' => $articles,
            'total' => $totalResults,
            'query' => $query,
        ])->render();

        $result = [
            'success' => true,
            'message' => __('blog.success.search_results_fetched_successfully'),
            'data' => [
                'html' => $html,
                'total' => $totalResults,
                'query' => $query,
            ],
        ];

        // Кешируем на 5 минут
        Cache::put($cacheKey, $result, self::CACHE_TTL);

        return response()->json($result);
    }

    /**
     * Асинхронное добавление комментария с обновлением списка
     */
    public function storeComment(Request $request, string $slug)
    {

        $userId = Auth::id() ?? $request->ip();
        $action = 'store_comment';
        $limit = 1; // 1 comment
        $window = 60; // per 60 seconds (1 minute)

        // if (! $this->checkAntiFlood($userId, $action, $limit, $window)) {
        //     $errorMessage = __('blogs.comments.flood_protection_message');
        //     if ($request->expectsJson()) {
        //         return response()->json(['success' => false, 'message' => $errorMessage], 429); // 429 Too Many Requests
        //     }

        //     return redirect()->back()->withInput();
        // }
        // --- Anti-Flood Check End ---
        // Валидация входных данных
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|min:2|max:1000',
            'parent_id' => 'nullable|exists:blog_comments,id',
        ], [
            'content.required' => __('blogs.comments.content_required'),
            'content.min' => __('blogs.comments.content_min'),
            'content.max' => __('blogs.comments.content_max'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => __('blog.errors.you_must_be_logged_in_to_submit_a_comment'),
            ], 401);
        }

        $post = BlogPost::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $user = Auth::user();
        $validated = $validator->validated();

        // Проверка на ответ самому себе
        if ($request->filled('parent_id')) {
            $parentComment = BlogComment::findOrFail($request->parent_id);

            if ($parentComment->post_id != $post->id) {
                return response()->json([
                    'success' => false,
                    'message' => __('blog.errors.invalid_parent_comment'),
                ], 422);
            }

            if ($parentComment->email === $user->email) {
                return response()->json([
                    'success' => false,
                    'message' => __('blog.errors.cannot_reply_to_own_comment'),
                ], 422);
            }
        }

        // Создание комментария
        $comment = new BlogComment([
            'post_id' => $post->id,
            'author_name' => $user->name,
            'email' => $user->email,
            'content' => $this->sanitizeInput($validated['content']),
            'status' => CommentStatus::APPROVED, // Автоодобрение для авторизованных пользователей
            'is_spam' => false,
        ]);

        if ($request->filled('parent_id')) {
            $comment->parent_id = $request->parent_id;
        }

        $comment->save();

        // Получаем обновленный список комментариев
        $commentsData = $this->getCommentsData($post);

        return response()->json([
            'success' => true,
            'message' => __('blogs.comments.comment_added_successfully'),
            'comment' => $comment->load('replies'),
            'html' => $commentsData['html'],
            'pagination' => $commentsData['pagination'],
            'hasPagination' => $commentsData['hasPagination'],
            'currentPage' => 1, // Возвращаемся на первую страницу для показа нового комментария
            'totalPages' => $commentsData['totalPages'],
            'count' => $commentsData['count'],
            'commentsCount' => $commentsData['commentsCount'],
        ], 201);
    }

    /**
     * AJAX получение комментариев (для пагинации и обновления)
     */
    public function getComments(Request $request, string $slug)
    {
        $validator = Validator::make($request->all(), [
            'page' => 'integer|min:1|max:1000',
            'sort' => 'string|in:latest,oldest',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $post = BlogPost::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $page = (int) $request->get('page', 1);
        $sort = $request->get('sort', 'latest');

        $commentsData = $this->getCommentsData($post, $page, $sort);

        return response()->json([
            'success' => true,
            'html' => $commentsData['html'],
            'pagination' => $commentsData['pagination'],
            'hasPagination' => $commentsData['hasPagination'],
            'currentPage' => $commentsData['currentPage'],
            'totalPages' => $commentsData['totalPages'],
            'count' => $commentsData['count'],
            'commentsCount' => $commentsData['commentsCount'],
        ]);
    }

    /**
     * Получение данных комментариев для AJAX ответов
     */
    private function getCommentsData(BlogPost $post, int $page = 1, string $sort = 'latest'): array
    {
        $sortDirection = $sort === 'oldest' ? 'asc' : 'desc';

        $comments = BlogComment::where('post_id', $post->id)
            ->where('status', CommentStatus::APPROVED->value)
            ->whereNull('parent_id')
            ->with(['replies' => function ($query) {
                $query->where('status', CommentStatus::APPROVED->value)->orderBy('created_at', 'asc');
            }])
            ->orderBy('created_at', $sortDirection)
            ->paginate(10, ['*'], 'page', $page);

        $commentsCount = BlogComment::where('post_id', $post->id)
            ->where('status', CommentStatus::APPROVED->value)
            ->count();

        // Генерируем HTML для комментариев
        $commentsHtml = '';
        if ($comments->isEmpty()) {
            $commentsHtml = view('components.blog.comment.no-comments')->render();
        } else {
            // Добавляем форму комментария если пользователь авторизован
            if (Auth::check()) {
                $commentsHtml .= view('components.blog.comment.reply-form', [
                    'article' => $post,
                ])->render();
            }

            // Добавляем список комментариев
            foreach ($comments as $comment) {
                $commentsHtml .= view('components.blog.comment.comment', [
                    'comment' => $comment,
                    'slug' => $post->slug,
                ])->render();
            }
        }

        // Генерируем пагинацию
        $paginationHtml = '';
        $hasPagination = $comments->hasPages();

        if ($hasPagination) {
            $paginationHtml = $comments->links('components.blog.comment.async-pagination')->render();
        }

        return [
            'html' => $commentsHtml,
            'pagination' => $paginationHtml,
            'hasPagination' => $hasPagination,
            'currentPage' => $comments->currentPage(),
            'totalPages' => $comments->lastPage(),
            'count' => $comments->count(),
            'commentsCount' => $commentsCount,
        ];
    }
}
