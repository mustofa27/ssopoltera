<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\OAuthAuthorizationCode;
use App\Models\SsoSession;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OAuthController extends Controller
{
    public function authorize(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'response_type' => ['required', 'in:code'],
            'client_id' => ['required', 'string', 'max:255'],
            'redirect_uri' => ['required', 'url', 'max:255'],
            'scope' => ['nullable', 'string'],
            'state' => ['nullable', 'string', 'max:255'],
        ]);

        $application = Application::query()
            ->where('client_id', $validated['client_id'])
            ->where('is_active', true)
            ->first();

        if (! $application) {
            return $this->authorizationError($validated['redirect_uri'], 'invalid_client', 'Client application not found.');
        }

        if ($application->redirect_uri !== $validated['redirect_uri']) {
            return $this->authorizationError($validated['redirect_uri'], 'invalid_request', 'Redirect URI mismatch.');
        }

        $user = $request->user();

        if (! $user instanceof User) {
            return $this->authorizationError($validated['redirect_uri'], 'access_denied', 'Authentication required.');
        }

        if (! $user->hasAccessToApplication($application->id)) {
            AuditLogger::log(
                event: 'oauth.authorization',
                action: 'denied',
                request: $request,
                userId: $user->id,
                targetType: Application::class,
                targetId: $application->id,
                targetLabel: $application->slug,
                metadata: ['reason' => 'application_access_denied']
            );

            return $this->authorizationError($validated['redirect_uri'], 'access_denied', 'User has no access to this application for the current role or user type.', $validated['state'] ?? null);
        }

        if (! $application->allowsUserType($user->user_type)) {
            AuditLogger::log(
                event: 'oauth.authorization',
                action: 'denied',
                request: $request,
                userId: $user->id,
                targetType: Application::class,
                targetId: $application->id,
                targetLabel: $application->slug,
                metadata: [
                    'reason' => 'user_type_not_allowed',
                    'user_type' => $user->user_type,
                    'allowed_user_types' => $application->allowed_user_types,
                ]
            );

            return $this->authorizationError($validated['redirect_uri'], 'access_denied', 'User type is not allowed for this application.', $validated['state'] ?? null);
        }

        $requestedScopes = collect(explode(' ', trim((string) ($validated['scope'] ?? ''))))
            ->map(fn (string $scope) => trim($scope))
            ->filter()
            ->unique()
            ->values();

        $allowedScopes = collect($application->allowed_scopes ?? []);

        if ($requestedScopes->isNotEmpty() && $allowedScopes->isNotEmpty()) {
            $invalidScopes = $requestedScopes->diff($allowedScopes);

            if ($invalidScopes->isNotEmpty()) {
                return $this->authorizationError($validated['redirect_uri'], 'invalid_scope', 'One or more scopes are not allowed.', $validated['state'] ?? null);
            }
        }

        $authorizationCode = OAuthAuthorizationCode::create([
            'user_id' => $user->id,
            'application_id' => $application->id,
            'code' => Str::random(96),
            'scopes' => $requestedScopes->all(),
            'expires_at' => now()->addMinutes((int) config('security.sso.authorization_code_ttl_minutes', 5)),
        ]);

        AuditLogger::log(
            event: 'oauth.authorization',
            action: 'issued',
            request: $request,
            userId: $user->id,
            targetType: Application::class,
            targetId: $application->id,
            targetLabel: $application->slug,
            metadata: ['scopes' => $authorizationCode->scopes]
        );

        $redirectParams = [
            'code' => $authorizationCode->code,
        ];

        if (! empty($validated['state'])) {
            $redirectParams['state'] = $validated['state'];
        }

        return redirect()->away($validated['redirect_uri'] . '?' . http_build_query($redirectParams));
    }

    public function token(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'grant_type' => ['required', 'in:authorization_code,refresh_token'],
            'client_id' => ['required', 'string', 'max:255'],
            'client_secret' => ['required', 'string', 'max:255'],
        ]);

        if ($validated['grant_type'] === 'authorization_code') {
            $payload = $request->validate([
                'code' => ['required', 'string'],
                'redirect_uri' => ['required', 'url', 'max:255'],
            ]);
        } else {
            $payload = $request->validate([
                'refresh_token' => ['required', 'string'],
            ]);
        }

        $application = Application::query()
            ->where('client_id', $validated['client_id'])
            ->where('is_active', true)
            ->first();

        if (! $application || ! hash_equals($application->client_secret, $validated['client_secret'])) {
            return response()->json(['error' => 'invalid_client'], 401);
        }

        if ($validated['grant_type'] === 'authorization_code') {
            return $this->issueTokenFromAuthorizationCode($request, $application, $payload);
        }

        return $this->issueTokenFromRefreshToken($request, $application, $payload);
    }

    /**
     * @param  array{code:string,redirect_uri:string}  $payload
     */
    private function issueTokenFromAuthorizationCode(Request $request, Application $application, array $payload): JsonResponse
    {
        if ($application->redirect_uri !== $payload['redirect_uri']) {
            return response()->json(['error' => 'invalid_grant', 'error_description' => 'Redirect URI mismatch.'], 400);
        }

        $authorizationCode = OAuthAuthorizationCode::query()
            ->where('code', $payload['code'])
            ->where('application_id', $application->id)
            ->whereNull('used_at')
            ->first();

        if (! $authorizationCode || $authorizationCode->expires_at->isPast()) {
            return response()->json(['error' => 'invalid_grant'], 400);
        }

        $authorizedUser = User::query()->find($authorizationCode->user_id);

        if (! $authorizedUser || ! $application->allowsUserType($authorizedUser->user_type)) {
            AuditLogger::log(
                event: 'oauth.token',
                action: 'denied',
                request: $request,
                userId: $authorizationCode->user_id,
                targetType: Application::class,
                targetId: $application->id,
                targetLabel: $application->slug,
                metadata: [
                    'reason' => 'user_type_not_allowed',
                    'user_type' => $authorizedUser?->user_type,
                    'allowed_user_types' => $application->allowed_user_types,
                ]
            );

            return response()->json([
                'error' => 'access_denied',
                'error_description' => 'User type is not allowed for this application.',
            ], 403);
        }

        $accessToken = Str::random(80);
        $refreshToken = Str::random(80);
        $accessTokenTtlMinutes = (int) config('security.sso.access_token_ttl_minutes', 60);

        $session = SsoSession::create([
            'user_id' => $authorizationCode->user_id,
            'application_id' => $application->id,
            'session_token' => Str::random(64),
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_at' => now()->addMinutes($accessTokenTtlMinutes),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_activity' => now(),
        ]);

        $authorizationCode->update(['used_at' => now()]);

        AuditLogger::log(
            event: 'oauth.token',
            action: 'issued',
            request: $request,
            userId: $authorizationCode->user_id,
            targetType: Application::class,
            targetId: $application->id,
            targetLabel: $application->slug,
            metadata: ['session_id' => $session->id]
        );

        return response()->json([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => $accessTokenTtlMinutes * 60,
            'refresh_token' => $refreshToken,
            'scope' => implode(' ', $authorizationCode->scopes ?? []),
        ]);
    }

    /**
     * @param  array{refresh_token:string}  $payload
     */
    private function issueTokenFromRefreshToken(Request $request, Application $application, array $payload): JsonResponse
    {
        $session = SsoSession::query()
            ->where('application_id', $application->id)
            ->where('refresh_token', $payload['refresh_token'])
            ->first();

        if (! $session) {
            return response()->json(['error' => 'invalid_grant'], 400);
        }

        $refreshTokenTtlMinutes = (int) config('security.sso.refresh_token_ttl_minutes', 43200);

        if ($refreshTokenTtlMinutes > 0 && $session->created_at && $session->created_at->addMinutes($refreshTokenTtlMinutes)->isPast()) {
            return response()->json(['error' => 'invalid_grant', 'error_description' => 'Refresh token expired.'], 400);
        }

        $authorizedUser = User::query()->find($session->user_id);

        if (! $authorizedUser || ! $application->allowsUserType($authorizedUser->user_type)) {
            AuditLogger::log(
                event: 'oauth.token',
                action: 'denied',
                request: $request,
                userId: $session->user_id,
                targetType: Application::class,
                targetId: $application->id,
                targetLabel: $application->slug,
                metadata: [
                    'reason' => 'user_type_not_allowed',
                    'user_type' => $authorizedUser?->user_type,
                    'allowed_user_types' => $application->allowed_user_types,
                    'grant_type' => 'refresh_token',
                ]
            );

            return response()->json([
                'error' => 'access_denied',
                'error_description' => 'User type is not allowed for this application.',
            ], 403);
        }

        $accessTokenTtlMinutes = (int) config('security.sso.access_token_ttl_minutes', 60);
        $newAccessToken = Str::random(80);
        $newRefreshToken = Str::random(80);

        $session->update([
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'expires_at' => now()->addMinutes($accessTokenTtlMinutes),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_activity' => now(),
        ]);

        AuditLogger::log(
            event: 'oauth.token',
            action: 'refreshed',
            request: $request,
            userId: $session->user_id,
            targetType: Application::class,
            targetId: $application->id,
            targetLabel: $application->slug,
            metadata: ['session_id' => $session->id]
        );

        return response()->json([
            'access_token' => $newAccessToken,
            'token_type' => 'Bearer',
            'expires_in' => $accessTokenTtlMinutes * 60,
            'refresh_token' => $newRefreshToken,
            'scope' => '',
        ]);
    }

    public function userinfo(Request $request): JsonResponse
    {
        $bearerToken = $request->bearerToken();

        if (! $bearerToken) {
            return response()->json(['error' => 'invalid_token'], 401);
        }

        $session = SsoSession::query()
            ->with(['user.primaryAffiliation.department', 'user.primaryAffiliation.programStudy', 'user.primaryAffiliation.supportUnit'])
            ->where('access_token', $bearerToken)
            ->where('expires_at', '>', now())
            ->first();

        if (! $session) {
            return response()->json(['error' => 'invalid_token'], 401);
        }

        $user = $session->user;

        if (! $user) {
            return response()->json(['error' => 'invalid_token'], 401);
        }

        $session->update(['last_activity' => now()]);

        return response()->json([
            'sub' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'department' => $user->department,
            'job_title' => $user->job_title,
            'user_type' => $user->user_type,
            'employee_type' => $user->employee_type,
            'nip' => $user->nip,
            'nrp' => $user->nrp,
            'organization' => [
                'department' => optional(optional($user->primaryAffiliation)->department)->name,
                'program_study' => optional(optional($user->primaryAffiliation)->programStudy)->name,
                'support_unit' => optional(optional($user->primaryAffiliation)->supportUnit)->name,
            ],
        ]);
    }

    private function authorizationError(string $redirectUri, string $error, string $description, ?string $state = null): RedirectResponse
    {
        $params = [
            'error' => $error,
            'error_description' => $description,
        ];

        if ($state) {
            $params['state'] = $state;
        }

        return redirect()->away($redirectUri . '?' . http_build_query($params));
    }
}
