<?php

namespace App\Support;

use App\Models\Department;
use App\Models\User;
use App\Models\UserAffiliation;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class MicrosoftProfileSyncService
{
    /**
     * @return array{success: bool, message: string}
     */
    public function syncUser(User $user): array
    {
        if (! config('security.profile_sync.enabled', true)) {
            return ['success' => false, 'message' => 'Profile sync is disabled by configuration.'];
        }

        if (empty($user->microsoft_id)) {
            return ['success' => false, 'message' => 'User is not linked to Microsoft account.'];
        }

        $token = $this->getGraphAccessToken();

        if (! $token) {
            $user->forceFill([
                'microsoft_sync_error' => 'Unable to acquire Microsoft Graph access token.',
            ])->save();

            return ['success' => false, 'message' => 'Unable to acquire Microsoft Graph access token.'];
        }

        $response = $this->fetchMicrosoftUser($token, $user->microsoft_id);

        if (! $response->successful()) {
            $message = 'Graph user request failed with status ' . $response->status();

            $user->forceFill([
                'microsoft_sync_error' => $message,
            ])->save();

            return ['success' => false, 'message' => $message];
        }

        $payload = $response->json();

        $incomingName = (string) ($payload['displayName'] ?? $user->name);
        $incomingEmail = (string) ($payload['mail'] ?? $payload['userPrincipalName'] ?? $user->email);
        $incomingDepartment = (string) ($payload['department'] ?? '');
        $incomingJobTitle = (string) ($payload['jobTitle'] ?? '');

        $emailToPersist = $user->email;
        if ($incomingEmail !== '' && $incomingEmail !== $user->email) {
            $duplicate = User::query()
                ->where('email', $incomingEmail)
                ->where('id', '!=', $user->id)
                ->exists();

            if (! $duplicate) {
                $emailToPersist = $incomingEmail;
            }
        }

        $user->forceFill([
            'name' => $incomingName,
            'email' => $emailToPersist,
            'department' => $incomingDepartment !== '' ? $incomingDepartment : $user->department,
            'job_title' => $incomingJobTitle !== '' ? $incomingJobTitle : $user->job_title,
            'microsoft_synced_at' => now(),
            'microsoft_sync_error' => null,
        ])->save();

        $this->syncDepartmentAffiliation($user, $incomingDepartment);

        return ['success' => true, 'message' => 'Profile synchronized successfully.'];
    }

    /**
     * @return array{total:int, success:int, failed:int}
     */
    public function syncAllMicrosoftUsers(): array
    {
        $batchLimit = (int) config('security.profile_sync.batch_limit', 200);
        $users = User::query()
            ->whereNotNull('microsoft_id')
            ->limit($batchLimit)
            ->get();

        $summary = [
            'total' => $users->count(),
            'success' => 0,
            'failed' => 0,
        ];

        foreach ($users as $user) {
            $result = $this->syncUser($user);

            if ($result['success']) {
                $summary['success']++;
            } else {
                $summary['failed']++;
            }
        }

        return $summary;
    }

    private function getGraphAccessToken(): ?string
    {
        $tenantId = (string) config('services.microsoft.tenant');
        $clientId = (string) config('services.microsoft.client_id');
        $clientSecret = (string) config('services.microsoft.client_secret');

        if ($tenantId === '' || $clientId === '' || $clientSecret === '') {
            return null;
        }

        $response = Http::asForm()
            ->timeout((int) config('security.profile_sync.timeout_seconds', 10))
            ->post("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token", [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ]);

        if (! $response->successful()) {
            return null;
        }

        return (string) $response->json('access_token');
    }

    private function fetchMicrosoftUser(string $accessToken, string $microsoftId): Response
    {
        $baseUrl = rtrim((string) config('security.profile_sync.graph_base_url', 'https://graph.microsoft.com/v1.0'), '/');

        return Http::withToken($accessToken)
            ->timeout((int) config('security.profile_sync.timeout_seconds', 10))
            ->acceptJson()
            ->get("{$baseUrl}/users/{$microsoftId}", [
                '$select' => 'id,displayName,mail,userPrincipalName,department,jobTitle',
            ]);
    }

    private function syncDepartmentAffiliation(User $user, string $departmentName): void
    {
        $normalizedDepartment = trim($departmentName);

        if ($normalizedDepartment === '') {
            return;
        }

        $department = Department::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($normalizedDepartment)])
            ->first();

        if (! $department) {
            return;
        }

        $primaryAffiliation = $user->primaryAffiliation()->first();
        $affiliationType = $user->user_type === 'student'
            ? 'student'
            : ($user->employee_type ?? 'employee');

        if ($primaryAffiliation) {
            $primaryAffiliation->update([
                'department_id' => $department->id,
                'affiliation_type' => $affiliationType,
            ]);

            return;
        }

        UserAffiliation::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'affiliation_type' => $affiliationType,
            'is_primary' => true,
        ]);
    }
}
