@extends('layouts.app')

@section('content')
    {{-- Header --}}
    <div class="card mb-16">
        <div class="row-between">
            <h2 class="heading-reset">Session Management</h2>
            <div class="form-inline">
                <form method="POST" action="{{ route('sessions.clear') }}" class="inline-form"
                      onsubmit="return confirm('Clear all expired sessions?')">
                    @csrf
                    <input type="hidden" name="scope" value="expired">
                    <button class="btn btn-secondary" type="submit">Clear Expired</button>
                </form>
                <form method="POST" action="{{ route('sessions.clear') }}" class="inline-form"
                      onsubmit="return confirm('This will revoke ALL sessions including active ones. Continue?')">
                    @csrf
                    <input type="hidden" name="scope" value="all">
                    <button class="btn btn-danger" type="submit">Revoke All</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-3 mb-16">
        <div class="card text-center p-14">
            <div class="text-xl">{{ $stats['total'] }}</div>
            <div class="muted text-sm">Total Sessions</div>
        </div>
        <div class="card text-center p-14">
            <div class="text-xl text-green">{{ $stats['active'] }}</div>
            <div class="muted text-sm">Active</div>
        </div>
        <div class="card text-center p-14">
            <div class="text-xl text-red">{{ $stats['expired'] }}</div>
            <div class="muted text-sm">Expired</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card">
        <form method="GET" action="{{ route('sessions.index') }}" class="mb-16 form-inline">
            <input class="input" type="text" name="q"
                   value="{{ $search }}" placeholder="Search user, app, IP address…">
            <select class="input" name="status">
                <option value="all"     {{ $status === 'all'     ? 'selected' : '' }}>All Sessions</option>
                <option value="active"  {{ $status === 'active'  ? 'selected' : '' }}>Active Only</option>
                <option value="expired" {{ $status === 'expired' ? 'selected' : '' }}>Expired Only</option>
            </select>
            <button class="btn btn-secondary" type="submit">Filter</button>
            @if($search || $status !== 'all')
                <a class="btn btn-secondary" href="{{ route('sessions.index') }}">Reset</a>
            @endif
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Application</th>
                        <th>IP Address</th>
                        <th>Login / Last Activity</th>
                        <th>Expires</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($query as $session)
                        <tr>
                            <td>
                                <div class="text-strong">{{ optional($session->user)->name ?? '—' }}</div>
                                <div class="muted text-xs">{{ optional($session->user)->email }}</div>
                            </td>
                            <td>{{ optional($session->application)->name ?? '—' }}</td>
                            <td>{{ $session->ip_address ?? '—' }}</td>
                            <td>
                                <div class="text-sm">{{ optional($session->created_at)->format('Y-m-d H:i') }}</div>
                                <div class="muted text-xs">
                                    Last: {{ optional($session->last_activity)->format('Y-m-d H:i') ?? '—' }}
                                </div>
                            </td>
                            <td>
                                <span class="text-sm">{{ optional($session->expires_at)->format('Y-m-d H:i') }}</span>
                            </td>
                            <td>
                                @if($session->isActive())
                                    <span class="badge badge-green">Active</span>
                                @else
                                    <span class="badge badge-red">Expired</span>
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="{{ route('sessions.destroy', $session) }}"
                                      onsubmit="return confirm('Revoke this session?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm" type="submit">Revoke</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="muted text-center p-20">
                                No sessions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-14">{{ $query->links() }}</div>
    </div>
@endsection
