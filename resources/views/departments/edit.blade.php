@extends('layouts.app')

@section('content')
    <div class="card">
        <h2 class="heading-top-reset">Edit Department</h2>

        <form method="POST" action="{{ route('departments.update', $department) }}">
            @csrf
            @method('PUT')

            <div class="grid mb-16">
                <div>
                    <label class="label" for="code">Code</label>
                    <input class="input" id="code" type="text" name="code" value="{{ old('code', $department->code) }}" required>
                </div>
                <div>
                    <label class="label" for="name">Name</label>
                    <input class="input" id="name" type="text" name="name" value="{{ old('name', $department->name) }}" required>
                </div>
                <div>
                    <label class="label" for="head_user_id">Head of Department</label>
                    <div class="user-picker js-user-picker" data-search-url="{{ route('users.search') }}">
                        <input class="input js-user-search" id="head_user_search" type="text" name="head_user_query" value="{{ old('head_user_query', $department->head ? $department->head->name . ' (' . $department->head->email . ')' : '') }}" placeholder="Type name or email to search">
                        <input class="js-user-id" id="head_user_id" type="hidden" name="head_user_id" value="{{ old('head_user_id', $department->head_user_id) }}">
                        <div class="user-picker-results js-user-results"></div>
                    </div>
                    <div class="muted text-xs mt-4">Type at least 2 characters, then pick a user from the list.</div>
                </div>
                <div class="checkbox-row">
                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $department->is_active) ? 'checked' : '' }}>
                    <label for="is_active">Active</label>
                </div>
            </div>

            <div class="flex">
                <button class="btn" type="submit">Save</button>
                <a class="btn btn-secondary" href="{{ route('departments.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
