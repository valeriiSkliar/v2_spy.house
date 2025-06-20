<?php

namespace App\Traits\Frontend;

use App\Models\Frontend\Blog\BlogPost;
use App\Models\Frontend\Blog\PostCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait BlogQueryTrait
{
    const ARTICLES_PER_PAGE = 12;

    /**
     * Построение запроса статей с учетом фильтров
     */
    protected function buildArticlesQuery(?string $search, ?string $categorySlug, string $sort = 'latest', string $direction = 'desc'): array
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
    protected function applySorting($query, string $sort, string $direction): void
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
            return redirect()->route('blog.index');
        }

        // Вычисляем максимальное количество страниц
        $maxPages = $this->calculateMaxPages($totalCount, $currentPage);

        // Если текущая страница превышает максимум
        if ($currentPage > $maxPages && $maxPages > 0) {
            $redirectPage = $maxPages;
            return redirect()->route('blog.index', $this->buildRedirectParams($request, $redirectPage));
        }

        return null;
    }

    /**
     * Вычисление максимального количества страниц
     */
    protected function calculateMaxPages(int $totalCount, int $currentPage): int
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
    protected function getArticlesForPage($query, int $currentPage, int $totalCount): array
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
     * Построение параметров для редиректа
     */
    protected function buildRedirectParams(Request $request, int $page): array
    {
        $params = $request->except('page');
        if ($page > 1) {
            $params['page'] = $page;
        }

        return $params;
    }
}
