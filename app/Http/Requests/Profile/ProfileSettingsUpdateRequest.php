<?php

namespace App\Http\Requests\Profile;

use App\Enums\Frontend\UserExperience;
use App\Enums\Frontend\UserScopeOfActivity;
use App\Http\Requests\BaseRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ProfileSettingsUpdateRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'login' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'surname' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'experience' => ['nullable', 'string', 'in:' . implode(',', UserExperience::names())],
            'scope_of_activity' => ['nullable', 'string', 'in:' . implode(',', UserScopeOfActivity::names())],
            'user_avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'telegram' => ['nullable', 'string', 'max:255'],
            'viber_phone' => ['nullable', 'string', 'max:255'],
            'whatsapp_phone' => ['nullable', 'string', 'max:255'],
            'messengers' => ['nullable', 'array', 'max:3'],
            'messengers.*.phone' => ['nullable', 'string', 'max:255'],
            'messengers.*.type' => ['nullable', 'string', 'in:telegram,viber,whatsapp'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated();

        // Преобразуем дату рождения в формат Y-m-d
        if (isset($validated['date_of_birth']) && $validated['date_of_birth']) {
            $validated['date_of_birth'] = Carbon::parse($validated['date_of_birth'])->format('Y-m-d');
        }

        if (isset($validated['telegram'])) {
            $validated['telegram'] = $validated['telegram'] ?? null;
        }

        if (isset($validated['viber_phone'])) {
            $validated['viber_phone'] = $validated['viber_phone'] ?? null;
        }

        if (isset($validated['whatsapp_phone'])) {
            $validated['whatsapp_phone'] = $validated['whatsapp_phone'] ?? null;
        }

        // Преобразуем массив мессенджеров в отдельные поля
        // if (isset($validated['messengers'])) {
        //     $messengers = array_filter($validated['messengers'], function ($messenger) {
        //         return !empty($messenger['phone']) && !empty($messenger['type']);
        //     });

        //     $validated['telegram'] = null;
        //     $validated['viber_phone'] = null;
        //     $validated['whatsapp_phone'] = null;

        //     foreach ($messengers as $messenger) {
        //         switch ($messenger['type']) {
        //             case 'telegram':
        //                 $validated['telegram'] = $messenger['phone'];
        //                 break;
        //             case 'viber':
        //                 $validated['viber_phone'] = $messenger['phone'];
        //                 break;
        //             case 'whatsapp':
        //                 $validated['whatsapp_phone'] = $messenger['phone'];
        //                 break;
        //         }
        //     }

        //     unset($validated['messengers']);
        // }

        return $validated;
    }

    protected function prepareForValidation(): void
    {
        // dd($this->all());

        $data = $this->all();

        // Санитизация простых строковых полей
        $stringFields = ['login', 'name', 'surname'];
        foreach ($stringFields as $field) {
            if ($this->has($field) && $this->input($field) !== null) {
                $data[$field] = $this->sanitizeInput($this->input($field));
            }
        }

        // Санитизация полей мессенджеров
        if ($this->has('messengers') && is_array($this->input('messengers'))) {
            $messengers = $this->input('messengers');
            foreach ($messengers as $key => $messenger) {
                if (isset($messenger['phone']) && $messenger['phone'] !== null) {
                    $messengers[$key]['phone'] = $this->sanitizeInput($messenger['phone']);
                }
            }
            $data['messengers'] = $messengers;
        }
        if ($this->has('visible_value')) {
            // Удаляем поле visible_value, так как оно используется только для отображения в форме
            // и не должно быть частью валидации или сохранения данных
            unset($data['visible_value']);
        }

        $this->merge($data);
    }
}
