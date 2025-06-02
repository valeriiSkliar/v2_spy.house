<?php

declare(strict_types=1);

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Profile\ChangePasswordApiRequest;
use App\Http\Requests\Profile\ProfileUpdateRequest;
use App\Http\Requests\Profile\UpdateEmailRequest;
use App\Http\Requests\Profile\UpdateIpRestrictionRequest;
use App\Http\Requests\Profile\UpdateNotificationSettingsRequest;
use App\Http\Requests\Profile\UpdatePersonalGreetingSettingsRequest;
use App\Services\ProfileService;
use App\Services\SecurityService;
use App\Traits\ProfileVerificationTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UnifiedProfileController extends Controller
{
    use ProfileVerificationTrait;

    public function __construct(
        private readonly ProfileService $profileService,
        private readonly SecurityService $securityService
    ) {}

    // ==================== MAIN VIEWS ====================

    /**
     * Display main profile settings page
     */
    public function index(Request $request): View
    {
        $activeTab = $request->query('tab', 'personal');

        return $this->renderProfileView('pages.profile.settings', [
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * Display password change page
     */
    public function changePasswordView(Request $request): View
    {
        $user = $request->user();

        return $this->renderProfileView('pages.profile.change-password', [
            'passwordUpdatePending' => $this->isOperationPending($user, 'password'),
            'confirmationMethod' => $this->getConfirmationMethod($user),
        ]);
    }

    /**
     * Display email change page
     */
    public function changeEmailView(Request $request): View
    {
        $user = $request->user();

        return $this->renderProfileView('pages.profile.change-email', [
            'emailUpdatePending' => $this->isOperationPending($user, 'email'),
            'confirmationMethod' => $this->getConfirmationMethod($user),
        ]);
    }

    /**
     * Display IP restriction page
     */
    public function ipRestrictionView(Request $request): View
    {
        return $this->renderProfileView('pages.profile.ip-restriction');
    }

    /**
     * Display 2FA setup page
     */
    public function connect2faView(Request $request): View
    {
        $user = $request->user();
        if ($user->google_2fa_enabled) {
            return redirect()->route('profile.settings', ['tab' => 'security']);
        }

        return $this->renderProfileView('pages.profile.connect-2fa');
    }

    // ==================== PERSONAL SETTINGS ====================

    /**
     * Update personal information (both web and API)
     */
    public function updatePersonalInfo(ProfileUpdateRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $result = $this->profileService->updatePersonalInfo($user, $request->validated());

        if (request()->expectsJson()) {
            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => __('profile.success.settings_update_success'),
                    'user' => $user->only([
                        'login', 'experience', 'scope_of_activity',
                        'messenger_type', 'messenger_contact',
                    ]),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => __('profile.errors.update_failed'),
            ], 422);
        }

        if ($result) {
            return redirect()->route('profile.index')->with('success', __('profile.success.settings_update_success'));
        }

        return redirect()->back()->withErrors(['error' => __('profile.errors.update_failed')]);
    }

    // ==================== PASSWORD MANAGEMENT ====================

    /**
     * Initiate password change process
     */
    public function initiatePasswordChange(ChangePasswordApiRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        return $this->handleSecureAction(
            $user,
            'password_change',
            fn ($user, $data) => $this->profileService->initiatePasswordChange($user, $data),
            $request->validated()
        );
    }

    /**
     * Confirm password change
     */
    public function confirmPasswordChange(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate(['verification_code' => 'required|string']);
        $user = $request->user();

        return $this->handleSecureConfirmation(
            $user,
            'password_change',
            $request->input('verification_code'),
            fn ($user, $code) => $this->profileService->confirmPasswordChange($user, $code)
        );
    }

    /**
     * Cancel password change process
     */
    public function cancelPasswordChange(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $result = $this->cancelOperation($user, 'password');

        if (request()->expectsJson()) {
            return response()->json(['success' => $result]);
        }

        return redirect()->route('profile.change-password')->with('info', __('profile.info.operation_cancelled'));
    }

    // ==================== EMAIL MANAGEMENT ====================

    /**
     * Initiate email change process
     */
    public function initiateEmailChange(UpdateEmailRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        return $this->handleSecureAction(
            $user,
            'email_change',
            fn ($user, $data) => $this->profileService->initiateEmailChange($user, $data['new_email']),
            $request->validated()
        );
    }

    /**
     * Confirm email change
     */
    public function confirmEmailChange(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate(['verification_code' => 'required|string']);
        $user = $request->user();

        return $this->handleSecureConfirmation(
            $user,
            'email_change',
            $request->input('verification_code'),
            fn ($user, $code) => $this->profileService->confirmEmailChange($user, $code)
        );
    }

    /**
     * Cancel email change process
     */
    public function cancelEmailChange(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $result = $this->cancelOperation($user, 'email');

        if (request()->expectsJson()) {
            return response()->json(['success' => $result]);
        }

        return redirect()->route('profile.change-email')->with('info', __('profile.info.operation_cancelled'));
    }

    // ==================== NOTIFICATION SETTINGS ====================

    /**
     * Update notification preferences
     */
    public function updateNotifications(UpdateNotificationSettingsRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $result = $this->profileService->updateNotificationSettings($user, $request->validated());

        if (request()->expectsJson()) {
            return response()->json([
                'success' => $result,
                'message' => $result ? __('profile.success.notifications_updated') : __('profile.errors.update_failed'),
            ]);
        }

        if ($result) {
            return redirect()->route('profile.index', ['tab' => 'notifications'])
                ->with('success', __('profile.success.notifications_updated'));
        }

        return redirect()->back()->withErrors(['error' => __('profile.errors.update_failed')]);
    }

    // ==================== SECURITY SETTINGS ====================

    /**
     * Update IP restrictions
     */
    public function updateIpRestriction(UpdateIpRestrictionRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $ips = array_filter(explode("\n", $request->input('allowed_ips', '')));
        $result = $this->securityService->updateIpRestrictions($user, $ips);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => $result,
                'message' => $result ? __('profile.success.ip_restrictions_updated') : __('profile.errors.update_failed'),
            ]);
        }

        if ($result) {
            return redirect()->route('profile.ip-restriction')
                ->with('success', __('profile.success.ip_restrictions_updated'));
        }

        return redirect()->back()->withErrors(['error' => __('profile.errors.update_failed')]);
    }

    // ==================== TWO-FACTOR AUTHENTICATION ====================

    /**
     * Enable 2FA
     */
    public function enable2FA(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if ($user->google_2fa_enabled) {
            return $this->formatErrorResponse(__('profile.errors.2fa_already_enabled'));
        }

        $result = $this->securityService->enable2FA($user);

        if (request()->expectsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return view('pages.profile.connect-2fa-step2', [
                'user' => $user,
                'qrCode' => $result['qr_code'],
                'secret' => $result['secret'],
            ]);
        }

        return redirect()->back()->withErrors(['error' => $result['error'] ?? __('profile.errors.2fa_setup_failed')]);
    }

    /**
     * Confirm 2FA setup
     */
    public function confirm2FA(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate(['verification_code' => 'required|string']);
        $user = $request->user();

        $result = $this->securityService->confirm2FASetup($user, $request->input('verification_code'));

        if (request()->expectsJson()) {
            return response()->json([
                'success' => $result,
                'message' => $result ? __('profile.success.2fa_enabled') : __('profile.errors.invalid_code'),
            ]);
        }

        if ($result) {
            return redirect()->route('profile.index', ['tab' => 'security'])
                ->with('success', __('profile.success.2fa_enabled'));
        }

        return redirect()->back()->withErrors(['verification_code' => __('profile.errors.invalid_code')]);
    }

    /**
     * Disable 2FA
     */
    public function disable2FA(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate(['verification_code' => 'required|string']);
        $user = $request->user();

        $result = $this->securityService->disable2FA($user, $request->input('verification_code'));

        if (request()->expectsJson()) {
            return response()->json([
                'success' => $result,
                'message' => $result ? __('profile.success.2fa_disabled') : __('profile.errors.invalid_code'),
            ]);
        }

        if ($result) {
            return redirect()->route('profile.index', ['tab' => 'security'])
                ->with('success', __('profile.success.2fa_disabled'));
        }

        return redirect()->back()->withErrors(['verification_code' => __('profile.errors.invalid_code')]);
    }

    // ==================== PERSONAL GREETING ====================

    /**
     * Display personal greeting page
     */
    public function personalGreetingView(Request $request): View
    {
        $user = $request->user();

        return $this->renderProfileView('pages.profile.personal-greeting', [
            'greetingUpdatePending' => $this->isOperationPending($user, 'personal_greeting'),
            'confirmationMethod' => $this->getConfirmationMethod($user),
        ]);
    }

    /**
     * Initiate personal greeting update
     */
    public function initiatePersonalGreetingUpdate(UpdatePersonalGreetingSettingsRequest $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        return $this->handleSecureAction(
            $user,
            'personal_greeting_update',
            fn ($user, $data) => $this->profileService->updatePersonalGreeting($user, $data['personal_greeting']),
            $request->validated()
        );
    }

    /**
     * Confirm personal greeting update
     */
    public function confirmPersonalGreetingUpdate(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate(['verification_code' => 'required|string']);
        $user = $request->user();

        return $this->handleSecureConfirmation(
            $user,
            'personal_greeting_update',
            $request->input('verification_code'),
            fn ($user, $code) => true // Implementation depends on your greeting update logic
        );
    }

    /**
     * Cancel personal greeting update
     */
    public function cancelPersonalGreetingUpdate(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $result = $this->cancelOperation($user, 'personal_greeting');

        if (request()->expectsJson()) {
            return response()->json(['success' => $result]);
        }

        return redirect()->route('profile.personal-greeting')->with('info', __('profile.info.operation_cancelled'));
    }

    // ==================== UTILITY METHODS ====================

    /**
     * Validate login uniqueness (AJAX)
     */
    public function validateLoginUnique(Request $request): JsonResponse
    {
        $request->validate(['login' => 'required|string']);

        $exists = \App\Models\User::where('login', $request->input('login'))
            ->where('id', '!=', $request->user()->id)
            ->exists();

        return response()->json(['available' => ! $exists]);
    }

    /**
     * Delete user account
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

        return redirect('/');
    }
}
