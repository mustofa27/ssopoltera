@extends('layouts.app')

@section('content')
    <div class="card mb-16">
        <div class="row-between">
            <h2 class="heading-reset">Token Management</h2>
            <form method="GET" action="{{ route('tokens.index') }}" class="form-inline">
                <input class="input" type="text" name="q" value="{{ $search }}" placeholder="Search user, app, token...">
                <select class="input" name="status">
                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="expired" {{ $status === 'expired' ? 'selected' : '' }}>Expired</option>
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All</option>
                </select>
                <button class="btn btn-secondary" type="submit">Filter</button>
                @if($search !== '' || $status !== 'active')
                    <a class="btn btn-secondary" href="{{ route('tokens.index') }}">Reset</a>
                @endif
            </form>
        </div>
    </div>

    <div class="grid grid-3 mb-16">
        <div class="card text-center p-14">
            <div class="text-xl">{{ $stats['total'] }}</div>
            <div class="muted text-sm">Total Tokens</div>
        </div>
        <div class="card text-center p-14">
            <div class="text-xl text-green">{{ $stats['active'] }}</div>
            <div class="muted text-sm">Active Tokens</div>
        </div>
        <div class="card text-center p-14">
            <div class="text-xl">{{ $stats['recent_activity'] }}</div>
            <div class="muted text-sm">Used in Last 15m</div>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Application</th>
                        <th>Token</th>
                        <th>Last Activity</th>
                        <th>Expires</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tokens as $token)
                        @php
                            $rawToken = $token->access_token ?: $token->session_token;
                            $tokenPreview = substr($rawToken, 0, 8) . '...' . substr($rawToken, -4);
                        @endphp
                        <tr>
                            <td>
                                <div class="text-strong">{{ optional($token->user)->name ?? '—' }}</div>
                                <div class="muted text-xs">{{ optional($token->user)->email }}</div>
                            </td>
                            <td>{{ optional($token->application)->name ?? 'Portal Session' }}</td>
                            <td>
                                <div class="text-sm">{{ $tokenPreview }}</div>
                                <div class="muted text-xs">{{ $token->access_token ? 'Access Token' : 'Session Token' }}</div>
                            </td>
                            <td>
                                <span class="text-sm">{{ optional($token->last_activity)->format('Y-m-d H:i') ?? '—' }}</span>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('tokens.update-expiry', $token) }}" class="form-inline">
                                    @csrf
                                    @method('PATCH')
                                    <input class="input" type="datetime-local" name="expires_at"
                                           value="{{ optional($token->expires_at)->format('Y-m-d\\TH:i') }}" required>
                                    <button class="btn btn-secondary btn-sm" type="submit">Update</button>
                                </form>
                            </td>
                            <td>
                                @if($token->isActive())
                                    <span class="badge badge-green">Active</span>
                                @else
                                    <span class="badge badge-red">Expired</span>
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="{{ route('tokens.destroy', $token) }}" onsubmit="return confirm('Revoke this token?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-danger btn-sm" type="submit">Revoke</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="muted text-center p-20">No tokens found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-14">{{ $tokens->links() }}</div>
    </div>
@endsection
