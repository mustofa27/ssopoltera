<?php

namespace App\Support;

use App\Models\Application;
use App\Models\SsoSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SingleLogoutService
{
    /**
     * Revoke all sessions for a user and notify connected applications.
     *
     * @return array<string, mixed>
     */
    public function execute(User $user, Request $request, string $method, ?int $sourceApplicationId = null): array
    {
        $sessions = SsoSession::query()
            ->where('user_id', $user->id)
            ->get(['id', 'application_id']);

        $applicationIds = $sessions
            ->pluck('application_id')
            ->filter()
            ->unique()
            ->values();

        $applications = Application::query()
            ->whereIn('id', $applicationIds)
            ->when($sourceApplicationId, fn ($query) => $query->where('id', '!=', $sourceApplicationId))
            ->whereNotNull('logout_uri')
            ->where('logout_uri', '!=', '')
            ->get();

        $notified = 0;
        $failed = 0;

        foreach ($applications as $application) {
            $payload = [
                'event' => 'sso.single_logout',
                'user_id' => $user->id,
                'user_email' => $user->email,
                'application_slug' => $application->slug,
                'occurred_at' => now()->toIso8601String(),
                'source' => config('app.url'),
                'method' => $method,
            ];

            $signature = hash_hmac('sha256', json_encode($payload), $application->client_secret);

            try {
                $response = Http::timeout(5)
                    ->acceptJson()
                    ->withHeaders([
                        'X-SSO-Event' => 'single_logout',
                        'X-SSO-Signature' => $signature,
                    ])
                    ->post($application->logout_uri, $payload);

                if ($response->successful()) {
                    $notified++;
                } else {
                    $failed++;

                    AuditLogger::log(
                        event: 'sso.single_logout',
                        action: 'notify_failed',
                        request: $request,
                        userId: $user->id,
                        targetType: Application::class,
                        targetId: $application->id,
                        targetLabel: $application->slug,
                        metadata: [
                            'status' => $response->status(),
                            'logout_uri' => $application->logout_uri,
                        ]
                    );
                }
            } catch (\Throwable $exception) {
                $failed++;

                AuditLogger::log(
                    event: 'sso.single_logout',
                    action: 'notify_failed',
                    request: $request,
                    userId: $user->id,
                    targetType: Application::class,
                    targetId: $application->id,
                    targetLabel: $application->slug,
                    metadata: [
                        'error' => $exception->getMessage(),
                        'logout_uri' => $application->logout_uri,
                    ]
                );
            }
        }

        $revokedCount = $sessions->count();
        SsoSession::query()->where('user_id', $user->id)->delete();

        AuditLogger::log(
            event: 'sso.single_logout',
            action: 'completed',
            request: $request,
            userId: $user->id,
            targetType: User::class,
            targetId: $user->id,
            targetLabel: $user->email,
            metadata: [
                'method' => $method,
                'revoked_sessions' => $revokedCount,
                'notified_applications' => $notified,
                'failed_notifications' => $failed,
            ]
        );

        return [
            'revoked_sessions' => $revokedCount,
            'notified_applications' => $notified,
            'failed_notifications' => $failed,
        ];
    }
}
