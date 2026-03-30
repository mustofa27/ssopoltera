<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\ProgramStudy;
use App\Models\User;
use App\Models\UserAffiliation;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserAffiliationController extends Controller
{
    public function createForDepartment(Department $department): View
    {
        return view('user-affiliations.create', [
            'pageTitle' => 'Attach User to Department',
            'pageDescription' => 'Create a department affiliation for an existing user.',
            'submitUrl' => route('departments.affiliations.store', $department),
            'cancelUrl' => route('departments.index'),
            'department' => $department,
            'programStudy' => null,
            'users' => $this->userOptions(),
            'primaryHint' => 'Set this only when the department should become the user\'s primary affiliation. For students and lecturers, use the program study attach flow for primary affiliation.',
        ]);
    }

    public function storeForDepartment(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $user = User::query()->findOrFail($validated['user_id']);
        $isPrimary = (bool) ($validated['is_primary'] ?? false);

        if ($isPrimary && $this->requiresProgramStudyPrimary($user)) {
            return back()
                ->withErrors(['is_primary' => 'Primary affiliation for this user must be attached from Program Studies.'])
                ->withInput();
        }

        $exists = UserAffiliation::query()
            ->where('user_id', $user->id)
            ->where('department_id', $department->id)
            ->whereNull('program_study_id')
            ->whereNull('support_unit_id')
            ->exists();

        if ($exists) {
            return back()
                ->withErrors(['user_id' => 'This user is already attached to the selected department.'])
                ->withInput();
        }

        DB::transaction(function () use ($department, $user, $isPrimary) {
            if ($isPrimary) {
                $user->affiliations()->update(['is_primary' => false]);
            }

            UserAffiliation::create([
                'user_id' => $user->id,
                'department_id' => $department->id,
                'affiliation_type' => $this->resolveAffiliationType($user),
                'is_primary' => $isPrimary,
            ]);

            if ($isPrimary) {
                $user->forceFill([
                    'department' => $department->name,
                ])->save();
            }
        });

        AuditLogger::log(
            event: 'organization.management',
            action: 'department_affiliation_attached',
            request: $request,
            userId: $request->user()?->id,
            targetType: Department::class,
            targetId: $department->id,
            targetLabel: $department->name,
            metadata: [
                'attached_user_id' => $user->id,
                'attached_user_email' => $user->email,
                'is_primary' => $isPrimary,
            ]
        );

        return redirect()->route('departments.index')->with('success', 'User affiliation attached to department successfully.');
    }

    public function createForProgramStudy(ProgramStudy $programStudy): View
    {
        $programStudy->loadMissing('department');

        return view('user-affiliations.create', [
            'pageTitle' => 'Attach User to Program Study',
            'pageDescription' => 'Create a program study affiliation for an existing user.',
            'submitUrl' => route('program-studies.affiliations.store', $programStudy),
            'cancelUrl' => route('program-studies.index'),
            'department' => $programStudy->department,
            'programStudy' => $programStudy,
            'users' => $this->userOptions(),
            'primaryHint' => 'If set as primary, the user\'s previous primary affiliation will be replaced.',
        ]);
    }

    public function storeForProgramStudy(Request $request, ProgramStudy $programStudy): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $programStudy->loadMissing('department');
        $user = User::query()->findOrFail($validated['user_id']);
        $isPrimary = (bool) ($validated['is_primary'] ?? false);

        $exists = UserAffiliation::query()
            ->where('user_id', $user->id)
            ->where('program_study_id', $programStudy->id)
            ->exists();

        if ($exists) {
            return back()
                ->withErrors(['user_id' => 'This user is already attached to the selected program study.'])
                ->withInput();
        }

        DB::transaction(function () use ($programStudy, $user, $isPrimary) {
            if ($isPrimary) {
                $user->affiliations()->update(['is_primary' => false]);
            }

            UserAffiliation::create([
                'user_id' => $user->id,
                'department_id' => $programStudy->department_id,
                'program_study_id' => $programStudy->id,
                'affiliation_type' => $this->resolveAffiliationType($user),
                'is_primary' => $isPrimary,
            ]);

            if ($isPrimary) {
                $user->forceFill([
                    'department' => $programStudy->department?->name,
                ])->save();
            }
        });

        AuditLogger::log(
            event: 'organization.management',
            action: 'program_study_affiliation_attached',
            request: $request,
            userId: $request->user()?->id,
            targetType: ProgramStudy::class,
            targetId: $programStudy->id,
            targetLabel: $programStudy->name,
            metadata: [
                'attached_user_id' => $user->id,
                'attached_user_email' => $user->email,
                'department_id' => $programStudy->department_id,
                'is_primary' => $isPrimary,
            ]
        );

        return redirect()->route('program-studies.index')->with('success', 'User affiliation attached to program study successfully.');
    }

    private function requiresProgramStudyPrimary(User $user): bool
    {
        return $user->user_type === 'student' || $user->employee_type === 'lecturer';
    }

    private function resolveAffiliationType(User $user): string
    {
        if ($user->user_type === 'student') {
            return 'student';
        }

        return $user->employee_type ?: 'employee';
    }

    private function userOptions()
    {
        return User::query()
            ->with(['primaryAffiliation.department', 'primaryAffiliation.programStudy'])
            ->orderBy('name')
            ->get();
    }
}