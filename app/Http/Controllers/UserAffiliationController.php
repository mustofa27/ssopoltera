<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\ProgramStudy;
use App\Models\SupportUnit;
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
            'supportUnit' => null,
            'users' => $this->userOptions(),
            'primaryHint' => 'Set this only when the department should become the user\'s primary affiliation. For students and lecturers, use the program study attach flow for primary affiliation.',
        ]);
    }

    public function storeForDepartment(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'user_ids'   => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'exists:users,id'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $isPrimary = (bool) ($validated['is_primary'] ?? false);
        $users = User::query()->whereIn('id', $validated['user_ids'])->get();
        $attached = 0;
        $skipped = [];

        foreach ($users as $user) {
            if ($isPrimary && $this->requiresProgramStudyPrimary($user)) {
                $skipped[] = $user->email . ' (must use Program Studies for primary)';
                continue;
            }

            $exists = UserAffiliation::query()
                ->where('user_id', $user->id)
                ->where('department_id', $department->id)
                ->whereNull('program_study_id')
                ->whereNull('support_unit_id')
                ->exists();

            if ($exists) {
                $skipped[] = $user->email . ' (already attached)';
                continue;
            }

            DB::transaction(function () use ($department, $user, $isPrimary) {
                if ($isPrimary) {
                    $user->affiliations()->update(['is_primary' => false]);
                }

                UserAffiliation::create([
                    'user_id'          => $user->id,
                    'department_id'    => $department->id,
                    'affiliation_type' => $this->resolveAffiliationType($user),
                    'is_primary'       => $isPrimary,
                ]);

                if ($isPrimary) {
                    $user->forceFill(['department' => $department->name])->save();
                }
            });

            $attached++;
        }

        AuditLogger::log(
            event: 'organization.management',
            action: 'department_affiliation_attached',
            request: $request,
            userId: $request->user()?->id,
            targetType: Department::class,
            targetId: $department->id,
            targetLabel: $department->name,
            metadata: [
                'attached_count' => $attached,
                'skipped'        => $skipped,
                'is_primary'     => $isPrimary,
            ]
        );

        $message = "Attached {$attached} user(s) to department.";
        if (count($skipped) > 0) {
            $message .= ' Skipped: ' . implode(', ', $skipped);
        }

        return redirect()->route('departments.index')->with(
            $attached > 0 ? 'success' : 'error',
            $message
        );
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
            'supportUnit' => null,
            'users' => $this->userOptions(),
            'primaryHint' => 'If set as primary, the user\'s previous primary affiliation will be replaced.',
        ]);
    }

    public function createForSupportUnit(SupportUnit $supportUnit): View
    {
        return view('user-affiliations.create', [
            'pageTitle' => 'Attach User to Support Unit',
            'pageDescription' => 'Create a support unit affiliation for existing users.',
            'submitUrl' => route('support-units.affiliations.store', $supportUnit),
            'cancelUrl' => route('support-units.index'),
            'department' => null,
            'programStudy' => null,
            'supportUnit' => $supportUnit,
            'users' => $this->userOptions(),
            'primaryHint' => 'If set as primary, the user\'s previous primary affiliation will be replaced.',
        ]);
    }

    public function storeForSupportUnit(Request $request, SupportUnit $supportUnit): RedirectResponse
    {
        $validated = $request->validate([
            'user_ids'   => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'exists:users,id'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $isPrimary = (bool) ($validated['is_primary'] ?? false);
        $users = User::query()->whereIn('id', $validated['user_ids'])->get();
        $attached = 0;
        $skipped = [];

        foreach ($users as $user) {
            $exists = UserAffiliation::query()
                ->where('user_id', $user->id)
                ->where('support_unit_id', $supportUnit->id)
                ->whereNull('program_study_id')
                ->exists();

            if ($exists) {
                $skipped[] = $user->email . ' (already attached)';
                continue;
            }

            DB::transaction(function () use ($supportUnit, $user, $isPrimary) {
                if ($isPrimary) {
                    $user->affiliations()->update(['is_primary' => false]);
                }

                UserAffiliation::create([
                    'user_id' => $user->id,
                    'support_unit_id' => $supportUnit->id,
                    'affiliation_type' => $this->resolveAffiliationType($user),
                    'is_primary' => $isPrimary,
                ]);
            });

            $attached++;
        }

        AuditLogger::log(
            event: 'organization.management',
            action: 'support_unit_affiliation_attached',
            request: $request,
            userId: $request->user()?->id,
            targetType: SupportUnit::class,
            targetId: $supportUnit->id,
            targetLabel: $supportUnit->name,
            metadata: [
                'attached_count' => $attached,
                'skipped' => $skipped,
                'is_primary' => $isPrimary,
            ]
        );

        $message = "Attached {$attached} user(s) to support unit.";
        if (count($skipped) > 0) {
            $message .= ' Skipped: ' . implode(', ', $skipped);
        }

        return redirect()->route('support-units.index')->with(
            $attached > 0 ? 'success' : 'error',
            $message
        );
    }

    public function storeForProgramStudy(Request $request, ProgramStudy $programStudy): RedirectResponse
    {
        $validated = $request->validate([
            'user_ids'   => ['required', 'array', 'min:1'],
            'user_ids.*' => ['required', 'exists:users,id'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $programStudy->loadMissing('department');
        $isPrimary = (bool) ($validated['is_primary'] ?? false);
        $users = User::query()->whereIn('id', $validated['user_ids'])->get();
        $attached = 0;
        $skipped = [];

        foreach ($users as $user) {
            $exists = UserAffiliation::query()
                ->where('user_id', $user->id)
                ->where('program_study_id', $programStudy->id)
                ->exists();

            if ($exists) {
                $skipped[] = $user->email . ' (already attached)';
                continue;
            }

            DB::transaction(function () use ($programStudy, $user, $isPrimary) {
                if ($isPrimary) {
                    $user->affiliations()->update(['is_primary' => false]);
                }

                UserAffiliation::create([
                    'user_id'          => $user->id,
                    'department_id'    => $programStudy->department_id,
                    'program_study_id' => $programStudy->id,
                    'affiliation_type' => $this->resolveAffiliationType($user),
                    'is_primary'       => $isPrimary,
                ]);

                if ($isPrimary) {
                    $user->forceFill([
                        'department' => $programStudy->department?->name,
                    ])->save();
                }
            });

            $attached++;
        }

        AuditLogger::log(
            event: 'organization.management',
            action: 'program_study_affiliation_attached',
            request: $request,
            userId: $request->user()?->id,
            targetType: ProgramStudy::class,
            targetId: $programStudy->id,
            targetLabel: $programStudy->name,
            metadata: [
                'attached_count' => $attached,
                'skipped'        => $skipped,
                'department_id'  => $programStudy->department_id,
                'is_primary'     => $isPrimary,
            ]
        );

        $message = "Attached {$attached} user(s) to program study.";
        if (count($skipped) > 0) {
            $message .= ' Skipped: ' . implode(', ', $skipped);
        }

        return redirect()->route('program-studies.index')->with(
            $attached > 0 ? 'success' : 'error',
            $message
        );
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