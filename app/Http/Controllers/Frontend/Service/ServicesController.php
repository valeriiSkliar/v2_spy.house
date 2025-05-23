<?php

namespace App\Http\Controllers\Frontend\Service;

use App\Http\Controllers\FrontendController;
use App\Models\Frontend\Rating;
use App\Models\Frontend\Service\Service;
use App\Models\Frontend\Service\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ServicesController extends FrontendController
{
    private $indexView = 'pages.services.index';

    private $showView = 'pages.services.show';

    public function index(Request $request)
    {
        $locale = app()->getLocale();
        $sortField = $request->input('sortBy', 'transitions');
        $sortOrder = $request->input('sortOrder', 'desc');
        $selectedCategory = $request->input('category', 'all');
        $selectedBonuses = $request->input('bonuses', 'all');

        // Получаем запрос для закреплённых сервисов
        $pinnedQuery = Service::with('category')
            ->where('status', 'Active')
            ->whereNotNull('pinned_until')
            ->where('pinned_until', '>', now())
            ->orderBy('pinned_until', 'desc');

        // Запрос для не закреплённых сервисов
        $unpinnedQuery = Service::with('category')
            ->where('status', 'Active')
            ->where(function ($query) {
                $query->whereNull('pinned_until')
                    ->orWhere('pinned_until', '<=', now());
            });

        // Фильтр поиска для обоих запросов
        if ($request->filled('search')) {
            $search = strtolower($request->input('search'));
            $searchCondition = function ($q) use ($search) {
                $q->whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("LOWER(JSON_EXTRACT(name, '$.ru')) LIKE ?", ["%{$search}%"]);
            };
            $pinnedQuery->where($searchCondition);
            $unpinnedQuery->where($searchCondition);
        }

        // Фильтр по категории для обоих запросов
        if ($request->filled('category')) {
            if ($selectedCategory === 'all') {
                $pinnedQuery->where('category_id', '!=', null);
                $unpinnedQuery->where('category_id', '!=', null);
            } else {
                $pinnedQuery->where('category_id', $selectedCategory);
                $unpinnedQuery->where('category_id', $selectedCategory);
            }
        }

        // Фильтр по бонусам для обоих запросов
        if ($request->filled('bonuses')) {
            if ($selectedBonuses === 'with_discount') {
                $pinnedQuery->where('code', '!=', '');
                $unpinnedQuery->where('code', '!=', '');
            } elseif ($selectedBonuses === 'without_discount') {
                $pinnedQuery->where(function ($query) {
                    $query->where('code', '')
                        ->orWhereNull('code');
                });
                $unpinnedQuery->where(function ($query) {
                    $query->where('code', '')
                        ->orWhereNull('code');
                });
            }
        }

        // Сортировка для не закреплённых сервисов
        if ($request->filled('sortBy')) {

            if (in_array($sortField, ['transitions', 'rating', 'views'])) {
                $unpinnedQuery->orderBy($sortField, $sortOrder);
            }
        } else {
            $unpinnedQuery->orderBy('created_at', 'desc');
        }

        // Получаем все закреплённые сервисы
        $pinnedServices = $pinnedQuery->get();
        $pinnedCount = $pinnedServices->count();

        // Подготовка переменных пагинации
        $perPage = max((int) $request->input('perPage', 12), 12);
        $currentPage = max((int) $request->input('page', 1), 1);

        // Общее количество не закреплённых
        $totalUnpinned = $unpinnedQuery->count();
        // Общий итоговый счёт: все закреплённые + все не закреплённые
        $totalServices = $pinnedCount + $totalUnpinned;

        if ($currentPage === 1) {
            // На первой странице показываем все закреплённые и дополняем не закреплёнными,
            // чтобы суммарно получилось $perPage элементов (если хватает не закреплённых)
            $unpinnedLimit = max($perPage - $pinnedCount, 0);
            $unpinnedServices = $unpinnedQuery->take($unpinnedLimit)->get();
            // Объединяем закреплённые и не закреплённые
            $allServices = $pinnedServices->concat($unpinnedServices);
        } else {
            // На остальных страницах не показываем закреплённые.
            // При этом нужно пропустить те не закреплённые, которые уже были показаны на первой странице.
            // Смещение для не закреплённых:
            //   offset = (кол-во не закреплённых, показанных на 1-й странице) + (номер страницы - 2) * $perPage
            $unpinnedOffset = max(($perPage - $pinnedCount) + ($currentPage - 2) * $perPage, 0);
            $allServices = $unpinnedQuery->skip($unpinnedOffset)->take($perPage)->get();
        }

        // Преобразование сервисов под нужный формат
        $transformedServices = $allServices->map(function ($service) use ($locale) {
            return [
                'id' => $service->id,
                'name' => $service->getTranslation('name', $locale),
                'description' => $service->getTranslation('description', $locale),
                'code' => $service->code,
                'code_description' => $service->getTranslation('code_description', $locale),
                'category' => $service->category,
                'transitions' => $service->transitions,
                'rating' => $service->rating,
                'views' => $service->views,
                'url' => $service->url,
                'redirect_url' => $service->redirect_url,
                'logo' => $service->logo,
                'is_active_code' => $service->is_active_code,
                'is_pinned' => $service->pinned_until && $service->pinned_until > now(),
            ];
        });

        // Создаём кастомный пагинатор
        $paginator = new LengthAwarePaginator(
            $transformedServices,
            $totalServices,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $filters = $request->only(['search', 'category', 'bonuses', 'perPage', 'page', 'sortBy', 'sortOrder']);

        $categories = ServiceCategory::all()
            ->map(function ($category) use ($locale) {
                return [
                    'id' => $category->id,
                    'name' => $category->getTranslation('name', $locale),
                ];
            });

        $sortOptions = [
            ['value' => 'transitions', 'label' => 'Transitions High to Low', 'order' => 'desc'],
            ['value' => 'transitions', 'label' => 'Transitions Low to High', 'order' => 'asc'],
            ['value' => 'rating', 'label' => 'Rating High to Low', 'order' => 'desc'],
            ['value' => 'rating', 'label' => 'Rating Low to High', 'order' => 'asc'],
            ['value' => 'views', 'label' => 'Views High to Low', 'order' => 'desc'],
            ['value' => 'views', 'label' => 'Views Low to High', 'order' => 'asc'],
        ];

        $perPageOptions = [
            ['value' => 12, 'label' => '12', 'order' => ''],
            ['value' => 24, 'label' => '24', 'order' => ''],
            ['value' => 48, 'label' => '48', 'order' => ''],
            ['value' => 96, 'label' => '96', 'order' => ''],
        ];

        $bonusesOptions = [
            ['value' => 'all', 'label' => 'All Bonuses', 'order' => ''],
            ['value' => 'with_discount', 'label' => 'With Discount', 'order' => ''],
            ['value' => 'without_discount', 'label' => 'Without Discount', 'order' => ''],
        ];

        $categoriesOptions = ServiceCategory::all()
            ->map(function ($category) use ($locale) {
                return [
                    'value' => $category->id,
                    'label' => $category->getTranslation('name', $locale),
                    'order' => '',
                ];
            });
        $categoriesOptions = $categoriesOptions->merge([
            ['value' => 'all', 'label' => 'All Categories', 'order' => ''],
        ]);

        // Ensure 'All Categories' is always first
        $allCategoriesOption = $categoriesOptions->firstWhere('value', 'all');
        $categoriesOptions = $categoriesOptions->reject(function ($option) {
            return $option['value'] === 'all';
        });
        if ($allCategoriesOption) {
            $categoriesOptions->prepend($allCategoriesOption);
        }

        $sortOptionsPlaceholder = 'Sort by — ';
        $perPageOptionsPlaceholder = 'On Page — ';
        $bonusesOptionsPlaceholder = 'Bonuses — ';
        $categoriesOptionsPlaceholder = 'Category — ';

        $selectedSort = collect($sortOptions)->first(function ($option) use ($sortField, $sortOrder) {
            return $option['value'] === $sortField && $option['order'] === $sortOrder;
        });

        $selectedBonuses = collect($bonusesOptions)->firstWhere('value', $selectedBonuses) ?? $bonusesOptions[0];
        $selectedCategory = collect($categoriesOptions)->firstWhere('value', $selectedCategory) ?? $categoriesOptions[0];

        $selectedPerPage = collect($perPageOptions)->firstWhere('value', $perPage) ?? $perPageOptions[0];

        return view($this->indexView, [
            'services' => $paginator,
            'categories' => $categories,
            'filters' => $filters,
            'currentPage' => $currentPage,
            'totalPages' => ceil($totalServices / $perPage),
            'sortOptions' => $sortOptions,
            'perPageOptions' => $perPageOptions,
            'selectedSort' => $selectedSort,
            'selectedPerPage' => $selectedPerPage,
            'categoriesOptions' => $categoriesOptions,
            'selectedCategory' => $selectedCategory,
            'bonusesOptions' => $bonusesOptions,
            'selectedBonuses' => $selectedBonuses,
            'sortOptionsPlaceholder' => $sortOptionsPlaceholder,
            'perPageOptionsPlaceholder' => $perPageOptionsPlaceholder,
            'bonusesOptionsPlaceholder' => $bonusesOptionsPlaceholder,
            'categoriesOptionsPlaceholder' => $categoriesOptionsPlaceholder,
        ]);
    }

    public function show($id)
    {
        $locale = app()->getLocale();
        $service = Service::where('id', $id)
            ->where('status', 'Active')
            ->with('ratings')
            ->with('category')
            ->firstOrFail();

        // Increment views
        $service->increment('views');
        $userRating = Auth::check() ? Auth::user()->getRatingForService($service->id) : null;

        // Get translations for the current locale
        $translatedService = [
            'id' => $service->id,
            'name' => $service->getTranslation('name', $locale),
            'description' => $service->getTranslation('description', $locale),
            'code' => $service->code,
            'code_description' => $service->getTranslation('code_description', $locale),
            'transitions' => $service->transitions,
            'category' => $service->category,
            'rating' => $service->rating,
            'views' => $service->views,
            'url' => $service->url,
            'redirect_url' => $service->redirect_url,
            'logo' => $service->logo,
            'is_active_code' => $service->is_active_code,
            'userRating' => $userRating,
        ];

        $relatedServices = Service::where('status', 'Active')
            ->where('id', '!=', $service->id)
            ->take(5)
            ->inRandomOrder()
            ->with('category')
            ->get()
            ->map(function ($service) use ($locale) {
                return [
                    'id' => $service->id,
                    'name' => $service->getTranslation('name', $locale),
                    'description' => $service->getTranslation('description', $locale),
                    'category' => $service->category,
                    'transitions' => $service->transitions,
                    'rating' => $service->rating,
                    'views' => $service->views,
                    'url' => $service->url,
                    'redirect_url' => $service->redirect_url,
                    'logo' => $service->logo,
                    'is_active_code' => $service->is_active_code,
                ];
            });

        return view($this->showView, [
            'service' => $translatedService,
            'relatedServices' => $relatedServices,
        ]);
    }

    /**
     * Rate a service
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function rate(Request $request, $id, $rating)
    {
        // Validate the rating value
        if (! in_array($rating, [1, 2, 3, 4, 5])) {
            return response()->json(['error' => 'Invalid rating value.'], 400);
        }

        $service = Service::findOrFail($id);
        $userId = Auth::id(); // Get authenticated user ID

        // Check if the user has already rated this service
        $existingRating = Rating::where('service_id', $service->id)
            ->where('user_id', $userId)
            ->first();

        if ($existingRating) {
            // User has already rated, return an error or appropriate response
            return response()->json(['error' => 'You have already rated this service.'], 409); // 409 Conflict
        }

        // Create a new rating
        $newRating = new Rating([
            'service_id' => $service->id,
            'user_id' => $userId, // Use the authenticated user's ID
            'rating' => $rating,
            'comment' => $request->input('comment', ''), // Optionally get comment from request
        ]);

        $newRating->save();

        // Recalculate average rating and update service
        $reviewsCount = $service->ratings()->count();

        $averageRating = $service->averageRating();
        $service->update([
            'rating' => $averageRating,
            'reviews_count' => $reviewsCount,
        ]);

        $formattedRating = number_format($averageRating, 1);
        $ratedHtml = view('components.services.show.rated-rating', [
            'userRating' => $newRating->rating ?? 0,
            'formattedRating' => $formattedRating,
        ])->render();

        return response()->json([
            'success' => true,
            'message' => 'Rating submitted successfully',
            'rating' => $newRating,
            'user_rating' => $rating,
            'average_rating' => number_format($averageRating, 1),
            'reviews_count' => $reviewsCount,
            'ratedHtml' => $ratedHtml,
        ]);
    }

    public function getUserRating(string $id)
    {
        $service = Service::findOrFail($id);
        $rating = $service->ratings()->where('user_id', Auth::id())->first();

        return response()->json([
            'rating' => $rating->rating ?? 0,
            'averageRating' => $service?->rating ?? 0,
            'hasRated' => $rating ? true : false,
        ]);
    }

    /**
     * AJAX endpoint for loading services list
     *
     * @return \Illuminate\Http\Response
     */
    public function ajaxList(Request $request)
    {
        // Call the index method to get the data
        $view = $this->index($request);

        // If this is an AJAX request, return only the services list partial
        if ($request->ajax()) {
            $viewData = $view->getData();
            $services = $viewData['services'];
            $currentPage = $viewData['currentPage'];
            $totalPages = $viewData['totalPages'];

            // Render services list
            $servicesHtml = view('components.services.index.list.services-list', [
                'services' => $services,
            ])->render();

            // Check if pagination should be shown
            $hasPagination = $services->hasPages();

            // Only render pagination if needed
            $paginationHtml = '';
            if ($hasPagination) {
                // Make sure we pass all necessary data for the pagination component
                $paginationHtml = view('components.pagination', [
                    'currentPage' => $currentPage,
                    'totalPages' => $totalPages,
                ])->render();
            }

            // If services is empty, render the empty services component
            if ($services->isEmpty()) {
                $servicesHtml = view('components.services.index.list.empty-services')->render();
            }

            return response()->json([
                'html' => $servicesHtml,
                'pagination' => $paginationHtml,
                'hasPagination' => $hasPagination,
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'count' => $services->count(),
            ]);
        }

        // Otherwise, return the full view
        return $view;
    }
}
