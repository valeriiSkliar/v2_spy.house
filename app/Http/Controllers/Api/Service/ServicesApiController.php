<?php

namespace App\Http\Controllers\Api\Service;

use App\Http\Controllers\FrontendController;
use App\Models\Frontend\Service\Service;
use App\Models\Frontend\Service\ServiceCategory;
use App\Models\Frontend\Rating;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ServicesApiController extends FrontendController
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
            } else if ($selectedBonuses === 'without_discount') {
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
                    'id'   => $category->id,
                    'name' => $category->getTranslation('name', $locale)
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
                    'value'   => $category->id,
                    'label' => $category->getTranslation('name', $locale),
                    'order' => ''
                ];
            });
        $categoriesOptions = $categoriesOptions->merge([
            ['value' => 'all', 'label' => 'All Categories', 'order' => '']
        ]);

        // Ensure 'All Categories' is always first
        $allCategoriesOption = $categoriesOptions->firstWhere('value', 'all');
        $categoriesOptions = $categoriesOptions->reject(function ($option) {
            return $option['value'] === 'all';
        });
        if ($allCategoriesOption) {
            $categoriesOptions->prepend($allCategoriesOption);
        }

        $selectedSort = collect($sortOptions)->first(function ($option) use ($sortField, $sortOrder) {
            return $option['value'] === $sortField && $option['order'] === $sortOrder;
        });

        $selectedBonuses = collect($bonusesOptions)->firstWhere('value', $selectedBonuses) ?? $bonusesOptions[0];
        $selectedCategory = collect($categoriesOptions)->firstWhere('value', $selectedCategory) ?? $categoriesOptions[0];

        $selectedPerPage = collect($perPageOptions)->firstWhere('value', $perPage) ?? $perPageOptions[0];

        // Возвращаем данные в формате JSON
        return response()->json([
            'services' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'total_pages' => $paginator->lastPage()
            ],
            'filters' => $filters,
            'categories' => $categories,
            'sortOptions' => $sortOptions,
            'perPageOptions' => $perPageOptions,
            'bonusesOptions' => $bonusesOptions,
            'categoriesOptions' => $categoriesOptions,
            'selectedSort' => $selectedSort,
            'selectedPerPage' => $selectedPerPage,
            'selectedCategory' => $selectedCategory,
            'selectedBonuses' => $selectedBonuses,
        ]);
    }


    public function getUserRating(string $id)
    {
        $service = Service::findOrFail($id);
        $rating = $service->ratings()->where('user_id', Auth::id())->first();


        return response()->json([
            'rating' => $rating->rating ?? 0,
            'averageRating' => $service?->rating ?? 0,
            'hasRated' => $rating ? true : false
        ]);
    }
}
