@extends('layouts.app')

@section('content')
    <div class="card">
        <h2 class="heading-top-reset">Edit Program Study</h2>

        <form method="POST" action="{{ route('program-studies.update', $programStudy) }}">
            @csrf
            @method('PUT')

            <div class="grid mb-16">
                <div>
                    <label class="label" for="department_id">Department</label>
                    <select class="input" id="department_id" name="department_id" required>
                        <option value="">Select department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ (string) old('department_id', $programStudy->department_id) === (string) $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="code">Code</label>
                    <input class="input" id="code" type="text" name="code" value="{{ old('code', $programStudy->code) }}" required>
                </div>
                <div>
                    <label class="label" for="name">Name</label>
                    <input class="input" id="name" type="text" name="name" value="{{ old('name', $programStudy->name) }}" required>
                </div>
                <div>
                    <label class="label" for="head_user_id">Head of Program Study</label>
                    <select class="input" id="head_user_id" name="head_user_id">
                        <option value="">Select head (optional)</option>
                        @foreach($headCandidates as $headCandidate)
                            <option value="{{ $headCandidate->id }}" {{ (string) old('head_user_id', $programStudy->head_user_id) === (string) $headCandidate->id ? 'selected' : '' }}>{{ $headCandidate->name }} ({{ $headCandidate->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="academic_degree">Academic Degree</label>
                    <input class="input" id="academic_degree" type="text" name="academic_degree" value="{{ old('academic_degree', $programStudy->academic_degree) }}" placeholder="e.g. D3, S1, S2">
                </div>
                <div class="checkbox-row">
                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $programStudy->is_active) ? 'checked' : '' }}>
                    <label for="is_active">Active</label>
                </div>
            </div>

            <div class="flex">
                <button class="btn" type="submit">Save</button>
                <a class="btn btn-secondary" href="{{ route('program-studies.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
