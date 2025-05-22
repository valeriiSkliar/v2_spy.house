<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class UpdateIpRestrictionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'ip_restrictions' => ['nullable', 'string'],
            'password' => ['required', 'current_password'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $ipRestrictions = $this->input('ip_restrictions');
            if (!empty($ipRestrictions)) {
                $ips = array_filter(array_map('trim', explode("\n", $ipRestrictions)));
                foreach ($ips as $ip) {
                    if (!$this->isValidIp($ip)) {
                        $validator->errors()->add('ip_restrictions', __('validation.ip', ['attribute' => 'IP address']));
                        break;
                    }
                }
            }
        });
    }

    /**
     * Check if the IP address or range is valid
     *
     * @param string $ip
     * @return bool
     */
    protected function isValidIp(string $ip): bool
    {
        // Проверка одиночного IP-адреса
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }

        // Проверка CIDR формата (например, 192.168.1.0/24)
        if (preg_match('/^(\d{1,3}\.){3}\d{1,3}\/\d{1,2}$/', $ip)) {
            list($ipPart, $mask) = explode('/', $ip);
            return filter_var($ipPart, FILTER_VALIDATE_IP) && $mask >= 0 && $mask <= 32;
        }

        // Проверка диапазона (например, 192.168.1.1-192.168.1.255)
        if (preg_match('/^(\d{1,3}\.){3}\d{1,3}-(\d{1,3}\.){3}\d{1,3}$/', $ip)) {
            list($start, $end) = explode('-', $ip);
            return filter_var($start, FILTER_VALIDATE_IP) && filter_var($end, FILTER_VALIDATE_IP);
        }

        return false;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'password.required' => __('profile.validation.password_required'),
            'password.current_password' => __('profile.validation.current_password_incorrect'),
        ];
    }
}
