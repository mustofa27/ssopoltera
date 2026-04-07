@extends('layouts.app')

@section('content')
    <div class="card">
        <h2 class="heading-top-reset">Create Program Study</h2>

        <form method="POST" action="{{ route('program-studies.store') }}">
            @csrf

            <div class="grid mb-16">
                <div>
                    <label class="label" for="department_id">Department</label>
                    <select class="input" id="department_id" name="department_id" required>
                        <option value="">Select department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ (string) old('department_id') === (string) $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="code">Code</label>
                    <input class="input" id="code" type="text" name="code" value="{{ old('code') }}" required>
                </div>
                <div>
                    <label class="label" for="name">Name</label>
                    <input class="input" id="name" type="text" name="name" value="{{ old('name') }}" required>
                </div>
                <div>
                    <label class="label" for="head_user_id">Head of Program Study</label>
                    <div class="user-picker js-user-picker" data-search-url="{{ route('users.search') }}">
                        <input class="input js-user-search" id="head_user_search" type="text" name="head_user_query" value="{{ old('head_user_query') }}" placeholder="Type name or email to search">
                        <input class="js-user-id" id="head_user_id" type="hidden" name="head_user_id" value="{{ old('head_user_id') }}">
                        <div class="user-picker-results js-user-results"></div>
                    </div>
                    <div class="muted text-xs mt-4">Type at least 2 characters, then pick a user from the list.</div>
                </div>
                <div>
                    <label class="label" for="academic_degree">Academic Degree</label>
                    <input class="input" id="academic_degree" type="text" name="academic_degree" value="{{ old('academic_degree') }}" placeholder="e.g. D3, S1, S2">
                </div>
                <div class="checkbox-row">
                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                    <label for="is_active">Active</label>
                </div>
            </div>

            <div class="flex">
                <button class="btn" type="submit">Create</button>
                <a class="btn btn-secondary" href="{{ route('program-studies.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
