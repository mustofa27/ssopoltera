@extends('layouts.app')

@section('content')
    <div class="card mb-16">
        <div class="row-between">
            <div>
                <h2 class="heading-reset">Profile Synchronization</h2>
                <p class="muted mt-8">Synchronize user profile, department, and job title from Microsoft Graph.</p>
            </div>
            <form method="POST" action="{{ route('profile-sync.sync-all') }}" onsubmit="return confirm('Run synchronization for all Microsoft-linked users?')">
                @csrf
                <button class="btn" type="submit">Sync All Linked Users</button>
            </form>
        </div>
    </div>

    <div class="grid grid-3 mb-16">
        <div class="card text-center p-14">
            <div class="text-xl">{{ $stats['linked'] }}</div>
            <div class="muted text-sm">Microsoft Linked Users</div>
        </div>
        <div class="card text-center p-14">
            <div class="text-xl text-green">{{ $stats['synced_today'] }}</div>
            <div class="muted text-sm">Synced Today</div>
        </div>
        <div class="card text-center p-14">
            <div class="text-xl text-red">{{ $stats['sync_errors'] }}</div>
            <div class="muted text-sm">Users with Sync Error</div>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('profile-sync.index') }}" class="form-inline mb-16">
            <input class="input" type="text" name="q" value="{{ $search }}" placeholder="Search users...">
            <button class="btn btn-secondary" type="submit">Filter</button>
            @if($search !== '')
                <a class="btn btn-secondary" href="{{ route('profile-sync.index') }}">Reset</a>
            @endif
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Department / Job Title</th>
                        <th>Last Synced</th>
                        <th>Sync Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <div class="text-strong">{{ $user->name }}</div>
                                <div class="muted text-xs">{{ $user->email }}</div>
                            </td>
                            <td>
                                <div class="text-sm">{{ $user->department ?: '—' }}</div>
                                <div class="muted text-xs">{{ $user->job_title ?: '—' }}</div>
                            </td>
                            <td>
                                <span class="text-sm">{{ optional($user->microsoft_synced_at)->format('Y-m-d H:i') ?: 'Never' }}</span>
                            </td>
                            <td>
                                @if($user->microsoft_sync_error)
                                    <span class="badge badge-red">Failed</span>
                                    <div class="muted text-xs mt-4">{{ $user->microsoft_sync_error }}</div>
                                @elseif($user->microsoft_synced_at)
                                    <span class="badge badge-green">Synced</span>
                                @else
                                    <span class="badge badge-blue">Pending</span>
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="{{ route('profile-sync.sync-user', $user) }}">
                                    @csrf
                                    <button class="btn btn-secondary btn-sm" type="submit">Sync Now</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="muted text-center p-20">No Microsoft-linked users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-14">{{ $users->links() }}</div>
    </div>
@endsection
