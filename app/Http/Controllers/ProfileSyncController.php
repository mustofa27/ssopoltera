<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\AuditLogger;
use App\Support\MicrosoftProfileSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Throwable;

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

        $importStatus = Cache::get($this->importStatusCacheKey($request->user()?->id));

        return view('profile-sync.index', compact('users', 'stats', 'search', 'importStatus'));
    }

    public function importAll(Request $request, MicrosoftProfileSyncService $service): RedirectResponse
    {
        $userId = $request->user()?->id;
        $cacheKey = $this->importStatusCacheKey($userId);

        Cache::put($cacheKey, [
            'status' => 'running',
            'started_at' => now()->toDateTimeString(),
            'summary' => null,
            'error' => null,
        ], now()->addHours(2));

        dispatch(function () use ($cacheKey, $userId) {
            try {
                $summary = app(MicrosoftProfileSyncService::class)->importAllMicrosoftUsers();
                $existingStatus = Cache::get($cacheKey);
                $startedAt = is_array($existingStatus)
                    ? ($existingStatus['started_at'] ?? now()->toDateTimeString())
                    : now()->toDateTimeString();

                Cache::put($cacheKey, [
                    'status' => 'completed',
                    'started_at' => $startedAt,
                    'finished_at' => now()->toDateTimeString(),
                    'summary' => $summary,
                    'error' => null,
                ], now()->addHours(2));

                AuditLogger::log(
                    event: 'profile.sync',
                    action: 'import_all',
                    request: null,
                    userId: $userId,
                    targetType: User::class,
                    metadata: $summary
                );
            } catch (Throwable $e) {
                $existingStatus = Cache::get($cacheKey);
                $startedAt = is_array($existingStatus)
                    ? ($existingStatus['started_at'] ?? now()->toDateTimeString())
                    : now()->toDateTimeString();

                Cache::put($cacheKey, [
                    'status' => 'failed',
                    'started_at' => $startedAt,
                    'finished_at' => now()->toDateTimeString(),
                    'summary' => null,
                    'error' => $e->getMessage(),
                ], now()->addHours(2));

                AuditLogger::log(
                    event: 'profile.sync',
                    action: 'import_all_failed',
                    request: null,
                    userId: $userId,
                    targetType: User::class,
                    metadata: ['error' => $e->getMessage()]
                );
            }
        })->afterResponse();

        return back()->with('success', 'Microsoft import started in background. You can continue using this page and refresh to see final results.');
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

    private function importStatusCacheKey(?int $userId): string
    {
        return 'profile-sync:microsoft-import-status:' . ($userId ?? 'guest');
    }
}
