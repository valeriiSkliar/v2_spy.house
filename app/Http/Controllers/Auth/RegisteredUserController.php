<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\RegisteredUserRequest;
use App\Models\User;
use App\Services\Api\TokenService;
use App\Services\User\UserRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    protected TokenService $tokenService;
    protected UserRegistrationService $registrationService;

    public function __construct(TokenService $tokenService, UserRegistrationService $registrationService)
    {
        $this->tokenService = $tokenService;
        $this->registrationService = $registrationService;
    }

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

        // Обрабатываем регистрацию через сервис
        $this->registrationService->processRegistration($user, [
            'registration_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'source' => $request->input('source', 'web'),
        ]);

        Auth::login($user);

        // Создание API токена через сервис
        $tokenData = $this->tokenService->createBasicToken($user);
        session(['api_token' => $tokenData['access_token']]);
        session(['api_token_expires_at' => $tokenData['expires_at']]);

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
}
