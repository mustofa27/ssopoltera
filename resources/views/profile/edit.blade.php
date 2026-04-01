@extends('layouts.app')

@section('content')
    <div class="card mb-20">
        <h2 class="heading-top-reset">My Profile</h2>
        <p class="muted">Update your display name, email address, or password.</p>
    </div>

    {{-- Profile info --}}
    <div class="card mb-20">
        <h3 class="heading-top-reset">Profile Information</h3>

        @if($user->microsoft_id)
            <p class="muted mt-8">Your profile is managed by Microsoft 365. These fields are updated automatically on each sign-in and cannot be edited here.</p>
            <div class="grid mt-14">
                <div>
                    <div class="label">Name</div>
                    <div>{{ $user->name }}</div>
                </div>
                <div>
                    <div class="label">Email</div>
                    <div>{{ $user->email }}</div>
                </div>
            </div>
        @else
            <form method="POST" action="{{ route('profile.update') }}" class="mt-14">
                @csrf
                @method('PUT')

                <div class="grid mb-16">
                    <div>
                        <label class="label" for="name">Name</label>
                        <input class="input" id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required>
                    </div>
                    <div>
                        <label class="label" for="email">Email</label>
                        <input class="input" id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                    </div>
                </div>

                <div class="flex">
                    <button class="btn" type="submit">Save Changes</button>
                </div>
            </form>
        @endif
    </div>

    {{-- Read-only account info --}}
    <div class="card mb-20">
        <h3 class="heading-top-reset">Account Details</h3>
        <p class="muted mt-8">These fields are managed by your administrator and cannot be edited here.</p>

        <div class="grid mt-14">
            <div>
                <div class="label">User Type</div>
                <div>{{ $user->user_type ? ucfirst($user->user_type) : '—' }}</div>
            </div>
            <div>
                <div class="label">Employee Type</div>
                <div>{{ $user->employee_type ? ucfirst(str_replace('_', ' ', $user->employee_type)) : '—' }}</div>
            </div>
            <div>
                <div class="label">NIP</div>
                <div>{{ $user->nip ?: '—' }}</div>
            </div>
            <div>
                <div class="label">NRP</div>
                <div>{{ $user->nrp ?: '—' }}</div>
            </div>
            <div>
                <div class="label">Job Title</div>
                <div>{{ $user->job_title ?: '—' }}</div>
            </div>
            <div>
                <div class="label">Department</div>
                <div>{{ $user->department ?: '—' }}</div>
            </div>
        </div>
    </div>

    {{-- Change password (only for users with a local password) --}}
    @if($user->password)
        <div class="card">
            <h3 class="heading-top-reset">Change Password</h3>

            <form method="POST" action="{{ route('profile.password') }}" class="mt-14">
                @csrf
                @method('PUT')

                <div class="grid mb-16">
                    <div>
                        <label class="label" for="current_password">Current Password</label>
                        <input class="input" id="current_password" type="password" name="current_password" autocomplete="current-password" required>
                    </div>
                    <div>
                        {{-- spacer --}}
                    </div>
                    <div>
                        <label class="label" for="password">New Password</label>
                        <input class="input" id="password" type="password" name="password" autocomplete="new-password" required>
                    </div>
                    <div>
                        <label class="label" for="password_confirmation">Confirm New Password</label>
                        <input class="input" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
                    </div>
                </div>

                <div class="flex">
                    <button class="btn" type="submit">Change Password</button>
                </div>
            </form>
        </div>
    @else
        <div class="card">
            <h3 class="heading-top-reset">Change Password</h3>
            <p class="muted mt-8">Your account uses Microsoft sign-in. Password management is handled through Microsoft.</p>
        </div>
    @endif
@endsection
