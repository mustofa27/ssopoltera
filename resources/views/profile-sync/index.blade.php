@extends('layouts.app')

@section('content')
    <div class="card mb-16">
        <div class="row-between">
            <div>
                <h2 class="heading-reset">Profile Synchronization</h2>
                <p class="muted mt-8">Import tenant users and synchronize local Microsoft-linked profiles from Microsoft Graph.</p>
            </div>
            <div class="form-inline">
                <form id="import-all-form" method="POST" action="{{ \Illuminate\Support\Facades\Route::has('profile-sync.import-all') ? route('profile-sync.import-all') : url('/profile-sync/import-all') }}" onsubmit="return confirm('Import users from Microsoft 365 into the local directory? This will create or update local accounts.')">
                    @csrf
                    <button id="import-all-button" class="btn" type="submit">Import All Tenant Users</button>
                </form>
                <form method="POST" action="{{ route('profile-sync.sync-all') }}" onsubmit="return confirm('Run synchronization for all Microsoft-linked users?')">
                    @csrf
                    <button class="btn btn-secondary" type="submit" data-profile-sync-top-action="1">Sync All Linked Users</button>
                </form>
            </div>
        </div>
    </div>

    <div id="import-loading-state" class="card mb-16" style="display:none; border-left:4px solid #2563eb;">
        <div class="row-between">
            <div>
                <div class="text-strong">Import in progress<span id="import-loading-dots">.</span></div>
                <div class="muted text-sm mt-8">Import started. You can keep using this page while the process runs in background.</div>
            </div>
            <div class="badge badge-blue">Running</div>
        </div>
    </div>

    @if(! empty($importStatus) && is_array($importStatus))
        <div class="card mb-16" style="border-left:4px solid {{ ($importStatus['status'] ?? '') === 'failed' ? '#dc2626' : (($importStatus['status'] ?? '') === 'completed' ? '#16a34a' : '#2563eb') }};">
            <div class="row-between">
                <div>
                    @if(($importStatus['status'] ?? '') === 'running')
                        <div class="text-strong">Last import status: Running</div>
                        <div class="muted text-sm mt-8">Started at {{ $importStatus['started_at'] ?? '-' }}. Refresh this page in a while to see the final summary.</div>
                    @elseif(($importStatus['status'] ?? '') === 'completed')
                        @php $s = $importStatus['summary'] ?? []; @endphp
                        <div class="text-strong">Last import status: Completed</div>
                        <div class="muted text-sm mt-8">
                            Total: {{ $s['total'] ?? 0 }}, Created: {{ $s['created'] ?? 0 }}, Updated: {{ $s['updated'] ?? 0 }}, Skipped: {{ $s['skipped'] ?? 0 }}, Failed: {{ $s['failed'] ?? 0 }}.
                            (Pages: {{ $s['pages_processed'] ?? 0 }}, Page size: {{ $s['config_page_size'] ?? 0 }}, Limit: {{ $s['config_limit'] ?? 0 }})
                        </div>
                    @else
                        <div class="text-strong">Last import status: Failed</div>
                        <div class="muted text-sm mt-8">{{ $importStatus['error'] ?? 'Unexpected import error.' }}</div>
                    @endif
                </div>
                <div class="badge {{ ($importStatus['status'] ?? '') === 'failed' ? 'badge-red' : (($importStatus['status'] ?? '') === 'completed' ? 'badge-green' : 'badge-blue') }}">
                    {{ ucfirst((string) ($importStatus['status'] ?? 'unknown')) }}
                </div>
            </div>
        </div>
    @endif

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

    <script>
        (function () {
            const importForm = document.getElementById('import-all-form');
            const importButton = document.getElementById('import-all-button');
            const loadingCard = document.getElementById('import-loading-state');
            const loadingDots = document.getElementById('import-loading-dots');

            if (!importForm || !importButton || !loadingCard || !loadingDots) {
                return;
            }

            let dots = 1;
            let dotTimer = null;

            importForm.addEventListener('submit', function () {
                importButton.disabled = true;
                importButton.textContent = 'Importing...';

                document.querySelectorAll('[data-profile-sync-top-action="1"]').forEach(function (button) {
                    button.disabled = true;
                });

                loadingCard.style.display = 'block';

                dotTimer = window.setInterval(function () {
                    dots = dots >= 3 ? 1 : dots + 1;
                    loadingDots.textContent = '.'.repeat(dots);
                }, 500);

                window.setTimeout(function () {
                    if (dotTimer) {
                        window.clearInterval(dotTimer);
                    }
                }, 180000);
            });
        })();
    </script>
@endsection
