<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function log(
        string $event,
        string $action,
        ?Request $request = null,
        ?int $userId = null,
        ?string $targetType = null,
        mixed $targetId = null,
        ?string $targetLabel = null,
        array $metadata = []
    ): void {
        if ($request) {
            $userId ??= $request->user()?->id;
        }

        AuditLog::create([
            'user_id' => $userId,
            'event' => $event,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId ? (int) $targetId : null,
            'target_label' => $targetLabel,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'metadata' => empty($metadata) ? null : $metadata,
        ]);
    }
}
