<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\ProfileSettingsUpdateRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Api\Profile\ChangePasswordApiRequest;
use Illuminate\Support\Facades\Hash;

class ProfileSettingsController extends Controller
{
    /**
     * Update user's personal settings asynchronously
     * 
     * @param ProfileSettingsUpdateRequest $request
     * @return JsonResponse
     */
    public function update(ProfileSettingsUpdateRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $validatedData = $request->validated();
            $settingsData = [];

            // Process validated data fields
            $fields = [
                'login',
                'name',
                'surname',
                'date_of_birth',
                'experience',
                'scope_of_activity',
                'messengers',
                'whatsapp_phone',
                'viber_phone',
                'telegram'
            ];

            foreach ($fields as $field) {
                if (isset($validatedData[$field])) {
                    $settingsData[$field] = $validatedData[$field];
                }
            }

            // Update user record
            $user->fill($settingsData);
            $user->save();

            // Return success response with updated user data
            return response()->json([
                'success' => true,
                'message' => __('profile.personal_info.update_success'),
                'user' => [
                    'login' => $user->login,
                    'name' => $user->name,
                    'surname' => $user->surname,
                    'experience' => $user->experience,
                    'scope_of_activity' => $user->scope_of_activity,
                    'telegram' => $user->telegram,
                    'viber_phone' => $user->viber_phone,
                    'whatsapp_phone' => $user->whatsapp_phone,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating profile settings: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => __('profile.personal_info.update_error'),
            ], 500);
        }
    }



    public function updatePasswordApi(ChangePasswordApiRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        // Опционально: отозвать все другие токены пользователя для повышения безопасности
        // $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

        return response()->json(['message' => __('profile.messages.password_updated_successfully')]);
    }
}
