<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\ProgramStudy;
use App\Models\Role;
use App\Models\SupportUnit;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator as ValidationValidator;
use Illuminate\View\View;

class UserController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $users = User::query()
            ->select(['id', 'name', 'email'])
            ->where(function ($innerQuery) use ($query) {
                $innerQuery
                    ->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit(15)
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'label' => $user->name . ' (' . $user->email . ')',
            ])
            ->values();

        return response()->json(['data' => $users]);
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->with([
                'roles',
                'primaryAffiliation.department',
                'primaryAffiliation.programStudy.department',
                'primaryAffiliation.supportUnit',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%")
                        ->orWhere('job_title', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%")
                        ->orWhere('nrp', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('users.index', compact('users', 'search'));
    }

    public function create(): View
    {
        return view('users.create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateUser($request);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'] ?? null,
            'password_changed_at' => ! empty($validated['password']) ? now() : null,
            'department' => $this->resolveDepartmentName($validated),
            'job_title' => $validated['job_title'] ?? null,
            'user_type' => $validated['user_type'],
            'employee_type' => $validated['employee_type'] ?? null,
            'nip' => $validated['nip'] ?? null,
            'nrp' => $validated['nrp'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'email_verified_at' => now(),
        ]);

        $user->roles()->sync($validated['roles'] ?? []);
        $this->syncAffiliations($user, $validated);

        if (! empty($validated['password'])) {
            $this->storePasswordHistory($user);
        }

        AuditLogger::log(
            event: 'user.management',
            action: 'created',
            request: $request,
            targetType: User::class,
            targetId: $user->id,
            targetLabel: $user->email,
            metadata: [
                'user_type' => $user->user_type,
                'employee_type' => $user->employee_type,
                'roles' => $user->roles->pluck('slug')->all(),
            ]
        );

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        $user->load([
            'roles',
            'affiliations.supportUnit',
            'primaryAffiliation.department',
            'primaryAffiliation.programStudy',
            'primaryAffiliation.supportUnit',
        ]);

        return view('users.edit', array_merge($this->formOptions(), compact('user')));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $this->validateUser($request, $user);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'department' => $this->resolveDepartmentName($validated),
            'job_title' => $validated['job_title'] ?? null,
            'user_type' => $validated['user_type'],
            'employee_type' => $validated['employee_type'] ?? null,
            'nip' => $validated['nip'] ?? null,
            'nrp' => $validated['nrp'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
            $user->password_changed_at = now();
        }

        $user->save();
        $user->roles()->sync($validated['roles'] ?? []);
        $this->syncAffiliations($user, $validated);

        if (! empty($validated['password'])) {
            $this->storePasswordHistory($user);
        }

        AuditLogger::log(
            event: 'user.management',
            action: 'updated',
            request: $request,
            targetType: User::class,
            targetId: $user->id,
            targetLabel: $user->email,
            metadata: [
                'is_active' => $user->is_active,
                'user_type' => $user->user_type,
                'employee_type' => $user->employee_type,
            ]
        );

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function toggle(User $user): RedirectResponse
    {
        $user->update(['is_active' => ! $user->is_active]);

        AuditLogger::log(
            event: 'user.management',
            action: $user->is_active ? 'activated' : 'deactivated',
            request: request(),
            targetType: User::class,
            targetId: $user->id,
            targetLabel: $user->email,
            metadata: ['is_active' => $user->is_active]
        );

        return redirect()->route('users.index')->with('success', 'User status updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'roles' => Role::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'programStudies' => ProgramStudy::with('department')->orderBy('name')->get(),
            'supportUnits' => SupportUnit::orderBy('name')->get(),
            'userTypes' => [
                'student' => 'Student',
                'employee' => 'Employee',
            ],
            'employeeTypes' => [
                'lecturer' => 'Lecturer',
                'lab_technician' => 'Laboratory Technician',
                'staff' => 'Staff / Administration',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateUser(Request $request, ?User $user = null): array
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => $this->passwordRules(),
            'job_title' => ['nullable', 'string', 'max:255'],
            'user_type' => ['required', Rule::in(['student', 'employee'])],
            'employee_type' => ['nullable', Rule::in(['lecturer', 'lab_technician', 'staff'])],
            'nip' => ['nullable', 'string', 'max:50', Rule::unique('users', 'nip')->ignore($user?->id)],
            'nrp' => ['nullable', 'string', 'max:50', Rule::unique('users', 'nrp')->ignore($user?->id)],
            'is_active' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
            'primary_department_id' => ['nullable', 'exists:departments,id'],
            'primary_program_study_id' => ['nullable', 'exists:program_studies,id'],
            'primary_support_unit_id' => ['nullable', 'exists:support_units,id'],
            'support_unit_ids' => ['nullable', 'array'],
            'support_unit_ids.*' => ['exists:support_units,id'],
        ]);

        $validator->after(function (ValidationValidator $validator) use ($user) {
            $data = $validator->getData();

            $userType = $data['user_type'] ?? null;
            $employeeType = $data['employee_type'] ?? null;
            $primaryDepartmentId = $data['primary_department_id'] ?? null;
            $primaryProgramStudyId = $data['primary_program_study_id'] ?? null;
            $primarySupportUnitId = $data['primary_support_unit_id'] ?? null;
            $supportUnitIds = array_filter((array) ($data['support_unit_ids'] ?? []));

            if ($userType === 'student') {
                if (empty($data['nrp'])) {
                    $validator->errors()->add('nrp', 'NRP is required for students.');
                }

                if (! empty($data['nip'])) {
                    $validator->errors()->add('nip', 'NIP must be empty for students.');
                }

                if (! empty($employeeType)) {
                    $validator->errors()->add('employee_type', 'Employee type must be empty for students.');
                }

                if (empty($primaryProgramStudyId)) {
                    $validator->errors()->add('primary_program_study_id', 'Primary program study is required for students.');
                }
            }

            if ($userType === 'employee') {
                if (empty($data['nip'])) {
                    $validator->errors()->add('nip', 'NIP is required for employees.');
                }

                if (! empty($data['nrp'])) {
                    $validator->errors()->add('nrp', 'NRP must be empty for employees.');
                }

                if (empty($employeeType)) {
                    $validator->errors()->add('employee_type', 'Employee type is required for employees.');
                }

                if ($employeeType === 'lecturer' && empty($primaryProgramStudyId)) {
                    $validator->errors()->add('primary_program_study_id', 'Primary program study is required for lecturers.');
                }

                if ($employeeType === 'staff' && empty($primarySupportUnitId) && count($supportUnitIds) === 0) {
                    $validator->errors()->add('primary_support_unit_id', 'At least one support unit is required for staff.');
                }

                if ($employeeType === 'lab_technician' && empty($primaryDepartmentId) && empty($primaryProgramStudyId) && empty($primarySupportUnitId) && count($supportUnitIds) === 0) {
                    $validator->errors()->add('primary_department_id', 'At least one affiliation is required for laboratory technicians.');
                }
            }

            if (! empty($primaryProgramStudyId)) {
                $programStudy = ProgramStudy::find($primaryProgramStudyId);

                if ($programStudy && ! empty($primaryDepartmentId) && (int) $programStudy->department_id !== (int) $primaryDepartmentId) {
                    $validator->errors()->add('primary_program_study_id', 'Selected program study does not belong to the selected department.');
                }
            }

            if ($user && ! empty($data['password']) && $this->isPasswordReused($user, (string) $data['password'])) {
                $validator->errors()->add('password', 'Password cannot match your recent password history.');
            }
        });

        return $validator->validate();
    }

    /**
     * @return array<int, string|Password>
     */
    private function passwordRules(): array
    {
        $policy = config('security.password_policy', []);

        $rule = Password::min((int) ($policy['min_length'] ?? 10));

        if ($policy['require_letters'] ?? true) {
            $rule = $rule->letters();
        }

        if ($policy['require_mixed_case'] ?? true) {
            $rule = $rule->mixedCase();
        }

        if ($policy['require_numbers'] ?? true) {
            $rule = $rule->numbers();
        }

        if ($policy['require_symbols'] ?? true) {
            $rule = $rule->symbols();
        }

        return ['nullable', $rule];
    }

    private function isPasswordReused(User $user, string $plainPassword): bool
    {
        if (! empty($user->password) && Hash::check($plainPassword, $user->password)) {
            return true;
        }

        $historyCount = (int) config('security.password_policy.history_count', 5);

        if ($historyCount <= 0) {
            return false;
        }

        $historyHashes = DB::table('user_password_histories')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit($historyCount)
            ->pluck('password');

        foreach ($historyHashes as $hash) {
            if (Hash::check($plainPassword, (string) $hash)) {
                return true;
            }
        }

        return false;
    }

    private function storePasswordHistory(User $user): void
    {
        if (empty($user->password)) {
            return;
        }

        DB::table('user_password_histories')->insert([
            'user_id' => $user->id,
            'password' => $user->password,
            'password_changed_at' => $user->password_changed_at ?? now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $historyCount = (int) config('security.password_policy.history_count', 5);

        if ($historyCount <= 0) {
            return;
        }

        $idsToKeep = DB::table('user_password_histories')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit($historyCount)
            ->pluck('id');

        if ($idsToKeep->isEmpty()) {
            return;
        }

        DB::table('user_password_histories')
            ->where('user_id', $user->id)
            ->whereNotIn('id', $idsToKeep)
            ->delete();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncAffiliations(User $user, array $validated): void
    {
        $user->affiliations()->delete();

        $programStudy = ! empty($validated['primary_program_study_id'])
            ? ProgramStudy::find($validated['primary_program_study_id'])
            : null;

        $primaryDepartmentId = $validated['primary_department_id'] ?? $programStudy?->department_id;
        $primarySupportUnitId = $validated['primary_support_unit_id'] ?? null;
        $affiliationType = $validated['user_type'] === 'student'
            ? 'student'
            : ($validated['employee_type'] ?? 'employee');

        if ($primaryDepartmentId || $programStudy || $primarySupportUnitId) {
            $user->affiliations()->create([
                'department_id' => $primaryDepartmentId,
                'program_study_id' => $programStudy?->id,
                'support_unit_id' => $primarySupportUnitId,
                'affiliation_type' => $affiliationType,
                'is_primary' => true,
            ]);
        }

        Collection::make($validated['support_unit_ids'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn (int $id) => $primarySupportUnitId && $id === (int) $primarySupportUnitId)
            ->each(function (int $supportUnitId) use ($user, $affiliationType) {
                $user->affiliations()->create([
                    'support_unit_id' => $supportUnitId,
                    'affiliation_type' => $affiliationType,
                    'is_primary' => false,
                ]);
            });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveDepartmentName(array $validated): ?string
    {
        if (! empty($validated['primary_program_study_id'])) {
            $programStudy = ProgramStudy::with('department')->find($validated['primary_program_study_id']);

            return $programStudy?->department?->name;
        }

        if (! empty($validated['primary_department_id'])) {
            return Department::find($validated['primary_department_id'])?->name;
        }

        return null;
    }
}
