@extends('layouts.app')

@section('content')
    <div class="card mb-16">
        <div class="row-between">
            <div>
                <h2 class="heading-reset">{{ $application->name }}</h2>
                <p class="muted mt-8">{{ $application->slug }}</p>
            </div>
            <div class="flex">
                <a class="btn btn-secondary" href="{{ route('applications.edit', $application) }}">Edit</a>
                <a class="btn btn-secondary" href="{{ route('applications.index') }}">Back</a>
            </div>
        </div>
    </div>

    <div class="card mb-16">
        <h3 class="heading-top-reset">Configuration</h3>
        <p><strong>Status:</strong>
            @if($application->is_active)
                <span class="badge badge-green">Active</span>
            @else
                <span class="badge badge-red">Inactive</span>
            @endif
        </p>
        <p><strong>Redirect URI:</strong> <span class="muted">{{ $application->redirect_uri }}</span></p>
        <p><strong>Logout Callback URI:</strong> <span class="muted">{{ $application->logout_uri ?: '-' }}</span></p>
        <p><strong>Description:</strong> <span class="muted">{{ $application->description ?: '-' }}</span></p>
        <p><strong>Allowed Scopes:</strong>
            @if(!empty($application->allowed_scopes))
                @foreach($application->allowed_scopes as $scope)
                    <span class="badge badge-blue">{{ $scope }}</span>
                @endforeach
            @else
                <span class="muted">-</span>
            @endif
        </p>
        <p><strong>Allowed User Types:</strong>
            @if(!empty($application->allowed_user_types))
                @foreach($application->allowed_user_types as $userType)
                    <span class="badge badge-blue">{{ ucfirst($userType) }}</span>
                @endforeach
            @else
                <span class="muted">All user types</span>
            @endif
        </p>

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
                    <form method="POST" action="{{ route('applications.regenerate-secret', $application) }}" onsubmit="return confirm('Regenerate the client secret? The old secret will stop working immediately.');">
                        @csrf
                        <button class="btn btn-warning" type="submit">Regenerate</button>
                    </form>
                </div>
                <p class="muted mt-8">Keep this secret private. Use it only in server-to-server OAuth token exchange.</p>
            </div>
        </div>
    </div>

    <div class="card mb-16">
        <h3 class="heading-top-reset">Granted Roles</h3>
        @if($application->roles->isEmpty())
            <p class="muted">No roles assigned.</p>
        @else
            <ul class="plain-list">
                @foreach($application->roles as $role)
                    <li>{{ $role->name }} <span class="muted">({{ $role->slug }})</span></li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="card">
        <h3 class="heading-top-reset">Effective Users</h3>
        @if($effectiveUsers->isEmpty())
            <p class="muted">No users inherit access from the selected roles.</p>
        @else
            <ul class="plain-list">
                @foreach($effectiveUsers as $user)
                    <li>{{ $user->name }} <span class="muted">({{ $user->email }})</span></li>
                @endforeach
            </ul>
        @endif
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
