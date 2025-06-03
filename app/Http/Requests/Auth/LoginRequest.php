<?php

namespace App\Http\Requests\Auth;

use App\Rules\Recaptcha;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Exceptions\Google2FAException;
use PragmaRX\Google2FA\Google2FA;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'code' => ['nullable', 'string', 'size:6'],
            'g-recaptcha-response' => ['required', new Recaptcha],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // First verify credentials without 2FA
        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // After credentials are verified, check if 2FA is required
        $user = Auth::user();

        if ($user->google_2fa_enabled) {
            // If 2FA is enabled but no code provided, logout and throw an error
            if (! $this->input('code')) {
                Auth::logout();
                throw ValidationException::withMessages([
                    'code' => trans('auth.2fa_required'),
                ]);
            }

            // Verify 2FA code
            $valid = $this->verify2FACode($user, $this->input('code'));

            if (! $valid) {
                Auth::logout();
                RateLimiter::hit($this->throttleKey('2fa'));

                throw ValidationException::withMessages([
                    'code' => trans('auth.2fa_failed'),
                ]);
            }
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Verify the 2FA code for the user.
     */
    protected function verify2FACode($user, $code): bool
    {
        if (empty($code) || strlen($code) !== 6 || ! is_numeric($code)) {
            return false;
        }

        // Check if secret key exists and has proper length
        if (empty($user->google_2fa_secret)) {
            // Log this error as it indicates a problem with the user's 2FA setup
            Log::error('Invalid 2FA secret key for user', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return false;
        }

        try {
            $google2fa = new Google2FA;
            // Расшифровываем секретный ключ перед проверкой
            $secret = Crypt::decryptString($user->google_2fa_secret);

            return $google2fa->verifyKey(
                $secret,
                $code,
                // Allow 1 window of leeway (30 seconds window)
                1
            );
        } catch (Google2FAException $e) {
            // Log the exception
            Log::error('2FA verification error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        } catch (\Exception $e) {
            // Добавляем обработку ошибок расшифровки
            Log::error('Error decrypting 2FA secret', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(string $type = 'login'): string
    {
        if ($type === '2fa') {
            return Str::transliterate(Str::lower($this->input('email')).'|2fa|'.$this->ip());
        }

        return Str::transliterate(Str::lower($this->input('email')).'|'.$this->ip());
    }
}
