<?php

namespace App\Http\Controllers\Test;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Services\Api\TokenService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
    // Modify ProfileController.php to add this method
    public function settings(Request $request)
    {
        $user = $request->user();

        // Mock user data
        $user = (object)[
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'company' => 'Example Corp',
            'position' => 'Senior Developer',
            'country' => 'United States',
            'city' => 'New York',
            'timezone' => 'America/New_York',
            'language' => 'en',
            'photo' => 'https://ui-avatars.com/api/?name=John+Doe&background=random',
            'notification_email' => true,
            'notification_sms' => false,
            'notification_telegram' => true,
            'scope_of_activity' => 'Arbitrage (solo)',
            'created_at' => now()->subDays(30),
            'last_login_at' => now()->subHours(2),
            'two_factor_enabled' => false,
            'pin_enabled' => true,
            'ip_restriction_enabled' => false,
            'personal_greeting' => 'Welcome back, John!',
            'avatar' => 'https://ui-avatars.com/api/?name=John+Doe&background=random',
        ];

        // Get user scopes of activity (for dropdown)
        $scopes = [
            'Arbitrage (solo)',
            'Arbitrage (team)',
            'Affiliate marketing',
            'Media buying',
            'SEO',
            'Content marketing',
            'Other'
        ];
        $user = $request->user();

        // Get user's tokens
        $tokens = app(TokenService::class)->getUserTokens($user);

        return view('profile.settings', [
            'user' => $user,
            'scopes' => $scopes,
            'tokens' => $tokens,
            'api_token' => session('api_token'),
        ]);
    }

    // Add new method for updating user profile settings
    public function updateSettings(Request $request)
    {
        $request->validate([
            'login' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'telegram' => 'nullable|string|max:255',
            'scope' => 'required|string|max:255',
            'photo' => 'nullable|image|max:200|dimensions:max_width=600,max_height=600',
        ]);

        $user = $request->user();

        // Update user profile
        $user->login = $request->login;

        if ($user->email !== $request->email) {
            $user->email = $request->email;
            $user->email_verified_at = null;
        }

        $user->phone = $request->phone;
        $user->telegram = $request->telegram;
        $user->scope = $request->scope;

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $filename = Str::uuid() . '.' . $photo->getClientOriginalExtension();
            $photo->storeAs('public/avatars', $filename);

            // Remove old photo if exists
            if ($user->photo) {
                Storage::delete('public/avatars/' . $user->photo);
            }

            $user->photo = $filename;
        }

        $user->save();

        return redirect()->route('profile.settings')->with('status', 'profile-updated');
    }

    // Add to ProfileController.php
    public function changePassword()
    {
        return view('profile.change-password');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'confirmation_method' => ['required', 'string', 'in:authenticator,sms'],
            'code' => ['required_if:confirmation_method,authenticator', 'string', 'nullable'],
        ]);

        $user = $request->user();

        // In a real application, you would validate the 2FA code here

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('profile.settings')->with('status', 'password-updated');
    }

    // Add method to handle notification settings update
    public function updateNotifications(Request $request): RedirectResponse
    {
        $request->validate([
            'notifications' => ['nullable', 'array'],
            'notifications.*' => ['string', 'in:system,bonus'], // Validate allowed values
        ]);

        $user = $request->user();

        $user->update([
            'notification_settings' => $request->input('notifications', []), // Save the array, default to empty
        ]);

        return redirect()->route('profile.settings', '#notifications')->with('status', 'notifications-updated');
    }
}
