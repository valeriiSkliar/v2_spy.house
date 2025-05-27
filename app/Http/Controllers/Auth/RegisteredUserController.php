<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\RegisteredUserRequest;
use App\Mail\VerificationAccountEmail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('pages.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(RegisteredUserRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'login' => $data['login'],
            'name' => $data['login'],  // Using login as name initially
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'messenger_type' => $data['messenger_type'],
            'messenger_contact' => $data['messenger_contact'],
            'experience' => $data['experience'],
            'scope_of_activity' => $data['scope_of_activity'],
        ]);

        // Событие регистрации для других слушателей (без автоматической отправки письма)
        event(new Registered($user));

        Auth::login($user);

        // Create a basic API token for the user with minimal abilities
        $token = $user->createToken('basic-access', ['read:profile', 'read:public', 'read:base-token'])->plainTextToken;
        // Optionally store this token for the user's reference
        session()->flash('api_token', $token);

        // Генерируем и отправляем код верификации email
        $this->sendVerificationCode($user);

        // Handle AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => __('Registration successful'),
                'redirect_url' => route('profile.settings', absolute: false)
            ]);
        }

        return redirect(route('profile.settings', absolute: false));
    }

    /**
     * Генерирует и отправляет код верификации email
     */
    private function sendVerificationCode(User $user): void
    {
        // Генерируем 6-значный код
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Сохраняем код в кэш на 15 минут
        Cache::put('email_verification_code:' . $user->id, $code, now()->addMinutes(15));

        // Отправляем email с кодом
        try {
            Mail::to($user->email)->send(new VerificationAccountEmail(
                $code,
                config('app.url') . '/login',
                config('app.telegram_url', 'https://t.me/spyhouse'),
                config('mail.support_email', 'support@spy.house'),
                config('app.url') . '/unsubscribe'
            ));
        } catch (\Exception $e) {
            // Логируем ошибку, но не прерываем процесс регистрации
            Log::error('Failed to send verification email during registration', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage()
            ]);
        }
    }
}
