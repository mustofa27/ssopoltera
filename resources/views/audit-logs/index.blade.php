@extends('layouts.app')

@section('content')
    <div class="card mb-16">
        <div class="row-between">
            <h2 class="heading-reset">Audit Logs</h2>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('audit-logs.index') }}" class="mb-16 form-inline">
            <input class="input" type="text" name="q" value="{{ $search }}" placeholder="Search event, user, target, IP">

            <select class="input" name="event">
                <option value="">All Events</option>
                @foreach($events as $eventName)
                    <option value="{{ $eventName }}" {{ $event === $eventName ? 'selected' : '' }}>{{ $eventName }}</option>
                @endforeach
            </select>

            <select class="input" name="action">
                <option value="">All Actions</option>
                @foreach($actions as $actionName)
                    <option value="{{ $actionName }}" {{ $action === $actionName ? 'selected' : '' }}>{{ $actionName }}</option>
                @endforeach
            </select>

            <select class="input" name="target_type">
                <option value="">All Targets</option>
                @foreach($targetTypes as $type)
                    <option value="{{ $type }}" {{ $targetType === $type ? 'selected' : '' }}>{{ class_basename($type) }}</option>
                @endforeach
            </select>

            <button class="btn btn-secondary" type="submit">Filter</button>
            @if($search !== '' || $event !== '' || $action !== '' || $targetType !== '')
                <a class="btn btn-secondary" href="{{ route('audit-logs.index') }}">Reset</a>
            @endif
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Event</th>
                        <th>Action</th>
                        <th>Target</th>
                        <th>IP</th>
                        <th>Metadata</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                            <td>
                                @if($log->user)
                                    <div class="text-strong">{{ $log->user->name }}</div>
                                    <div class="muted text-xs">{{ $log->user->email }}</div>
                                @else
                                    <span class="muted">System / Guest</span>
                                @endif
                            </td>
                            <td>{{ $log->event }}</td>
                            <td><span class="badge badge-blue">{{ $log->action }}</span></td>
                            <td>
                                <div>{{ $log->target_label ?: '—' }}</div>
                                <div class="muted text-xs">
                                    {{ $log->target_type ? class_basename($log->target_type) : '—' }}
                                    {{ $log->target_id ? '#' . $log->target_id : '' }}
                                </div>
                            </td>
                            <td>{{ $log->ip_address ?: '—' }}</td>
                            <td>
                                @if(! empty($log->metadata))
                                    <details>
                                        <summary class="muted">View</summary>
                                        <pre class="text-xs">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="muted">No audit logs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-14">{{ $logs->links() }}</div>
    </div>
@endsection
