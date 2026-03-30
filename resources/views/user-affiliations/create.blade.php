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
                @php
                    $selectedUserIds = array_map('strval', (array) old('user_ids', []));
                @endphp

                <div class="row-between">
                    <label class="label" for="user_search">Users</label>
                    <div class="form-inline">
                        <button class="btn btn-secondary btn-sm" type="button" id="select_all_users">Select All</button>
                        <button class="btn btn-secondary btn-sm" type="button" id="clear_all_users">Clear</button>
                    </div>
                </div>

                <input
                    class="input mb-12"
                    id="user_search"
                    type="text"
                    placeholder="Search name, email, or current affiliation"
                    autocomplete="off"
                >

                <div id="users_picker" class="choice-group" style="max-height:280px; overflow:auto; padding:8px; border:1px solid #e5e7eb; border-radius:8px;">
                    @foreach($users as $user)
                        @php
                            $primaryAffiliation = $user->primaryAffiliation;
                            $currentAffiliation = $primaryAffiliation?->programStudy?->name
                                ?? $primaryAffiliation?->department?->name
                                ?? 'No primary affiliation';
                            $selected = in_array((string) $user->id, $selectedUserIds, true);
                            $searchText = strtolower($user->name . ' ' . $user->email . ' ' . $currentAffiliation);
                        @endphp
                        <label class="choice-item" data-user-option="{{ $searchText }}" style="display:flex; width:100%; justify-content:flex-start; padding:6px 2px;">
                            <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" {{ $selected ? 'checked' : '' }}>
                            <span>{{ $user->name }} — {{ $user->email }} — {{ $currentAffiliation }}</span>
                        </label>
                    @endforeach
                </div>

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

    <script>
        (function () {
            const searchInput = document.getElementById('user_search');
            const optionRows = Array.from(document.querySelectorAll('[data-user-option]'));
            const selectAllButton = document.getElementById('select_all_users');
            const clearAllButton = document.getElementById('clear_all_users');

            if (!searchInput || optionRows.length === 0 || !selectAllButton || !clearAllButton) {
                return;
            }

            searchInput.addEventListener('input', function () {
                const query = searchInput.value.trim().toLowerCase();

                optionRows.forEach(function (row) {
                    const text = row.getAttribute('data-user-option') || '';
                    row.style.display = text.includes(query) ? 'flex' : 'none';
                });
            });

            selectAllButton.addEventListener('click', function () {
                optionRows.forEach(function (row) {
                    if (row.style.display === 'none') {
                        return;
                    }

                    const checkbox = row.querySelector('input[type="checkbox"]');
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            });

            clearAllButton.addEventListener('click', function () {
                optionRows.forEach(function (row) {
                    const checkbox = row.querySelector('input[type="checkbox"]');
                    if (checkbox) {
                        checkbox.checked = false;
                    }
                });
            });
        })();
    </script>
@endsection