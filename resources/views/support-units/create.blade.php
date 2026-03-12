@extends('layouts.app')

@section('content')
    <div class="card">
        <h2 class="heading-top-reset">Create Support Unit</h2>

        <form method="POST" action="{{ route('support-units.store') }}">
            @csrf

            <div class="grid mb-16">
                <div>
                    <label class="label" for="code">Code</label>
                    <input class="input" id="code" type="text" name="code" value="{{ old('code') }}" required>
                </div>
                <div>
                    <label class="label" for="name">Name</label>
                    <input class="input" id="name" type="text" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="checkbox-row">
                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                    <label for="is_active">Active</label>
                </div>
            </div>

            <div class="flex">
                <button class="btn" type="submit">Create</button>
                <a class="btn btn-secondary" href="{{ route('support-units.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
