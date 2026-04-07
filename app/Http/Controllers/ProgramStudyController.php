<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\ProgramStudy;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramStudyController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $programStudies = ProgramStudy::query()
            ->with(['department', 'head:id,name,email'])
            ->withCount('userAffiliations')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('academic_degree', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('program-studies.index', compact('programStudies', 'search'));
    }

    public function create(): View
    {
        $departments = Department::orderBy('name')->get();

        return view('program-studies.create', compact('departments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'code' => ['required', 'string', 'max:50', 'unique:program_studies,code'],
            'name' => ['required', 'string', 'max:255'],
            'head_user_id' => ['nullable', 'exists:users,id'],
            'academic_degree' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $programStudy = ProgramStudy::create([
            'department_id' => $validated['department_id'],
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'head_user_id' => $validated['head_user_id'] ?? null,
            'academic_degree' => $validated['academic_degree'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        AuditLogger::log(
            event: 'organization.management',
            action: 'program_study_created',
            request: $request,
            targetType: ProgramStudy::class,
            targetId: $programStudy->id,
            targetLabel: $programStudy->name
        );

        return redirect()->route('program-studies.index')->with('success', 'Program study created successfully.');
    }

    public function edit(ProgramStudy $programStudy): View
    {
        $departments = Department::orderBy('name')->get();
        $programStudy->load('head:id,name,email');

        return view('program-studies.edit', compact('programStudy', 'departments'));
    }

    public function update(Request $request, ProgramStudy $programStudy): RedirectResponse
    {
        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'code' => ['required', 'string', 'max:50', 'unique:program_studies,code,' . $programStudy->id],
            'name' => ['required', 'string', 'max:255'],
            'head_user_id' => ['nullable', 'exists:users,id'],
            'academic_degree' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $programStudy->update([
            'department_id' => $validated['department_id'],
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'head_user_id' => $validated['head_user_id'] ?? null,
            'academic_degree' => $validated['academic_degree'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        AuditLogger::log(
            event: 'organization.management',
            action: 'program_study_updated',
            request: $request,
            targetType: ProgramStudy::class,
            targetId: $programStudy->id,
            targetLabel: $programStudy->name
        );

        return redirect()->route('program-studies.index')->with('success', 'Program study updated successfully.');
    }

    public function destroy(ProgramStudy $programStudy): RedirectResponse
    {
        if ($programStudy->userAffiliations()->exists()) {
            AuditLogger::log(
                event: 'organization.management',
                action: 'program_study_delete_blocked',
                request: request(),
                targetType: ProgramStudy::class,
                targetId: $programStudy->id,
                targetLabel: $programStudy->name,
                metadata: ['reason' => 'in_use']
            );

            return redirect()->route('program-studies.index')->with('error', 'Program study cannot be deleted because it is still in use.');
        }

        $label = $programStudy->name;

        $programStudy->delete();

        AuditLogger::log(
            event: 'organization.management',
            action: 'program_study_deleted',
            request: request(),
            targetType: ProgramStudy::class,
            targetId: $programStudy->id,
            targetLabel: $label
        );

        return redirect()->route('program-studies.index')->with('success', 'Program study deleted successfully.');
    }
}
