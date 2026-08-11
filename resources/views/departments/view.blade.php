@extends('layouts.app')

@section('content')
    <div class="card mb-16">
        <div class="row-between">
            <div>
                <h2 class="heading-reset">{{ $department->name }}</h2>
                <p class="muted mt-8">{{ $department->code }}</p>
            </div>
            <div class="flex">
                <a class="btn btn-secondary" href="{{ route('departments.affiliations.create', $department) }}">Attach User</a>
                <a class="btn btn-secondary" href="{{ route('departments.edit', $department) }}">Edit</a>
                <a class="btn btn-secondary" href="{{ route('departments.index') }}">Back</a>
            </div>
        </div>
    </div>

    <div class="card mb-16">
        <h3 class="heading-top-reset">Overview</h3>
        <p><strong>Status:</strong>
            @if($department->is_active)
                <span class="badge badge-green">Active</span>
            @else
                <span class="badge badge-red">Inactive</span>
            @endif
        </p>
        <p><strong>Head of Department:</strong> <span class="muted">{{ $department->head?->name ?: '—' }}</span></p>
    </div>

    <div class="card">
        <h3 class="heading-top-reset">Affiliated Users ({{ $affiliations->count() }})</h3>
        @if($affiliations->isEmpty())
            <p class="muted">No users are affiliated with this department yet.</p>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>Affiliation</th>
                            <th>Primary</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($affiliations as $affiliation)
                            <tr>
                                <td>{{ $affiliation->user?->name ?: '—' }}</td>
                                <td>{{ $affiliation->user?->email ?: '—' }}</td>
                                <td>{{ $affiliation->user?->employee_type ?: $affiliation->user?->user_type ?: '—' }}</td>
                                <td>{{ $affiliation->affiliation_type ?: '—' }}</td>
                                <td>
                                    @if($affiliation->is_primary)
                                        <span class="badge badge-blue">Primary</span>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
