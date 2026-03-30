<?php

namespace App\Support;

use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAffiliation;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class MicrosoftProfileSyncService
{
    /**
     * @return array{total:int, created:int, updated:int, skipped:int, failed:int, message:?string}
     */
    public function importAllMicrosoftUsers(): array
    {
        $summary = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'message' => null,
        ];

        if (! config('security.profile_sync.import_enabled', true)) {
            $summary['message'] = 'Microsoft tenant import is disabled by configuration.';

            return $summary;
        }

        $token = $this->getGraphAccessToken();

        if (! $token) {
            $summary['message'] = 'Unable to acquire Microsoft Graph access token.';

            return $summary;
        }

        $configuredImportLimit = (int) config('security.profile_sync.import_limit', 500);
        $remaining = $configuredImportLimit > 0 ? $configuredImportLimit : -1;
        $nextUrl = $this->buildMicrosoftUsersImportUrl();
        $defaultRole = Role::query()
            ->where('slug', (string) config('security.profile_sync.import_default_role_slug', 'user'))
            ->first();

        while ($nextUrl !== null && ($remaining > 0 || $remaining === -1)) {
            $response = $this->fetchMicrosoftUsersPage($token, $nextUrl);

            if (! $response->successful()) {
                $summary['message'] = 'Graph users request failed with status ' . $response->status() . '.';
                $summary['failed']++;

                return $summary;
            }

            $users = $response->json('value', []);

            if (! is_array($users) || $users === []) {
                $nextUrl = null;

                continue;
            }

            foreach ($users as $payload) {
                if ($remaining === 0) {
                    break;
                }

                $summary['total']++;
                $result = $this->upsertMicrosoftUser($payload, $defaultRole);
                $summary[$result]++;

                if ($remaining > 0) {
                    $remaining--;
                }
            }

            $nextUrl = $response->json('@odata.nextLink');
            $nextUrl = is_string($nextUrl) && $nextUrl !== '' ? $nextUrl : null;
        }

        if ($summary['message'] === null && $nextUrl !== null && $remaining === 0) {
            $summary['message'] = 'Import limit reached before processing the full Microsoft directory.';
        }

        return $summary;
    }

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

    private function buildMicrosoftUsersImportUrl(): string
    {
        $baseUrl = rtrim((string) config('security.profile_sync.graph_base_url', 'https://graph.microsoft.com/v1.0'), '/');
        $pageSize = min(999, max(1, (int) config('security.profile_sync.import_page_size', 100)));

        return $baseUrl . '/users?$select=id,displayName,mail,userPrincipalName,department,jobTitle,userType,accountEnabled&$top=' . $pageSize;
    }

    private function fetchMicrosoftUsersPage(string $accessToken, string $nextUrl): Response
    {
        return Http::withToken($accessToken)
            ->timeout((int) config('security.profile_sync.timeout_seconds', 10))
            ->acceptJson()
            ->get($nextUrl);
    }

    private function upsertMicrosoftUser(mixed $payload, ?Role $defaultRole): string
    {
        if (! is_array($payload)) {
            return 'skipped';
        }

        $microsoftId = trim((string) ($payload['id'] ?? ''));
        $userType = trim((string) ($payload['userType'] ?? ''));

        if ($microsoftId === '') {
            return 'skipped';
        }

        if ($userType === 'Guest' && ! config('security.profile_sync.import_include_guests', false)) {
            return 'skipped';
        }

        $incomingEmail = trim((string) ($payload['mail'] ?? $payload['userPrincipalName'] ?? ''));

        if ($incomingEmail === '') {
            return 'skipped';
        }

        $user = User::query()
            ->where('microsoft_id', $microsoftId)
            ->first();

        if (! $user) {
            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [strtolower($incomingEmail)])
                ->first();
        }

        $wasRecentlyCreated = false;

        if (! $user) {
            $user = new User();
            $wasRecentlyCreated = true;
        }

        $emailToPersist = $incomingEmail;

        if ($user->exists && strcasecmp((string) $user->email, $incomingEmail) !== 0) {
            $duplicate = User::query()
                ->whereRaw('LOWER(email) = ?', [strtolower($incomingEmail)])
                ->where('id', '!=', $user->id)
                ->exists();

            if ($duplicate) {
                $emailToPersist = (string) $user->email;
            }
        }

        $user->forceFill([
            'name' => trim((string) ($payload['displayName'] ?? '')) ?: ($user->name ?: $incomingEmail),
            'email' => $emailToPersist,
            'microsoft_id' => $microsoftId,
            'department' => trim((string) ($payload['department'] ?? '')) ?: $user->department,
            'job_title' => trim((string) ($payload['jobTitle'] ?? '')) ?: $user->job_title,
            'is_active' => array_key_exists('accountEnabled', $payload) ? (bool) $payload['accountEnabled'] : ($user->is_active ?? true),
            'email_verified_at' => $user->email_verified_at ?? now(),
            'microsoft_synced_at' => now(),
            'microsoft_sync_error' => null,
        ])->save();

        $this->syncDepartmentAffiliation($user, trim((string) ($payload['department'] ?? '')));

        if ($wasRecentlyCreated && $defaultRole && $user->roles()->count() === 0) {
            $user->roles()->attach($defaultRole->id);
        }

        return $wasRecentlyCreated ? 'created' : 'updated';
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
