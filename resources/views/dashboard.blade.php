@extends('layouts.app')

@section('content')
    <div class="card mb-20">
        <h2 class="heading-top-reset">Dashboard</h2>
        <p class="muted">Welcome to the SSO portal.</p>
    </div>

    <div class="grid">
        <div class="card">
            <h3 class="heading-top-reset">Users</h3>
            <p class="text-xl mt-8">{{ $userCount }}</p>
            <p class="muted mt-8">Active: {{ $activeUserCount }}</p>
        </div>
        <div class="card">
            <h3 class="heading-top-reset">Roles</h3>
            <p class="text-xl mt-8">{{ $roleCount }}</p>
        </div>
        <div class="card">
            <h3 class="heading-top-reset">Applications</h3>
            <p class="text-xl mt-8">{{ $applicationCount }}</p>
            <p class="muted mt-8">Accessible to you: {{ $accessibleApplicationCount }}</p>
        </div>
    </div>

    <div class="card mt-28">
        <div class="row-between mb-16">
            <div>
                <h3 class="heading-top-reset">Registered Applications</h3>
                <p class="muted mt-8">Open any active application directly from the portal.</p>
            </div>
        </div>

        @if($availableApplications->isEmpty())
            <p class="muted">No active applications are registered.</p>
        @else
            <div class="grid">
                @foreach($availableApplications as $application)
                    <div class="card card-soft">
                        <div class="row-between">
                            <div style="min-width:0; flex:1;">
                                <h4 class="heading-top-reset">{{ $application->name }}</h4>
                                <div class="muted text-sm mt-8">{{ $application->slug }}</div>
                            </div>
                            <span class="badge {{ $application->getAttribute('is_accessible') ? 'badge-green' : 'badge-blue' }}">
                                {{ $application->getAttribute('is_accessible') ? 'Assigned' : 'Available' }}
                            </span>
                        </div>

                        <p class="muted mt-8">{{ $application->description ?: 'No description available.' }}</p>
                        <div class="muted text-xs mt-8" style="white-space:normal; overflow-wrap:anywhere; word-break:break-word;">{{ $application->redirect_uri }}</div>

                        <div class="flex mt-14">
                            <a class="btn" href="{{ $application->redirect_uri }}" target="_blank" rel="noopener noreferrer">Open App</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
