<?php

namespace App\Http\Middleware\Frontend;

use App\Models\Frontend\Blog\PostCategory;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

use function App\Helpers\sanitize_input;

class BlogParametersValidation
{
    /**
     * Обработка входящего запроса
     */
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        // Валидируем только запросы к блогу
        if (!$this->isBlogRequest($request)) {
            return $next($request);
        }

        // Выполняем валидацию параметров
        $validationResult = $this->validateBlogParameters($request);

        if ($validationResult !== true) {
            return $this->handleValidationFailure($request, $validationResult);
        }

        // Санитизируем параметры
        $this->sanitizeParameters($request);

        return $next($request);
    }


    /**
     * Проверка является ли запрос блоговым
     * Зачем: применение валидации только к релевантным маршрутам
     */
    private function isBlogRequest(Request $request): bool
    {
        $blogRoutes = [
            'blog.index',
            'blog.category',
            'blog.search',
            'api.blog.list',
            'api.blog.search'
        ];

        $currentRoute = $request->route()?->getName();

        return in_array($currentRoute, $blogRoutes) ||
            str_starts_with($request->getPathInfo(), '/blog') ||
            str_starts_with($request->getPathInfo(), '/api/blog');
    }

    /**
     * Валидация параметров блога
     * Зачем: обеспечение корректности всех входных данных
     */
    private function validateBlogParameters(Request $request)
    {
        $rules = $this->getValidationRules($request);
        $messages = $this->getValidationMessages();

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return $validator->errors();
        }

        // Дополнительная валидация логики
        return $this->validateBusinessLogic($request);
    }

    /**
     * Получение правил валидации
     * Зачем: централизованное определение правил валидации
     */
    private function getValidationRules(Request $request): array
    {
        $rules = [
            'page' => [
                'integer',
                'min:1',
                'max:1000' //  ограничение для предотвращения злоупотреблений
            ],
            'category' => [
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9\-_]+$/' // Только безопасные символы
            ],
            'search' => [
                'string',
                'max:255',
                'min:1'
            ],
            'q' => [ // Для API поиска
                'string',
                'max:255',
                'min:3'
            ],
            'sort' => [
                'string',
                'in:date,title,views,rating' // Только разрешенные поля сортировки
            ],
            'order' => [
                'string',
                'in:asc,desc'
            ],
            'limit' => [
                'integer',
                'min:1',
                'max:50' // Ограничение для API запросов
            ]
        ];

        // Добавляем специфичные правила для разных типов запросов
        if ($request->routeIs('api.*')) {
            $rules = array_merge($rules, $this->getApiSpecificRules());
        }

        return $rules;
    }

    /**
     * Получение правил специфичных для API
     * Зачем: более строгие ограничения для API запросов
     */
    private function getApiSpecificRules(): array
    {
        return [
            'format' => [
                'string',
                'in:json,html'
            ],
            'include' => [
                'string',
                'regex:/^[a-zA-Z,_]+$/' // Для указания связанных моделей
            ]
        ];
    }

    /**
     * Получение сообщений валидации
     * Зачем: понятные сообщения об ошибках для пользователей
     */
    private function getValidationMessages(): array
    {
        return [
            'page.integer' => __('validation.blog.page.integer'),
            'page.min' => __('validation.blog.page.min'),
            'page.max' => __('validation.blog.page.max'),
            'category.regex' => __('validation.blog.category.regex'),
            'category.max' => __('validation.blog.category.max'),
            'search.max' => __('validation.blog.search.max'),
            'search.min' => __('validation.blog.search.min'),
            'q.min' => __('validation.blog.q.min'),
            'q.max' => __('validation.blog.q.max'),
            'sort.in' => __('validation.blog.sort.in'),
            'order.in' => __('validation.blog.order.in'),
            'limit.min' => __('validation.blog.limit.min'),
            'limit.max' => __('validation.blog.limit.max')
        ];
    }

    /**
     * Валидация бизнес-логики
     * Зачем: проверка логических ограничений сверх базовой валидации
     */
    private function validateBusinessLogic(Request $request)
    {
        $errors = [];

        // Проверка комбинации параметров поиска
        if ($request->has(['search', 'q'])) {
            $errors['search'] = __('validation.blog.search.combination');
        }

        // Проверка категории на существование (если указана)
        if ($request->has('category')) {
            $categoryExists = PostCategory::where('slug', $request->category)->exists();
            if (!$categoryExists) {
                $errors['category'] = __('validation.blog.category.not_found');
            }
        }

        // Проверка разумности комбинации параметров
        if ($request->has('page') && $request->integer('page') > 1 && $request->has('search')) {
            // При поиске страница должна сбрасываться на 1
            $errors['page'] = __('validation.blog.page.search');
        }

        return empty($errors) ? true : $errors;
    }

    /**
     * Санитизация параметров
     * Зачем: очистка входных данных от потенциально опасного контента
     */
    private function sanitizeParameters(Request $request): void
    {
        // Санитизируем поисковые запросы
        if ($request->has('search')) {
            $sanitized = $this->sanitizeSearchQuery($request->input('search'));
            $request->merge(['search' => $sanitized]);
        }

        if ($request->has('q')) {
            $sanitized = $this->sanitizeSearchQuery($request->input('q'));
            $request->merge(['q' => $sanitized]);
        }

        // Приводим к нижнему регистру категорию для консистентности
        if ($request->has('category')) {
            $category = trim(strtolower($request->input('category')));
            $request->merge(['category' => $category]);
        }

        // Нормализуем порядок сортировки
        if ($request->has('order')) {
            $order = strtolower(trim($request->input('order')));
            $request->merge(['order' => $order]);
        }
    }

    /**
     * Санитизация поискового запроса
     * Зачем: очистка от SQL инъекций и XSS
     */
    private function sanitizeSearchQuery(string $query): string
    {
        // Убираем опасные символы
        $query = preg_replace('/[<>"\']/', '', $query);

        // Убираем SQL ключевые слова
        $sqlKeywords = ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'DROP', 'UNION', 'SCRIPT'];
        $query = str_ireplace($sqlKeywords, '', $query);

        // Убираем лишние пробелы
        $query = preg_replace('/\s+/', ' ', trim($query));


        // Anti-XSS
        if ($query) {
            $query = sanitize_input($query);
        }

        return $query;
    }

    /**
     * Обработка ошибок валидации
     * Зачем: корректный ответ при неверных параметрах
     */
    private function handleValidationFailure(Request $request, $errors): Response
    {
        // Для AJAX запросов возвращаем JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => __('validation.blog.validation_failed'),
                'errors' => $errors,
                'redirect' => true,
                'url' => $this->getCleanRedirectUrl($request)
            ], 422);
        }

        // Для обычных запросов делаем редирект на чистую страницу
        return redirect($this->getCleanRedirectUrl($request))
            ->withErrors($errors)
            ->with('warning', __('validation.blog.validation_failed'));
    }

    /**
     * Получение "чистого" URL для редиректа
     * Зачем: возврат к базовому состоянию при ошибках
     */
    private function getCleanRedirectUrl(Request $request): string
    {
        // Определяем базовый маршрут
        $routeName = $request->route()?->getName();

        switch ($routeName) {
            case 'blog.category':
                // Для категорий сохраняем только валидную категорию
                $category = $request->route('slug');
                if ($this->isValidCategorySlug($category)) {
                    return route('blog.category', $category);
                }
                return route('blog.index');

            case 'blog.search':
                // Для поиска переходим на главную
                return route('blog.index');

            case 'api.blog.list':
            case 'api.blog.search':
                // Для API возвращаем базовые маршруты
                return route('blog.index');

            default:
                return route('blog.index');
        }
    }

    /**
     * Проверка валидности slug категории
     * Зачем: дополнительная проверка для маршрутов с параметрами
     */
    private function isValidCategorySlug(?string $slug): bool
    {
        if (!$slug) return false;

        return PostCategory::where('slug', $slug)->exists();
    }

    /**
     * Логирование подозрительных запросов
     * Зачем: мониторинг безопасности и злоупотреблений
     */
    private function logSuspiciousRequest(Request $request, $errors): void
    {
        // Определяем подозрительные паттерны
        $suspicious = [
            'page' => $request->integer('page', 0) > 100,
            'malformed_search' => $request->has('search') && strlen($request->input('search')) > 200,
            'sql_injection' => $this->detectSQLInjection($request),
            'xss_attempt' => $this->detectXSSAttempt($request)
        ];

        $isSuspicious = array_filter($suspicious);

        if (!empty($isSuspicious)) {
            Log::warning('Suspicious blog request detected', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'parameters' => $request->all(),
                'suspicious_flags' => array_keys($isSuspicious),
                'validation_errors' => $errors
            ]);
        }
    }

    /**
     * Обнаружение попыток SQL инъекций
     * Зачем: раннее обнаружение атак
     */
    private function detectSQLInjection(Request $request): bool
    {
        $sqlPatterns = [
            '/union\s+select/i',
            '/\'\s*or\s*\'/i',
            '/\'\s*;\s*drop/i',
            '/\'\s*;\s*delete/i',
            '/\/\*.*\*\//i'
        ];

        $queryString = $request->getQueryString();
        if (!$queryString) return false;

        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $queryString)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Обнаружение попыток XSS
     * Зачем: защита от межсайтового скриптинга
     */
    private function detectXSSAttempt(Request $request): bool
    {
        $xssPatterns = [
            '/<script/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe/i',
            '/<object/i'
        ];

        $allInput = implode(' ', $request->all());

        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $allInput)) {
                return true;
            }
        }

        return false;
    }
}
