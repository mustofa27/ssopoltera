@extends('layouts.app')

@section('content')
    <div class="card">
        <h2 class="heading-top-reset">Edit Role</h2>

        <form method="POST" action="{{ route('roles.update', $role) }}">
            @csrf
            @method('PUT')

            <div class="grid mb-16">
                <div>
                    <label class="label" for="name">Name</label>
                    <input class="input" id="name" type="text" name="name" value="{{ old('name', $role->name) }}" required>
                </div>
                <div>
                    <label class="label" for="slug">Slug</label>
                    <input class="input" id="slug" type="text" name="slug" value="{{ old('slug', $role->slug) }}" required>
                </div>
            </div>

            <div class="mb-16">
                <label class="label" for="description">Description</label>
                <textarea class="input" id="description" name="description" rows="3">{{ old('description', $role->description) }}</textarea>
            </div>

            @php $selectedPermissions = old('permissions', $role->permissions ?? []); @endphp
            <div class="mb-16">
                <label class="label">Permissions</label>
                <div class="choice-group">
                    @foreach($permissions as $permission)
                        <label class="choice-item">
                            <input type="checkbox" name="permissions[]" value="{{ $permission }}" {{ in_array($permission, $selectedPermissions) ? 'checked' : '' }}>
                            <span>{{ $permission }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="mb-16">
                @if($role->is_system)
                    <span class="badge badge-violet">System Role</span>
                    <span class="muted">System roles are protected from deletion.</span>
                @endif
            </div>

            <div class="flex">
                <button class="btn" type="submit">Save Role</button>
                <a class="btn btn-secondary" href="{{ route('roles.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
