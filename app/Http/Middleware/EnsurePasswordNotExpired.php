<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordNotExpired
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $expirationDays = (int) config('security.password_policy.expiration_days', 90);

        if ($expirationDays <= 0) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user || empty($user->password)) {
            return $next($request);
        }

        $changedAt = $user->password_changed_at ?? $user->created_at;

        if ($changedAt && $changedAt->lt(now()->subDays($expirationDays))) {
            AuditLogger::log(
                event: 'security.password_policy',
                action: 'expired',
                request: $request,
                userId: $user->id,
                targetType: User::class,
                targetId: $user->id,
                targetLabel: $user->email,
                metadata: ['expiration_days' => $expirationDays]
            );

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Your password has expired. Please contact an administrator to reset your password.');
        }

        return $next($request);
    }
}
