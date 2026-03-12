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
        </div>
    </div>
@endsection
