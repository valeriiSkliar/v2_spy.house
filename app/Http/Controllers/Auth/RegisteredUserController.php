<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\RegisteredUserRequest;
use App\Models\User;
use App\Notifications\Auth\WelcomeNotification;
use App\Services\EmailService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
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

        // Событие регистрации для других слушателей (включая отправку письма)
        event(new Registered($user));

        // Отправляем приветственное письмо
        $this->emailService->sendNotification($user, new WelcomeNotification());

        Auth::login($user);

        // Create a basic API token for the user with minimal abilities
        $token = $user->createToken('basic-access', ['read:profile', 'read:public', 'read:base-token'])->plainTextToken;
        // Optionally store this token for the user's reference
        session()->flash('api_token', $token);

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
