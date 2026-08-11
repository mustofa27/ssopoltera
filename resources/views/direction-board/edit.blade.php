@extends('layouts.app')

@section('content')
    <div class="card">
        <h2 class="heading-top-reset">Direction Board</h2>

        <form method="POST" action="{{ route('direction-board.update') }}">
            @csrf
            @method('PUT')

            <div class="grid grid-3 mb-16">
                <div>
                    <label class="label" for="director_user_id">Director</label>
                    <div class="user-picker js-user-picker" data-search-url="{{ route('users.search') }}">
                        <input class="input js-user-search" id="director_user_search" type="text" name="director_user_query" value="{{ old('director_user_query', $directionBoard->director ? $directionBoard->director->name . ' (' . $directionBoard->director->email . ')' : '') }}" placeholder="Type name or email to search">
                        <input class="js-user-id" id="director_user_id" type="hidden" name="director_user_id" value="{{ old('director_user_id', $directionBoard->director_user_id) }}">
                        <div class="user-picker-results js-user-results"></div>
                    </div>
                </div>
                <div>
                    <label class="label" for="vice_director_1_user_id">Vice Director 1</label>
                    <div class="user-picker js-user-picker" data-search-url="{{ route('users.search') }}">
                        <input class="input js-user-search" id="vice_director_1_user_search" type="text" name="vice_director_1_user_query" value="{{ old('vice_director_1_user_query', $directionBoard->viceDirector1 ? $directionBoard->viceDirector1->name . ' (' . $directionBoard->viceDirector1->email . ')' : '') }}" placeholder="Type name or email to search">
                        <input class="js-user-id" id="vice_director_1_user_id" type="hidden" name="vice_director_1_user_id" value="{{ old('vice_director_1_user_id', $directionBoard->vice_director_1_user_id) }}">
                        <div class="user-picker-results js-user-results"></div>
                    </div>
                </div>
                <div>
                    <label class="label" for="vice_director_2_user_id">Vice Director 2</label>
                    <div class="user-picker js-user-picker" data-search-url="{{ route('users.search') }}">
                        <input class="input js-user-search" id="vice_director_2_user_search" type="text" name="vice_director_2_user_query" value="{{ old('vice_director_2_user_query', $directionBoard->viceDirector2 ? $directionBoard->viceDirector2->name . ' (' . $directionBoard->viceDirector2->email . ')' : '') }}" placeholder="Type name or email to search">
                        <input class="js-user-id" id="vice_director_2_user_id" type="hidden" name="vice_director_2_user_id" value="{{ old('vice_director_2_user_id', $directionBoard->vice_director_2_user_id) }}">
                        <div class="user-picker-results js-user-results"></div>
                    </div>
                </div>
                <div>
                    <label class="label" for="vice_director_3_user_id">Vice Director 3</label>
                    <div class="user-picker js-user-picker" data-search-url="{{ route('users.search') }}">
                        <input class="input js-user-search" id="vice_director_3_user_search" type="text" name="vice_director_3_user_query" value="{{ old('vice_director_3_user_query', $directionBoard->viceDirector3 ? $directionBoard->viceDirector3->name . ' (' . $directionBoard->viceDirector3->email . ')' : '') }}" placeholder="Type name or email to search">
                        <input class="js-user-id" id="vice_director_3_user_id" type="hidden" name="vice_director_3_user_id" value="{{ old('vice_director_3_user_id', $directionBoard->vice_director_3_user_id) }}">
                        <div class="user-picker-results js-user-results"></div>
                    </div>
                </div>
            </div>
            <div class="muted text-xs mb-16">Type at least 2 characters, then pick a user from the list. Leave a field empty to leave that position vacant.</div>

            @error('director_user_id')
                <div class="alert alert-error mb-16">{{ $message }}</div>
            @enderror

            <div class="flex">
                <button class="btn" type="submit">Save</button>
            </div>
        </form>
    </div>
@endsection
