<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\RegisteredUserRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
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
    public function store(RegisteredUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'login' => $data['login'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'messenger_type' => $data['messenger_type'],
            'messenger_contact' => $data['messenger_contact'],
            'experience' => $data['experience'],
            'scope_of_activity' => $data['scope_of_activity'],
        ]);

        event(new Registered($user));

        Auth::login($user);

        // Create a basic API token for the user with minimal abilities
        $token = $user->createToken('basic-access', ['read:profile', 'read:public', 'read:base-token'])->plainTextToken;
        // Optionally store this token for the user's reference
        session()->flash('api_token', $token);

        return redirect(route('profile.settings', absolute: false));
    }
}
