@extends('layouts.app')

@section('content')
    <div class="card">
        <h2 class="heading-top-reset">Create User</h2>

        <form method="POST" action="{{ route('users.store') }}">
            @csrf

            <div class="grid mb-16">
                <div>
                    <label class="label" for="name">Name</label>
                    <input class="input" id="name" type="text" name="name" value="{{ old('name') }}" required>
                </div>
                <div>
                    <label class="label" for="email">Email</label>
                    <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required>
                </div>
                <div>
                    <label class="label" for="password">Password (optional for SSO users)</label>
                    <input class="input" id="password" type="password" name="password">
                </div>
                <div>
                    <label class="label" for="job_title">Job Title</label>
                    <input class="input" id="job_title" type="text" name="job_title" value="{{ old('job_title') }}">
                </div>
                <div>
                    <label class="label" for="user_type">User Type</label>
                    <select class="input" id="user_type" name="user_type" required>
                        <option value="">Select user type</option>
                        @foreach($userTypes as $value => $label)
                            <option value="{{ $value }}" {{ old('user_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="employee_type">Employee Type</label>
                    <select class="input" id="employee_type" name="employee_type">
                        <option value="">Select employee type</option>
                        @foreach($employeeTypes as $value => $label)
                            <option value="{{ $value }}" {{ old('employee_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="nip">NIP</label>
                    <input class="input" id="nip" type="text" name="nip" value="{{ old('nip') }}">
                </div>
                <div>
                    <label class="label" for="nrp">NRP</label>
                    <input class="input" id="nrp" type="text" name="nrp" value="{{ old('nrp') }}">
                </div>
                <div>
                    <label class="label" for="primary_department_id">Primary Department</label>
                    <select class="input" id="primary_department_id" name="primary_department_id">
                        <option value="">Select department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ (string) old('primary_department_id') === (string) $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="primary_program_study_id">Primary Program Study</label>
                    <select class="input" id="primary_program_study_id" name="primary_program_study_id">
                        <option value="">Select program study</option>
                        @foreach($programStudies as $programStudy)
                            <option value="{{ $programStudy->id }}" {{ (string) old('primary_program_study_id') === (string) $programStudy->id ? 'selected' : '' }}>
                                {{ $programStudy->name }}{{ $programStudy->academic_degree ? ' (' . $programStudy->academic_degree . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label" for="primary_support_unit_id">Primary Support Unit</label>
                    <select class="input" id="primary_support_unit_id" name="primary_support_unit_id">
                        <option value="">Select support unit</option>
                        @foreach($supportUnits as $supportUnit)
                            <option value="{{ $supportUnit->id }}" {{ (string) old('primary_support_unit_id') === (string) $supportUnit->id ? 'selected' : '' }}>{{ $supportUnit->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="checkbox-row">
                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                    <label for="is_active">Active</label>
                </div>
            </div>

            <div class="mb-16">
                <label class="label">Additional Support Units</label>
                <div class="choice-group">
                    @foreach($supportUnits as $supportUnit)
                        <label class="choice-item">
                            <input type="checkbox" name="support_unit_ids[]" value="{{ $supportUnit->id }}" {{ in_array($supportUnit->id, old('support_unit_ids', [])) ? 'checked' : '' }}>
                            <span>{{ $supportUnit->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="mb-16">
                <label class="label">Roles</label>
                <div class="choice-group">
                    @foreach($roles as $role)
                        <label class="choice-item">
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}" {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }}>
                            <span>{{ $role->name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex">
                <button class="btn" type="submit">Create</button>
                <a class="btn btn-secondary" href="{{ route('users.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
