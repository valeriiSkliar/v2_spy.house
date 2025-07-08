<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\FrontendController;
use App\Models\Creative;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class FavoriteController extends FrontendController
{
    /**
     * Display a listing of user's favorite creatives.
     * 
     * @OA\Get(
     *     path="/api/favorites",
     *     operationId="getFavorites",
     *     tags={"Креативы - Избранное"},
     *     summary="Получить список избранных креативов",
     *     description="Возвращает список всех избранных креативов для аутентифицированного пользователя",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Номер страницы",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="perPage",
     *         in="query",
     *         description="Количество элементов на странице",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список избранных креативов успешно получен",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=123),
     *                 @OA\Property(property="title", type="string", example="Creative Title"),
     *                 @OA\Property(property="description", type="string", example="Creative Description"),
     *                 @OA\Property(property="addedAt", type="string", format="date-time")
     *             )),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="total", type="integer", example=42),
     *                 @OA\Property(property="currentPage", type="integer", example=1),
     *                 @OA\Property(property="lastPage", type="integer", example=5),
     *                 @OA\Property(property="perPage", type="integer", example=12)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Пользователь не аутентифицирован"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min($request->get('perPage', 12), 100);

        $favorites = $user->favoriteCreatives()
            ->with(['country', 'language', 'advertismentNetwork', 'browser'])
            ->paginate($perPage);

        $creativesData = $favorites->getCollection()->map(function ($creative) {
            $data = $creative->toCreativeArray();
            $data['addedAt'] = $creative->pivot->created_at->toISOString();
            return $data;
        });

        return response()->json([
            'status' => 'success',
            'data' => $creativesData,
            'meta' => [
                'total' => $favorites->total(),
                'currentPage' => $favorites->currentPage(),
                'lastPage' => $favorites->lastPage(),
                'perPage' => $favorites->perPage(),
                'hasNextPage' => $favorites->hasMorePages(),
                'hasPrevPage' => $favorites->currentPage() > 1,
            ]
        ]);
    }

    /**
     * Store a newly created favorite.
     * 
     * @OA\Post(
     *     path="/api/favorites",
     *     operationId="storeFavorite",
     *     tags={"Креативы - Избранное"},
     *     summary="Добавить креатив в избранное",
     *     description="Добавляет указанный креатив в список избранного пользователя",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="creative_id", type="integer", example=123, description="ID креатива")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Креатив успешно добавлен в избранное",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Creative added to favorites"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="creativeId", type="integer", example=123),
     *                 @OA\Property(property="isFavorite", type="boolean", example=true),
     *                 @OA\Property(property="totalFavorites", type="integer", example=43),
     *                 @OA\Property(property="addedAt", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Креатив не найден"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Креатив уже в избранном"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'creative_id' => 'required|integer|exists:creatives,id'
            ]);

            $user = $request->user();
            $creativeId = $request->creative_id;

            // Проверяем, существует ли креатив
            $creative = Creative::findOrFail($creativeId);

            // Проверяем, не добавлен ли уже в избранное
            if ($user->hasFavoriteCreative($creativeId)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Creative already in favorites',
                    'data' => [
                        'creativeId' => $creativeId,
                        'isFavorite' => true,
                        'totalFavorites' => $user->getFavoritesCount()
                    ]
                ], 409);
            }

            // Добавляем в избранное
            $favorite = Favorite::addToFavorites($user->id, $creativeId);

            return response()->json([
                'status' => 'success',
                'message' => 'Creative added to favorites',
                'data' => [
                    'creativeId' => $creativeId,
                    'isFavorite' => true,
                    'totalFavorites' => $user->getFavoritesCount(),
                    'addedAt' => $favorite->created_at->toISOString()
                ]
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add creative to favorites: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified favorite.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $favorite = $user->favoriteCreatives()
            ->with(['country', 'language', 'advertismentNetwork', 'browser'])
            ->where('creative_id', $id)
            ->first();

        if (!$favorite) {
            return response()->json([
                'status' => 'error',
                'message' => 'Creative not found in favorites'
            ], 404);
        }

        $data = $favorite->toCreativeArray();
        $data['addedAt'] = $favorite->pivot->created_at->toISOString();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    /**
     * Remove the specified favorite.
     * 
     * @OA\Delete(
     *     path="/api/favorites/{creativeId}",
     *     operationId="removeFavorite",
     *     tags={"Креативы - Избранное"},
     *     summary="Удалить креатив из избранного",
     *     description="Удаляет указанный креатив из списка избранного пользователя",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="creativeId",
     *         in="path",
     *         description="ID креатива",
     *         required=true,
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Креатив успешно удален из избранного",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Creative removed from favorites"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="creativeId", type="integer", example=123),
     *                 @OA\Property(property="isFavorite", type="boolean", example=false),
     *                 @OA\Property(property="totalFavorites", type="integer", example=41),
     *                 @OA\Property(property="removedAt", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Креатив не найден в избранном"
     *     )
     * )
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            $creativeId = (int)$id;

            // Проверяем, есть ли креатив в избранном
            if (!$user->hasFavoriteCreative($creativeId)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Creative not found in favorites',
                    'data' => [
                        'creativeId' => $creativeId,
                        'isFavorite' => false,
                        'totalFavorites' => $user->getFavoritesCount()
                    ]
                ], 404);
            }

            // Удаляем из избранного
            $removed = Favorite::removeFromFavorites($user->id, $creativeId);

            if ($removed) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Creative removed from favorites',
                    'data' => [
                        'creativeId' => $creativeId,
                        'isFavorite' => false,
                        'totalFavorites' => $user->getFavoritesCount(),
                        'removedAt' => now()->toISOString()
                    ]
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove creative from favorites'
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to remove creative from favorites: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get count of user's favorite creatives.
     * 
     * @OA\Get(
     *     path="/api/favorites/count",
     *     operationId="getFavoritesCount",
     *     tags={"Креативы - Избранное"},
     *     summary="Получить количество избранных креативов",
     *     description="Возвращает текущее количество креативов в избранном для аутентифицированного пользователя",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Количество избранного успешно получено",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="count", type="integer", example=42),
     *                 @OA\Property(property="lastUpdated", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function count(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $user->getFavoritesCount();

        return response()->json([
            'status' => 'success',
            'data' => [
                'count' => $count,
                'lastUpdated' => now()->toISOString()
            ]
        ]);
    }

    /**
     * Check if specific creative is in user's favorites.
     * 
     * @OA\Get(
     *     path="/api/favorites/check/{creativeId}",
     *     operationId="checkFavorite",
     *     tags={"Креативы - Избранное"},
     *     summary="Проверить, находится ли креатив в избранном",
     *     description="Проверяет, добавлен ли указанный креатив в избранное пользователя",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="creativeId",
     *         in="path",
     *         description="ID креатива",
     *         required=true,
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Статус избранного успешно получен",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="creativeId", type="integer", example=123),
     *                 @OA\Property(property="isFavorite", type="boolean", example=true),
     *                 @OA\Property(property="addedAt", type="string", format="date-time", nullable=true)
     *             )
     *         )
     *     )
     * )
     */
    public function check(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $creativeId = (int)$id;

        $favorite = Favorite::where('user_id', $user->id)
            ->where('creative_id', $creativeId)
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'creativeId' => $creativeId,
                'isFavorite' => (bool)$favorite,
                'addedAt' => $favorite ? $favorite->created_at->toISOString() : null
            ]
        ]);
    }
}
