<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSessionNotIdle
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

        $timeoutMinutes = (int) config('security.session_idle_timeout_minutes', (int) config('session.lifetime'));

        if ($timeoutMinutes <= 0) {
            return $next($request);
        }

        $lastActivityTimestamp = (int) $request->session()->get('last_activity_at', 0);
        $currentTimestamp = now()->timestamp;

        if ($lastActivityTimestamp > 0) {
            $idleSeconds = $currentTimestamp - $lastActivityTimestamp;

            if ($idleSeconds > ($timeoutMinutes * 60)) {
                $user = $request->user();

                AuditLogger::log(
                    event: 'security.session_policy',
                    action: 'expired',
                    request: $request,
                    userId: $user?->id,
                    targetType: User::class,
                    targetId: $user?->id,
                    targetLabel: $user?->email,
                    metadata: ['reason' => 'idle_timeout', 'idle_seconds' => $idleSeconds]
                );

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('error', 'Session expired due to inactivity. Please sign in again.');
            }
        }

        $request->session()->put('last_activity_at', $currentTimestamp);

        return $next($request);
    }
}
