@extends('layouts.app')

@section('content')
    <div class="card">
        <h2 class="heading-top-reset">Create Role</h2>

        <form method="POST" action="{{ route('roles.store') }}">
            @csrf

            <div class="grid mb-16">
                <div>
                    <label class="label" for="name">Name</label>
                    <input class="input" id="name" type="text" name="name" value="{{ old('name') }}" required>
                </div>
                <div>
                    <label class="label" for="slug">Slug (optional)</label>
                    <input class="input" id="slug" type="text" name="slug" value="{{ old('slug') }}" placeholder="auto-generated if empty">
                </div>
            </div>

            <div class="mb-16">
                <label class="label" for="description">Description</label>
                <textarea class="input" id="description" name="description" rows="3">{{ old('description') }}</textarea>
            </div>

            <div class="mb-16">
                <label class="label">Permissions</label>
                <div class="choice-group">
                    @foreach($permissions as $permission)
                        <label class="choice-item">
                            <input type="checkbox" name="permissions[]" value="{{ $permission }}" {{ in_array($permission, old('permissions', [])) ? 'checked' : '' }}>
                            <span>{{ $permission }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex">
                <button class="btn" type="submit">Create Role</button>
                <a class="btn btn-secondary" href="{{ route('roles.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
