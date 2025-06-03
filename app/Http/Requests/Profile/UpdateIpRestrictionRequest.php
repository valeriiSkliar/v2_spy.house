<?php

namespace App\Http\Requests\Profile;

use App\Http\Requests\BaseRequest;

class UpdateIpRestrictionRequest extends BaseRequest
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
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $ipRestrictions = $this->input('ip_restrictions');
            if (! empty($ipRestrictions)) {
                $ips = array_filter(array_map(function ($ip) {
                    return $this->sanitizeInput($ip);
                }, explode("\n", $ipRestrictions)));

                foreach ($ips as $ip) {
                    if (! $this->isValidIp($ip)) {
                        $validator->errors()->add('ip_restrictions', __('validation.ip_restrictions.invalid'));
                        break;
                    }
                }
            }
        });
    }

    /**
     * Check if the IP address is valid
     */
    protected function isValidIp(string $ip): bool
    {
        // Проверка одиночного IP-адреса
        return filter_var($ip, FILTER_VALIDATE_IP);
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'password.required' => __('validation.validation_error'),
            'password.current_password' => __('validation.validation_error'),
        ];
    }
}
