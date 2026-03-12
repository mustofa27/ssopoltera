@extends('layouts.app')

@section('content')
    <div class="card mb-16">
        <div class="row-between">
            <h2 class="heading-reset">Roles</h2>
            <a class="btn" href="{{ route('roles.create') }}">Create Role</a>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('roles.index') }}" class="mb-16 form-inline">
            <input class="input" type="text" name="q" value="{{ $search }}" placeholder="Search name, slug, description">
            <button class="btn btn-secondary" type="submit">Search</button>
        </form>

        <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Permissions</th>
                    <th>Users</th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                    <tr>
                        <td>{{ $role->name }}</td>
                        <td><span class="muted">{{ $role->slug }}</span></td>
                        <td>{{ is_array($role->permissions) ? count($role->permissions) : 0 }}</td>
                        <td>{{ $role->users_count }}</td>
                        <td>
                            @if($role->is_system)
                                <span class="badge badge-violet">System</span>
                            @else
                                <span class="badge badge-blue">Custom</span>
                            @endif
                        </td>
                        <td>
                            <a class="btn btn-secondary" href="{{ route('roles.edit', $role) }}">Edit</a>

                            @if(!$role->is_system)
                                <form method="POST" action="{{ route('roles.destroy', $role) }}" class="inline-form" onsubmit="return confirm('Delete this role?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-warning" type="submit">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="muted">No roles found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        <div class="mt-14">{{ $roles->links() }}</div>
    </div>
@endsection
