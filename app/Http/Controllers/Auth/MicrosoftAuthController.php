<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SsoSession;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\SingleLogoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class MicrosoftAuthController extends Controller
{
    /**
     * Redirect to Microsoft authentication page.
     */
    public function redirect()
    {
        return Socialite::driver('microsoft')
            ->scopes(['openid', 'profile', 'email', 'User.Read'])
            ->redirect();
    }

    /**
     * Handle Microsoft callback.
     */
    public function callback()
    {
        try {
            $microsoftUser = Socialite::driver('microsoft')->user();

            $user = User::updateOrCreate(
                ['microsoft_id' => $microsoftUser->id],
                [
                    'name' => $microsoftUser->name,
                    'email' => $microsoftUser->email,
                    'avatar' => $microsoftUser->avatar ?? null,
                    'department' => $microsoftUser->user['department'] ?? null,
                    'job_title' => $microsoftUser->user['jobTitle'] ?? null,
                    'is_active' => true,
                    'last_login_at' => now(),
                    'email_verified_at' => now(),
                ]
            );

            if ($user->locked_until && $user->locked_until->isFuture()) {
                AuditLogger::log(
                    event: 'security.lockout',
                    action: 'blocked',
                    request: request(),
                    userId: $user->id,
                    targetType: User::class,
                    targetId: $user->id,
                    targetLabel: $user->email,
                    metadata: ['reason' => 'still_locked', 'method' => 'microsoft_oauth']
                );

                return redirect()->route('login')->with('error', 'Your account is temporarily locked. Please try again later.');
            }

            // Assign default role if user doesn't have any roles
            if ($user->roles()->count() === 0) {
                $defaultRole = \App\Models\Role::where('slug', 'user')->first();
                if ($defaultRole) {
                    $user->roles()->attach($defaultRole->id);
                }
            }

            Auth::login($user);

            $user->forceFill([
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ])->save();

            SsoSession::create([
                'user_id'        => $user->id,
                'application_id' => null,
                'session_token'  => Str::random(64),
                'expires_at'     => now()->addHours(8),
                'ip_address'     => request()->ip(),
                'user_agent'     => request()->userAgent(),
                'last_activity'  => now(),
            ]);

            AuditLogger::log(
                event: 'auth.login',
                action: 'success',
                request: request(),
                userId: $user->id,
                targetType: User::class,
                targetId: $user->id,
                targetLabel: $user->email,
                metadata: ['method' => 'microsoft_oauth']
            );

            return redirect()->intended(route('dashboard'));
        } catch (\Exception $e) {
            AuditLogger::log(
                event: 'auth.login',
                action: 'failed',
                request: request(),
                userId: null,
                targetType: User::class,
                targetId: null,
                targetLabel: null,
                metadata: [
                    'method' => 'microsoft_oauth',
                    'error' => $e->getMessage(),
                ]
            );

            return redirect()->route('login')->with('error', 'Failed to authenticate with Microsoft: ' . $e->getMessage());
        }
    }

    /**
     * Logout user.
     */
    public function logout(Request $request)
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
            metadata: ['method' => 'microsoft_oauth']
        );

        if ($user) {
            app(SingleLogoutService::class)->execute($user, $request, 'microsoft_oauth');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
