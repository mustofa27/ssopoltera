<?php

namespace App\Http\Controllers;

use App\Models\SsoSession;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TokenController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'active');

        $tokens = SsoSession::query()
            ->with(['user', 'application'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->whereHas('user', function ($userQuery) use ($search) {
                            $userQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('application', function ($appQuery) use ($search) {
                            $appQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('slug', 'like', "%{$search}%");
                        })
                        ->orWhere('session_token', 'like', "%{$search}%")
                        ->orWhere('access_token', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('expires_at', '>', now()))
            ->when($status === 'expired', fn ($query) => $query->where('expires_at', '<=', now()))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => SsoSession::count(),
            'active' => SsoSession::where('expires_at', '>', now())->count(),
            'expired' => SsoSession::where('expires_at', '<=', now())->count(),
            'recent_activity' => SsoSession::where('last_activity', '>=', now()->subMinutes(15))->count(),
        ];

        return view('tokens.index', compact('tokens', 'search', 'status', 'stats'));
    }

    public function updateExpiry(Request $request, SsoSession $session): RedirectResponse
    {
        $validated = $request->validate([
            'expires_at' => ['required', 'date', 'after:now'],
        ]);

        $session->update([
            'expires_at' => $validated['expires_at'],
        ]);

        AuditLogger::log(
            event: 'token.management',
            action: 'expiration_updated',
            request: $request,
            targetType: SsoSession::class,
            targetId: $session->id,
            targetLabel: optional($session->user)->email,
            metadata: ['expires_at' => $session->expires_at?->toIso8601String()]
        );

        return back()->with('success', 'Token expiration updated successfully.');
    }

    public function destroy(SsoSession $session): RedirectResponse
    {
        $targetUser = $session->user;
        $sessionId = $session->id;

        $session->delete();

        AuditLogger::log(
            event: 'token.management',
            action: 'revoked',
            request: request(),
            targetType: SsoSession::class,
            targetId: $sessionId,
            targetLabel: $targetUser?->email,
            metadata: ['scope' => 'single']
        );

        return back()->with('success', 'Token revoked successfully.');
    }
}
