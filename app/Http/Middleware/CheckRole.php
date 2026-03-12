<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if (!$user->is_active) {
            auth()->logout();
            return redirect('/')->with('error', 'Your account has been deactivated.');
        }

        if (empty($roles)) {
            return $next($request);
        }

        if (!$user->hasAnyRole($roles)) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
