<?php

namespace App\Http\Middleware;

use App\Models\Application;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApplicationKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $clientId = $request->header('X-Client-Id');
        $clientSecret = $request->header('X-Client-Secret');

        if (! $clientId || ! $clientSecret) {
            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'Missing application credentials.',
            ], 401);
        }

        $application = Application::query()
            ->where('client_id', $clientId)
            ->where('is_active', true)
            ->first();

        if (! $application || ! hash_equals($application->client_secret, $clientSecret)) {
            return response()->json([
                'error' => 'invalid_client',
                'error_description' => 'Invalid application credentials.',
            ], 401);
        }

        $request->attributes->set('application', $application);

        return $next($request);
    }
}
