@extends('layouts.app')

@section('content')
    <div class="card mb-16">
        <div class="row-between">
            <h2 class="heading-reset">Applications</h2>
            <a class="btn" href="{{ route('applications.create') }}">Register Application</a>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('applications.index') }}" class="mb-16 form-inline">
            <input class="input" type="text" name="q" value="{{ $search }}" placeholder="Search name, slug, client ID">
            <button class="btn btn-secondary" type="submit">Search</button>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Client ID</th>
                        <th>Redirect URI</th>
                        <th>Roles</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applications as $application)
                        <tr>
                            <td>
                                <strong>{{ $application->name }}</strong><br>
                                <span class="muted">{{ $application->slug }}</span>
                            </td>
                            <td><span class="muted">{{ \Illuminate\Support\Str::limit($application->client_id, 18) }}</span></td>
                            <td><span class="muted">{{ \Illuminate\Support\Str::limit($application->redirect_uri, 38) }}</span></td>
                            <td>{{ $application->roles_count }}</td>
                            <td>
                                @if($application->is_active)
                                    <span class="badge badge-green">Active</span>
                                @else
                                    <span class="badge badge-red">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <a class="btn btn-secondary" href="{{ route('applications.view', $application) }}">View</a>
                                <a class="btn btn-secondary" href="{{ route('applications.edit', $application) }}">Edit</a>
                                <form method="POST" action="{{ route('applications.destroy', $application) }}" class="inline-form" onsubmit="return confirm('Delete this application?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-warning" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="muted">No applications found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-14">{{ $applications->links() }}</div>
    </div>
@endsection
