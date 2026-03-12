<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $event = trim((string) $request->query('event', ''));
        $action = trim((string) $request->query('action', ''));
        $targetType = trim((string) $request->query('target_type', ''));

        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner
                        ->where('event', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%")
                        ->orWhere('target_label', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($event !== '', fn ($query) => $query->where('event', $event))
            ->when($action !== '', fn ($query) => $query->where('action', $action))
            ->when($targetType !== '', fn ($query) => $query->where('target_type', $targetType))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $events = AuditLog::query()->select('event')->distinct()->orderBy('event')->pluck('event');
        $actions = AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action');
        $targetTypes = AuditLog::query()->select('target_type')->whereNotNull('target_type')->distinct()->orderBy('target_type')->pluck('target_type');

        return view('audit-logs.index', compact('logs', 'search', 'event', 'action', 'targetType', 'events', 'actions', 'targetTypes'));
    }
}
