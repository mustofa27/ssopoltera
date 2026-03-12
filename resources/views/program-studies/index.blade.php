@extends('layouts.app')

@section('content')
    <div class="card mb-16">
        <div class="row-between">
            <h2 class="heading-reset">Program Studies</h2>
            <a class="btn" href="{{ route('program-studies.create') }}">Create Program Study</a>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('program-studies.index') }}" class="mb-16 form-inline">
            <input class="input" type="text" name="q" value="{{ $search }}" placeholder="Search name, code, degree">
            <button class="btn btn-secondary" type="submit">Search</button>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Academic Degree</th>
                        <th>Status</th>
                        <th>Affiliations</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($programStudies as $programStudy)
                        <tr>
                            <td>{{ $programStudy->code }}</td>
                            <td>{{ $programStudy->name }}</td>
                            <td>{{ $programStudy->department?->name }}</td>
                            <td>{{ $programStudy->academic_degree ?: '—' }}</td>
                            <td>
                                @if($programStudy->is_active)
                                    <span class="badge badge-green">Active</span>
                                @else
                                    <span class="badge badge-red">Inactive</span>
                                @endif
                            </td>
                            <td>{{ $programStudy->user_affiliations_count }}</td>
                            <td>
                                <a class="btn btn-secondary" href="{{ route('program-studies.edit', $programStudy) }}">Edit</a>
                                <form method="POST" action="{{ route('program-studies.destroy', $programStudy) }}" class="inline-form" onsubmit="return confirm('Delete this program study?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-warning" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="muted">No program studies found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-14">{{ $programStudies->links() }}</div>
    </div>
@endsection
