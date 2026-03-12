@extends('layouts.app')

@section('content')
    <div class="card mb-16">
        <div class="row-between">
            <h2 class="heading-reset">Departments</h2>
            <a class="btn" href="{{ route('departments.create') }}">Create Department</a>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('departments.index') }}" class="mb-16 form-inline">
            <input class="input" type="text" name="q" value="{{ $search }}" placeholder="Search name or code">
            <button class="btn btn-secondary" type="submit">Search</button>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Programs</th>
                        <th>Affiliations</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $department)
                        <tr>
                            <td>{{ $department->code }}</td>
                            <td>{{ $department->name }}</td>
                            <td>
                                @if($department->is_active)
                                    <span class="badge badge-green">Active</span>
                                @else
                                    <span class="badge badge-red">Inactive</span>
                                @endif
                            </td>
                            <td>{{ $department->program_studies_count }}</td>
                            <td>{{ $department->user_affiliations_count }}</td>
                            <td>
                                <a class="btn btn-secondary" href="{{ route('departments.edit', $department) }}">Edit</a>
                                <form method="POST" action="{{ route('departments.destroy', $department) }}" class="inline-form" onsubmit="return confirm('Delete this department?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-warning" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="muted">No departments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-14">{{ $departments->links() }}</div>
    </div>
@endsection
