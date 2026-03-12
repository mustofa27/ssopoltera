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
