<?php

namespace App\Http\Controllers;

use App\Models\SsoSession;
use App\Models\User;
use App\Models\Application;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SsoSessionController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = $request->query('status', 'all'); // all | active | expired

        $query = SsoSession::query()
            ->with(['user', 'application'])
            ->when($search !== '', function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('application', function ($aq) use ($search) {
                    $aq->where('name', 'like', "%{$search}%");
                })->orWhere('ip_address', 'like', "%{$search}%");
            })
            ->when($status === 'active', fn ($q) => $q->where('expires_at', '>', now()))
            ->when($status === 'expired', fn ($q) => $q->where('expires_at', '<=', now()))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total'   => SsoSession::count(),
            'active'  => SsoSession::where('expires_at', '>', now())->count(),
            'expired' => SsoSession::where('expires_at', '<=', now())->count(),
        ];

        return view('sessions.index', compact('query', 'search', 'status', 'stats'));
    }

    public function destroy(SsoSession $session): RedirectResponse
    {
        $targetUser = $session->user;

        $session->delete();

        AuditLogger::log(
            event: 'session.management',
            action: 'revoked',
            request: request(),
            targetType: SsoSession::class,
            targetId: $session->id,
            targetLabel: $targetUser?->email,
            metadata: ['scope' => 'single']
        );

        return back()->with('success', 'Session revoked successfully.');
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        $scope = $request->input('scope', 'expired'); // expired | all | user

        if ($scope === 'expired') {
            $deleted = SsoSession::where('expires_at', '<=', now())->delete();

            AuditLogger::log(
                event: 'session.management',
                action: 'bulk_revoked',
                request: $request,
                targetType: SsoSession::class,
                targetId: null,
                targetLabel: null,
                metadata: ['scope' => 'expired', 'deleted' => $deleted]
            );

            return back()->with('success', "Cleared {$deleted} expired session(s).");
        }

        if ($scope === 'all') {
            $deleted = SsoSession::count();
            SsoSession::truncate();

            AuditLogger::log(
                event: 'session.management',
                action: 'bulk_revoked',
                request: $request,
                targetType: SsoSession::class,
                targetId: null,
                targetLabel: null,
                metadata: ['scope' => 'all', 'deleted' => $deleted]
            );

            return back()->with('success', "Cleared all {$deleted} session(s).");
        }

        $userId = $request->input('user_id');
        if ($userId) {
            $deleted = SsoSession::where('user_id', $userId)->delete();

            AuditLogger::log(
                event: 'session.management',
                action: 'bulk_revoked',
                request: $request,
                targetType: User::class,
                targetId: (int) $userId,
                targetLabel: null,
                metadata: ['scope' => 'user', 'deleted' => $deleted]
            );

            return back()->with('success', "Revoked {$deleted} session(s) for the user.");
        }

        return back()->with('error', 'Invalid scope specified.');
    }
}
