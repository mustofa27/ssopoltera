@extends('layouts.app')

@section('content')
    <div class="card mb-16">
        <div class="row-between">
            <h2 class="heading-reset">Support Units</h2>
            <a class="btn" href="{{ route('support-units.create') }}">Create Support Unit</a>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('support-units.index') }}" class="mb-16 form-inline">
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
                        <th>Affiliations</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($supportUnits as $supportUnit)
                        <tr>
                            <td>{{ $supportUnit->code }}</td>
                            <td>{{ $supportUnit->name }}</td>
                            <td>
                                @if($supportUnit->is_active)
                                    <span class="badge badge-green">Active</span>
                                @else
                                    <span class="badge badge-red">Inactive</span>
                                @endif
                            </td>
                            <td>{{ $supportUnit->user_affiliations_count }}</td>
                            <td>
                                <a class="btn btn-secondary" href="{{ route('support-units.edit', $supportUnit) }}">Edit</a>
                                <form method="POST" action="{{ route('support-units.destroy', $supportUnit) }}" class="inline-form" onsubmit="return confirm('Delete this support unit?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-warning" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="muted">No support units found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-14">{{ $supportUnits->links() }}</div>
    </div>
@endsection
