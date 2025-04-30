<?php

namespace App\Http\Controllers\Frontend\Service;

use App\Http\Controllers\FrontendController;
use App\Models\Frontend\Service\Service;
use App\Models\Frontend\Service\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ServicesController extends FrontendController
{
    public function index(Request $request)
    {
        $locale = app()->getLocale();

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
            $categoryId = $request->input('category');
            $pinnedQuery->where('category_id', $categoryId);
            $unpinnedQuery->where('category_id', $categoryId);
        }

        // Сортировка для не закреплённых сервисов
        if ($request->filled('sortBy')) {
            $sortField = $request->input('sortBy');
            $sortOrder = $request->input('sortOrder', 'desc');

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
        $perPage = max((int)$request->input('perPage', 12), 12);
        $currentPage = max((int)$request->input('page', 1), 1);

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
                'id'                => $service->id,
                'name'              => $service->getTranslation('name', $locale),
                'description'       => $service->getTranslation('description', $locale),
                'code'              => $service->code,
                'code_description'  => $service->getTranslation('code_description', $locale),
                'category'          => $service->category,
                'transitions'       => $service->transitions,
                'rating'            => $service->rating,
                'views'             => $service->views,
                'url'               => $service->url,
                'redirect_url'      => $service->redirect_url,
                'logo'              => $service->logo,
                'is_active_code'    => $service->is_active_code,
                'is_pinned'         => $service->pinned_until && $service->pinned_until > now(),
            ];
        });

        // Создаём кастомный пагинатор
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $transformedServices,
            $totalServices,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $filters = $request->only(['search', 'category', 'perPage', 'page', 'sortBy', 'sortOrder']);

        $categories = ServiceCategory::all()
            ->map(function ($category) use ($locale) {
                return [
                    'id'   => $category->id,
                    'name' => $category->getTranslation('name', $locale)
                ];
            });

        return view('frontend.services.index', [
            'services'   => $paginator,
            'categories' => $categories,
            'filters'    => $filters
        ]);
    }

    // public function show($id)
    // {
    //     $locale = app()->getLocale();
    //     $service = Service::where('id', $id)
    //         ->where('status', 'Active')
    //         ->with('ratings')
    //         ->with('category')
    //         ->firstOrFail();

    //     // Increment views
    //     $service->increment('views');

    //     // Get translations for the current locale
    //     $translatedService = [
    //         'id' => $service->id,
    //         'name' => $service->getTranslation('name', $locale),
    //         'description' => $service->getTranslation('description', $locale),
    //         'code' => $service->code,
    //         'code_description' => $service->getTranslation('code_description', $locale),
    //         'transitions' => $service->transitions,
    //         'category' => $service->category,
    //         'rating' => $service->rating,
    //         'views' => $service->views,
    //         'url' => $service->url,
    //         'redirect_url' => $service->redirect_url,
    //         'logo' => $service->logo,
    //         'is_active_code' => $service->is_active_code,
    //     ];


    //     $relatedServices = Service::where('status', 'Active')
    //         ->where('id', '!=', $service->id)
    //         ->take(4)
    //         ->inRandomOrder()
    //         ->with('category')
    //         ->get()
    //         ->map(function ($service) use ($locale) {
    //             return [
    //                 'id' => $service->id,
    //                 'name' => $service->getTranslation('name', $locale),
    //                 'description' => $service->getTranslation('description', $locale),
    //                 'category' => $service->category,
    //                 'transitions' => $service->transitions,
    //                 'rating' => $service->rating,
    //                 'views' => $service->views,
    //                 'url' => $service->url,
    //                 'redirect_url' => $service->redirect_url,
    //                 'logo' => $service->logo,
    //                 'is_active_code' => $service->is_active_code,
    //             ];
    //         });

    //     return Inertia::render('ServicePage/Show', [
    //         'service' => $translatedService,
    //         'relatedServices' => $relatedServices,
    //     ]);
    // }

    // public function rate(Request $request, string $id)
    // {
    //     $request->validate([
    //         'rating' => 'required|integer|min:1|max:5',
    //         'review' => 'nullable|string|max:1000'
    //     ]);

    //     $service = Service::findOrFail($id);

    //     // Check if user has already rated this service
    //     $existingRating = $service->ratings()->where('user_id', Auth::id())->first();

    //     if ($existingRating) {
    //         return response()->json([
    //             'message' => 'You have already rated this service'
    //         ], 422);
    //     }

    //     // Create new rating
    //     $rating = $service->ratings()->create([
    //         'user_id' => Auth::id(),
    //         'rating' => $request->rating,
    //         'review' => $request->review
    //     ]);

    //     // Update average rating
    //     $averageRating = $service->ratings()->avg('rating');
    //     $service->update(['rating' => $averageRating]);

    //     return response()->json([
    //         'message' => 'Rating submitted successfully',
    //         'rating' => $rating,
    //         'averageRating' => $averageRating
    //     ]);
    // }

    // public function getUserRating(string $id)
    // {
    //     $service = Service::findOrFail($id);
    //     $rating = $service->ratings()->where('user_id', Auth::id())->first();

    //     return response()->json([
    //         'rating' => $rating->rating ?? 0,
    //         'averageRating' => $service?->rating ?? 0,
    //         'hasRated' => $rating ? true : false
    //     ]);
    // }
}
