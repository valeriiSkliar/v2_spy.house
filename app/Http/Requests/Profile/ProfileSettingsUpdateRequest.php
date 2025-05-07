<?php

namespace App\Http\Requests\Profile;

use App\Enums\Frontend\UserExperience;
use App\Enums\Frontend\UserScopeOfActivity;
use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class ProfileSettingsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'surname' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'experience' => ['nullable', 'string', 'in:' . implode(',', UserExperience::values())],
            'scope_of_activity' => ['nullable', 'string', 'in:' . implode(',', UserScopeOfActivity::values())],
            'user_avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            // 'messanger' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated();

        // Преобразуем дату рождения в формат Y-m-d
        if (isset($validated['date_of_birth']) && $validated['date_of_birth']) {
            $validated['date_of_birth'] = Carbon::parse($validated['date_of_birth'])->format('Y-m-d');
        }

        return $validated;
    }

    protected function prepareForValidation(): void
    {
        // $this->merge([
        //     'experience' => UserExperience::from($this->input('experience'))->value,
        //     'scope' => UserScopeOfActivity::from($this->input('scope'))->value,
        // ]);
    }
}
