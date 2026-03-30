@extends('layouts.app')

@section('content')
    <div class="card">
        <h2 class="heading-top-reset">{{ $pageTitle }}</h2>
        <p class="muted mt-8 mb-16">{{ $pageDescription }}</p>

        <div class="grid mb-16">
            <div>
                <div class="label">Department</div>
                <div class="input">{{ $department?->name ?: '—' }}</div>
            </div>
            @if($programStudy)
                <div>
                    <div class="label">Program Study</div>
                    <div class="input">{{ $programStudy->name }}{{ $programStudy->academic_degree ? ' (' . $programStudy->academic_degree . ')' : '' }}</div>
                </div>
            @endif
        </div>

        <form method="POST" action="{{ $submitUrl }}">
            @csrf

            <div class="mb-16">
                <label class="label" for="user_ids">Users <span class="muted text-sm">(hold Ctrl / Cmd to select multiple)</span></label>
                <select class="input" id="user_ids" name="user_ids[]" multiple required style="height:220px">
                    @foreach($users as $user)
                        @php
                            $primaryAffiliation = $user->primaryAffiliation;
                            $currentAffiliation = $primaryAffiliation?->programStudy?->name
                                ?? $primaryAffiliation?->department?->name
                                ?? 'No primary affiliation';
                            $selected = in_array((string) $user->id, (array) old('user_ids', []));
                        @endphp
                        <option value="{{ $user->id }}" {{ $selected ? 'selected' : '' }}>
                            {{ $user->name }} — {{ $user->email }} — {{ $currentAffiliation }}
                        </option>
                    @endforeach
                </select>
                @error('user_ids')
                    <div class="muted text-red mt-4">{{ $message }}</div>
                @enderror
                @error('user_ids.*')
                    <div class="muted text-red mt-4">{{ $message }}</div>
                @enderror
            </div>

            <div class="checkbox-row mb-16">
                <input type="checkbox" id="is_primary" name="is_primary" value="1" {{ old('is_primary') ? 'checked' : '' }}>
                <label for="is_primary">Set as primary affiliation</label>
            </div>
            <div class="muted text-sm mb-16">{{ $primaryHint }}</div>
            @error('is_primary')
                <div class="muted text-red mb-16">{{ $message }}</div>
            @enderror

            <div class="flex">
                <button class="btn" type="submit">Attach User</button>
                <a class="btn btn-secondary" href="{{ $cancelUrl }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection