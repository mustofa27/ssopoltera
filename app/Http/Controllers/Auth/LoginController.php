<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SsoSession;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\SingleLogoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $attemptedUser = User::query()->where('email', $credentials['email'])->first();

        if ($attemptedUser && $attemptedUser->locked_until && $attemptedUser->locked_until->isFuture()) {
            AuditLogger::log(
                event: 'security.lockout',
                action: 'blocked',
                request: $request,
                userId: $attemptedUser->id,
                targetType: User::class,
                targetId: $attemptedUser->id,
                targetLabel: $attemptedUser->email,
                metadata: ['reason' => 'still_locked', 'locked_until' => $attemptedUser->locked_until->toIso8601String()]
            );

            throw ValidationException::withMessages([
                'email' => 'Your account is temporarily locked. Please try again later.',
            ]);
        }

        $microsoftOnlyRoles = config('security.two_step_policy.enforce_microsoft_login_for_roles', []);

        if (
            $attemptedUser
            && count($microsoftOnlyRoles) > 0
            && $attemptedUser->hasAnyRole($microsoftOnlyRoles)
            && ! empty($attemptedUser->microsoft_id)
        ) {
            AuditLogger::log(
                event: 'security.two_step_policy',
                action: 'blocked',
                request: $request,
                userId: $attemptedUser->id,
                targetType: User::class,
                targetId: $attemptedUser->id,
                targetLabel: $attemptedUser->email,
                metadata: [
                    'reason' => 'microsoft_login_required',
                    'roles' => $microsoftOnlyRoles,
                ]
            );

            throw ValidationException::withMessages([
                'email' => 'This account requires Microsoft Sign-In as part of security policy.',
            ]);
        }

        if (! Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ], (bool) ($credentials['remember'] ?? false))) {
            $maxAttempts = (int) config('security.account_lockout.max_attempts', 5);
            $lockoutMinutes = (int) config('security.account_lockout.lockout_minutes', 15);

            if ($attemptedUser) {
                $failedAttempts = ((int) $attemptedUser->failed_login_attempts) + 1;
                $isNowLocked = $failedAttempts >= $maxAttempts;

                $attemptedUser->forceFill([
                    'failed_login_attempts' => $isNowLocked ? 0 : $failedAttempts,
                    'locked_until' => $isNowLocked ? now()->addMinutes($lockoutMinutes) : null,
                ])->save();

                if ($isNowLocked) {
                    AuditLogger::log(
                        event: 'security.lockout',
                        action: 'triggered',
                        request: $request,
                        userId: $attemptedUser->id,
                        targetType: User::class,
                        targetId: $attemptedUser->id,
                        targetLabel: $attemptedUser->email,
                        metadata: ['failed_attempts' => $failedAttempts, 'lockout_minutes' => $lockoutMinutes]
                    );

                    throw ValidationException::withMessages([
                        'email' => 'Too many failed attempts. Your account has been temporarily locked.',
                    ]);
                }
            }

            AuditLogger::log(
                event: 'auth.login',
                action: 'failed',
                request: $request,
                userId: $attemptedUser?->id,
                targetType: User::class,
                targetId: $attemptedUser?->id,
                targetLabel: $credentials['email'],
                metadata: ['reason' => 'invalid_credentials']
            );

            throw ValidationException::withMessages([
                'email' => 'Invalid email or password.',
            ]);
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();

            AuditLogger::log(
                event: 'auth.login',
                action: 'failed',
                request: $request,
                userId: $user->id,
                targetType: User::class,
                targetId: $user->id,
                targetLabel: $user->email,
                metadata: ['reason' => 'account_deactivated']
            );

            throw ValidationException::withMessages([
                'email' => 'Your account is deactivated.',
            ]);
        }

        $request->session()->regenerate();

        $user->update([
            'last_login_at' => now(),
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);

        SsoSession::create([
            'user_id'        => $user->id,
            'application_id' => null,
            'session_token'  => Str::random(64),
            'expires_at'     => now()->addHours(8),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
            'last_activity'  => now(),
        ]);

        AuditLogger::log(
            event: 'auth.login',
            action: 'success',
            request: $request,
            userId: $user->id,
            targetType: User::class,
            targetId: $user->id,
            targetLabel: $user->email,
            metadata: ['method' => 'password']
        );

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();

        AuditLogger::log(
            event: 'auth.logout',
            action: 'success',
            request: $request,
            userId: $user?->id,
            targetType: User::class,
            targetId: $user?->id,
            targetLabel: $user?->email,
            metadata: ['method' => 'password']
        );

        if ($user) {
            app(SingleLogoutService::class)->execute($user, $request, 'password');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
