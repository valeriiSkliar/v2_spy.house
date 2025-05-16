<?php

namespace App\Http\Controllers\Api\Landing;

use App\Http\Controllers\Frontend\Landing\BaseLandingsPageController;
use App\Models\Frontend\Landings\WebsiteDownloadMonitor;
use App\Services\App\Landings\LandingDownloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Rules\ValidUrl; // Убедитесь, что это правило существует или замените его
use Illuminate\Support\Facades\Auth;

class LandingsPageApiController extends BaseLandingsPageController
{


    /**
     * Handles AJAX request to get a list of landings.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function ajaxList(Request $request): JsonResponse
    {
        $viewConfig = $this->getViewConfig();
        $data = parent::getData($request);

        $html = $this->renderContentWrapperView($data);

        $responseObject = [
            'success' => true,
            'message' => __('landings.successfully_loaded_message_text'),
            'data' => [
                'table_html' => $html,

            ],
        ];

        return $this->jsonResponse($responseObject);
    }

    /**
     * Handles AJAX request to store a new landing.
     *
     * @param Request $request
     * @param LandingDownloadService $landingDownloadService
     * @return JsonResponse
     */
    public function ajaxStore(Request $request, LandingDownloadService $landingDownloadService): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => ['required', 'url', 'max:2048', new ValidUrl()],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('common.error_occurred_common_message'), // Общее сообщение об ошибке
                'errors' => $validator->errors(),
            ], 422);
        }

        // Anti-flood check (используем конфигурацию из antiflood.php)
        if (!$this->antiFloodService->isAllowed('landings.store', config('antiflood.landings.store.rate', 5), config('antiflood.landings.store.time', 60))) {
            return response()->json([
                'success' => false,
                'message' => __('common.anti_flood_message_text'),
            ], 429); // Too Many Requests
        }

        try {
            $landing = $landingDownloadService->createAndDispatch(Auth::id(), $request->input('url'));

            $landingHtml = view('components.landings.table.row', ['landing' => $landing, 'viewConfig' => $this->getViewConfig()])->render();

            return response()->json([
                'success' => true,
                'message' => __('landings.successfully_added_to_queue_message_text'),
                'landing_id' => $landing->id,
                'landing_html' => $landingHtml,
            ]);
        } catch (\Exception $e) {
            Log::error('Error storing landing via AJAX: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => __('common.error_occurred_common_message'),
            ], 500);
        }
    }

    /**
     * Handles AJAX request to delete a landing.
     *
     * @param WebsiteDownloadMonitor $landing
     * @return JsonResponse
     */
    public function ajaxDestroy(WebsiteDownloadMonitor $landing): JsonResponse
    {
        $this->authorize('delete', $landing); // Использует WebsiteDownloadMonitorPolicy

        try {
            if ($landing->path_to_archive && Storage::disk('landings')->exists($landing->path_to_archive)) {
                // Удаляем всю директорию лендинга, так как HTTrack создает поддиректорию с именем сайта
                $directoryPath = dirname($landing->path_to_archive);
                if ($directoryPath !== '.') { // Предосторожность, чтобы не удалить корень диска 'landings'
                    Storage::disk('landings')->deleteDirectory($directoryPath);
                } else {
                    // Если путь к архиву не содержит поддиректорий, удаляем только сам файл
                    Storage::disk('landings')->delete($landing->path_to_archive);
                }
            }
            $landing->delete();

            return response()->json([
                'success' => true,
                'message' => __('landings.successfully_deleted_message_text'),
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting landing via AJAX: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => __('common.error_occurred_common_message'),
            ], 500);
        }
    }
}
