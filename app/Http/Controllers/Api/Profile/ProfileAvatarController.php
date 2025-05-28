<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Services\Common\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProfileAvatarController extends Controller
{
    /**
     * Upload a new user avatar asynchronously
     */
    public function upload(Request $request): JsonResponse
    {
        // Validate the uploaded file
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:png|max:200',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('avatar'),
            ], 422);
        }

        try {
            $user = $request->user();
            $imageService = app(ImageService::class);

            // Process the avatar image
            $avatarFile = $request->file('avatar');


            $avatarPath = $imageService->replace(
                $avatarFile,
                $user->user_avatar,
                'avatars'
            );

            // Save the user avatar path and metadata
            $user->user_avatar = $avatarPath;
            $user->save();

            // Return success response with avatar details
            return response()->json([
                'success' => true,
                'message' => __('profile.success.photo_updated'),
                'avatar' => [
                    'url' => asset('storage/' . $avatarPath),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error uploading avatar: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => __('profile.error.photo_update_error'),
            ], 500);
        }
    }
}
