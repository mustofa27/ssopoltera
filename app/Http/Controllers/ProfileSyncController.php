<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AuditLogger;
use App\Support\MicrosoftProfileSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileSyncController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->whereNotNull('microsoft_id')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%")
                        ->orWhere('job_title', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'linked' => User::whereNotNull('microsoft_id')->count(),
            'synced_today' => User::whereNotNull('microsoft_synced_at')->where('microsoft_synced_at', '>=', now()->startOfDay())->count(),
            'sync_errors' => User::whereNotNull('microsoft_sync_error')->count(),
        ];

        return view('profile-sync.index', compact('users', 'stats', 'search'));
    }

    public function importAll(Request $request, MicrosoftProfileSyncService $service): RedirectResponse
    {
        $summary = $service->importAllMicrosoftUsers();

        AuditLogger::log(
            event: 'profile.sync',
            action: 'import_all',
            request: $request,
            targetType: User::class,
            metadata: $summary
        );

        $flashType = $summary['failed'] > 0 && $summary['created'] === 0 && $summary['updated'] === 0
            ? 'error'
            : 'success';

        $message = "Microsoft import complete. Total: {$summary['total']}, Created: {$summary['created']}, Updated: {$summary['updated']}, Skipped: {$summary['skipped']}, Failed: {$summary['failed']}.";
        $message .= " (Pages: {$summary['pages_processed']}, Page size: {$summary['config_page_size']}, Limit: {$summary['config_limit']})";

        if (! empty($summary['message'])) {
            $message .= ' ' . $summary['message'];
        }

        return back()->with($flashType, $message);
    }

    public function syncAll(Request $request, MicrosoftProfileSyncService $service): RedirectResponse
    {
        $summary = $service->syncAllMicrosoftUsers();

        AuditLogger::log(
            event: 'profile.sync',
            action: 'sync_all',
            request: $request,
            targetType: User::class,
            metadata: $summary
        );

        return back()->with('success', "Profile sync complete. Total: {$summary['total']}, Success: {$summary['success']}, Failed: {$summary['failed']}.");
    }

    public function syncUser(Request $request, User $user, MicrosoftProfileSyncService $service): RedirectResponse
    {
        $result = $service->syncUser($user);

        AuditLogger::log(
            event: 'profile.sync',
            action: $result['success'] ? 'sync_user_success' : 'sync_user_failed',
            request: $request,
            targetType: User::class,
            targetId: $user->id,
            targetLabel: $user->email,
            metadata: ['message' => $result['message']]
        );

        if ($result['success']) {
            return back()->with('success', "{$user->email}: {$result['message']}");
        }

        return back()->with('error', "{$user->email}: {$result['message']}");
    }
}
