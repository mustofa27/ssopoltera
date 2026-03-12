<?php

namespace App\Http\Middleware;

use App\Support\AuditLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

class EnforceIpPolicy
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $policy = config('security.ip_filter', []);

        if (! ($policy['enabled'] ?? false)) {
            return $next($request);
        }

        $ipAddress = $request->ip();
        $allowlist = array_values(array_filter($policy['allowlist'] ?? []));
        $denylist = array_values(array_filter($policy['denylist'] ?? []));

        if ($this->matchesAny($ipAddress, $denylist)) {
            $this->logDeniedIp($request, 'denylist_match', $ipAddress);
            abort(403, 'Access denied for your IP address.');
        }

        if (($policy['enforce_allowlist'] ?? false) && count($allowlist) > 0 && ! $this->matchesAny($ipAddress, $allowlist)) {
            $this->logDeniedIp($request, 'allowlist_missing', $ipAddress);
            abort(403, 'Your IP address is not in the allowlist.');
        }

        return $next($request);
    }

    /**
     * @param  array<int, string>  $rules
     */
    private function matchesAny(string $ipAddress, array $rules): bool
    {
        foreach ($rules as $rule) {
            if (IpUtils::checkIp($ipAddress, trim($rule))) {
                return true;
            }
        }

        return false;
    }

    private function logDeniedIp(Request $request, string $reason, string $ipAddress): void
    {
        AuditLogger::log(
            event: 'security.ip_policy',
            action: 'blocked',
            request: $request,
            targetLabel: $ipAddress,
            metadata: ['reason' => $reason]
        );
    }
}
