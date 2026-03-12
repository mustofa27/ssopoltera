@extends('layouts.app')

@section('content')
    <div class="card">
        <h2 class="heading-top-reset">Register Application</h2>
        <p class="muted">Client ID and Client Secret are generated automatically.</p>

        <form method="POST" action="{{ route('applications.store') }}">
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

            <div class="grid mb-16">
                <div>
                    <label class="label" for="redirect_uri">Redirect URI</label>
                    <input class="input" id="redirect_uri" type="url" name="redirect_uri" value="{{ old('redirect_uri') }}" required>
                </div>
                <div>
                    <label class="label" for="logout_uri">Logout Callback URI (optional)</label>
                    <input class="input" id="logout_uri" type="url" name="logout_uri" value="{{ old('logout_uri') }}" placeholder="https://app.example.com/api/sso/logout">
                </div>
            </div>

            <div class="grid mb-16">
                <div>
                    <label class="label" for="logo">Logo URL/Path (optional)</label>
                    <input class="input" id="logo" type="text" name="logo" value="{{ old('logo') }}">
                </div>
                <div></div>
            </div>

            <div class="mb-16">
                <label class="label" for="allowed_scopes">Allowed Scopes (comma separated)</label>
                <input class="input" id="allowed_scopes" type="text" name="allowed_scopes" value="{{ old('allowed_scopes') }}" placeholder="openid, profile, email, User.Read">
            </div>

            <div class="mb-16">
                <label class="choice-item">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                    <span>Active</span>
                </label>
            </div>

            <div class="mb-16">
                <label class="label">Grant Role Access</label>
                <div class="max-h-220">
                    @foreach($roles as $role)
                        <label class="choice-item mb-12">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}" {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }}>
                            <span>{{ $role->name }} <span class="muted">({{ $role->slug }})</span></span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex">
                <button class="btn" type="submit">Create Application</button>
                <a class="btn btn-secondary" href="{{ route('applications.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
