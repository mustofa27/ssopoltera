@extends('layouts.app')

@section('content')
    <div class="card">
        <h2 class="heading-top-reset">Edit Application</h2>

        <form id="regenerate-secret-form" method="POST" action="{{ route('applications.regenerate-secret', $application) }}" onsubmit="return confirm('Regenerate the client secret? The old secret will stop working immediately.');">
            @csrf
        </form>

        <form method="POST" action="{{ route('applications.update', $application) }}">
            @csrf
            @method('PUT')

            <div class="grid mb-16">
                <div>
                    <label class="label" for="name">Name</label>
                    <input class="input" id="name" type="text" name="name" value="{{ old('name', $application->name) }}" required>
                </div>
                <div>
                    <label class="label" for="slug">Slug</label>
                    <input class="input" id="slug" type="text" name="slug" value="{{ old('slug', $application->slug) }}" required>
                </div>
            </div>

            <div class="mb-16">
                <label class="label" for="description">Description</label>
                <textarea class="input" id="description" name="description" rows="3">{{ old('description', $application->description) }}</textarea>
            </div>

            <div class="grid mb-16">
                <div>
                    <label class="label" for="redirect_uri">Redirect URI</label>
                    <input class="input" id="redirect_uri" type="url" name="redirect_uri" value="{{ old('redirect_uri', $application->redirect_uri) }}" required>
                </div>
                <div>
                    <label class="label" for="logout_uri">Logout Callback URI (optional)</label>
                    <input class="input" id="logout_uri" type="url" name="logout_uri" value="{{ old('logout_uri', $application->logout_uri) }}" placeholder="https://app.example.com/api/sso/logout">
                </div>
            </div>

            <div class="grid mb-16">
                <div>
                    <label class="label" for="logo">Logo URL/Path (optional)</label>
                    <input class="input" id="logo" type="text" name="logo" value="{{ old('logo', $application->logo) }}">
                </div>
                <div></div>
            </div>

            @php $selectedScopes = old('allowed_scopes', is_array($application->allowed_scopes) ? implode(', ', $application->allowed_scopes) : ''); @endphp
            <div class="mb-16">
                <label class="label" for="allowed_scopes">Allowed Scopes (comma separated)</label>
                <input class="input" id="allowed_scopes" type="text" name="allowed_scopes" value="{{ $selectedScopes }}">
            </div>

            @php $selectedUserTypes = old('allowed_user_types', $application->allowed_user_types ?? []); @endphp
            <div class="mb-16">
                <label class="label">Allowed User Types</label>
                <div class="choice-group">
                    @foreach($userTypes as $value => $label)
                        <label class="choice-item">
                            <input type="checkbox" name="allowed_user_types[]" value="{{ $value }}" {{ in_array($value, $selectedUserTypes, true) ? 'checked' : '' }}>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="muted mt-8">Leave unchecked to allow all user types.</p>
            </div>

            <div class="mb-16">
                <label class="choice-item">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $application->is_active) ? 'checked' : '' }}>
                    <span>Active</span>
                </label>
            </div>

            @php $selectedRoles = old('roles', $application->roles->pluck('id')->all()); @endphp
            <div class="mb-16">
                <label class="label">Grant Role Access</label>
                <div class="max-h-220">
                    @foreach($roles as $role)
                        <label class="choice-item mb-12">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}" {{ in_array($role->id, $selectedRoles) ? 'checked' : '' }}>
                            <span>{{ $role->name }} <span class="muted">({{ $role->slug }})</span></span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="mb-16 card card-soft">
                <div class="mb-12">
                    <label class="label" for="client_id_display">Client ID</label>
                    <div class="form-inline">
                        <input class="input" id="client_id_display" type="text" value="{{ $application->client_id }}" readonly>
                        <button class="btn btn-secondary" type="button" onclick="copyCredential('client_id_display')">Copy</button>
                    </div>
                </div>

                <div>
                    <label class="label" for="client_secret_display">Client Secret</label>
                    <div class="form-inline">
                        <input class="input" id="client_secret_display" type="password" value="{{ $application->client_secret }}" readonly>
                        <button class="btn btn-secondary" type="button" onclick="toggleSecret('client_secret_display', this)">Show</button>
                        <button class="btn btn-secondary" type="button" onclick="copyCredential('client_secret_display')">Copy</button>
                        <button class="btn btn-warning" type="submit" form="regenerate-secret-form">Regenerate</button>
                    </div>
                </div>
            </div>

            <div class="flex">
                <button class="btn" type="submit">Save Changes</button>
                <a class="btn btn-secondary" href="{{ route('applications.index') }}">Cancel</a>
            </div>
        </form>
    </div>

    <script>
        function toggleSecret(inputId, button) {
            const input = document.getElementById(inputId);
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            button.textContent = isHidden ? 'Hide' : 'Show';
        }

        function copyCredential(inputId) {
            const input = document.getElementById(inputId);
            const originalType = input.type;
            if (originalType === 'password') {
                input.type = 'text';
            }
            input.select();
            input.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(input.value).then(() => showToast('Copied to clipboard'));
            if (originalType === 'password') {
                input.type = 'password';
            }
        }
    </script>
@endsection
