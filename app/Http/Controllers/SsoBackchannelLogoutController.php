<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\SingleLogoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SsoBackchannelLogoutController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => ['required', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer'],
            'user_email' => ['nullable', 'email', 'max:255'],
            'timestamp' => ['required', 'integer'],
        ]);

        if (empty($validated['user_id']) && empty($validated['user_email'])) {
            return response()->json([
                'message' => 'Either user_id or user_email is required.',
            ], 422);
        }

        $application = Application::query()
            ->where('client_id', $validated['client_id'])
            ->first();

        if (! $application || ! $application->is_active) {
            return response()->json(['message' => 'Invalid client.'], 401);
        }

        $expectedSignature = hash_hmac('sha256', (string) $request->getContent(), $application->client_secret);
        $providedSignature = (string) $request->header('X-SSO-Signature', '');

        if ($providedSignature === '' || ! hash_equals($expectedSignature, $providedSignature)) {
            AuditLogger::log(
                event: 'sso.single_logout',
                action: 'backchannel_rejected',
                request: $request,
                targetType: Application::class,
                targetId: $application->id,
                targetLabel: $application->slug,
                metadata: ['reason' => 'invalid_signature']
            );

            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        $maxSkewSeconds = (int) config('security.sso.backchannel_max_skew_seconds', 300);
        $requestTime = (int) $validated['timestamp'];

        if (abs(now()->timestamp - $requestTime) > $maxSkewSeconds) {
            AuditLogger::log(
                event: 'sso.single_logout',
                action: 'backchannel_rejected',
                request: $request,
                targetType: Application::class,
                targetId: $application->id,
                targetLabel: $application->slug,
                metadata: ['reason' => 'timestamp_skew_exceeded']
            );

            return response()->json(['message' => 'Request timestamp is out of allowed window.'], 422);
        }

        $user = User::query()
            ->when(! empty($validated['user_id']), fn ($query) => $query->where('id', (int) $validated['user_id']))
            ->when(! empty($validated['user_email']), fn ($query) => $query->where('email', $validated['user_email']))
            ->first();

        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $result = app(SingleLogoutService::class)->execute(
            user: $user,
            request: $request,
            method: 'backchannel',
            sourceApplicationId: $application->id,
        );

        return response()->json([
            'message' => 'Single logout completed.',
            'data' => $result,
        ]);
    }
}
