@extends('layouts.app')

@section('content')
    <div class="card mb-16">
        <div class="row-between">
            <h2 class="heading-reset">Users</h2>
            <a class="btn" href="{{ route('users.create') }}">Create User</a>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('users.index') }}" class="mb-16 form-inline">
            <input class="input" type="text" name="q" value="{{ $search }}" placeholder="Search name, email, NIP, NRP, department">
            <button class="btn btn-secondary" type="submit">Search</button>
        </form>

        <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Identity</th>
                    <th>Roles</th>
                    <th>Affiliation</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    @php $primaryAffiliation = $user->primaryAffiliation; @endphp
                    <tr>
                        <td>
                            <div class="text-strong">{{ $user->name }}</div>
                            <div class="muted text-xs">{{ $user->email }}</div>
                        </td>
                        <td>
                            <div>
                                <span class="badge badge-blue">
                                    {{ ucfirst($user->user_type ?? 'unknown') }}
                                </span>
                                @if($user->employee_type)
                                    <span class="badge badge-violet">
                                        {{ str_replace('_', ' ', ucfirst($user->employee_type)) }}
                                    </span>
                                @endif
                            </div>
                            <div class="muted text-xs mt-4">
                                {{ $user->nip ? 'NIP: ' . $user->nip : ($user->nrp ? 'NRP: ' . $user->nrp : 'No identifier') }}
                            </div>
                        </td>
                        <td>
                            @foreach($user->roles as $role)
                                <span class="badge badge-indigo">{{ $role->name }}</span>
                            @endforeach
                        </td>
                        <td>
                            @if($primaryAffiliation)
                                <div>{{ $primaryAffiliation->programStudy?->name ?? $primaryAffiliation->supportUnit?->name ?? $primaryAffiliation->department?->name ?? '—' }}</div>
                                <div class="muted text-xs">
                                    {{ $primaryAffiliation->department?->name ?? $primaryAffiliation->programStudy?->department?->name ?? 'No department' }}
                                </div>
                            @else
                                <span class="muted">No affiliation</span>
                            @endif
                        </td>
                        <td>
                            @if($user->is_active)
                                <span class="badge badge-green">Active</span>
                            @else
                                <span class="badge badge-red">Inactive</span>
                            @endif
                        </td>
                        <td>{{ optional($user->last_login_at)->format('Y-m-d H:i') ?? '-' }}</td>
                        <td>
                            <a class="btn btn-secondary" href="{{ route('users.edit', $user) }}">Edit</a>
                            <form method="POST" action="{{ route('users.toggle', $user) }}" class="inline-form">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-warning" type="submit">
                                    {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="muted">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        <div class="mt-14">{{ $users->links() }}</div>
    </div>
@endsection
