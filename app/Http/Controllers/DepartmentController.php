<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $departments = Department::query()
            ->with('head:id,name,email')
            ->withCount(['programStudies', 'userAffiliations'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('departments.index', compact('departments', 'search'));
    }

    public function create(): View
    {
        $headCandidates = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('departments.create', compact('headCandidates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:departments,code'],
            'name' => ['required', 'string', 'max:255'],
            'head_user_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Department::create([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'head_user_id' => $validated['head_user_id'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        $department = Department::where('code', strtoupper($validated['code']))->first();

        AuditLogger::log(
            event: 'organization.management',
            action: 'department_created',
            request: $request,
            targetType: Department::class,
            targetId: $department?->id,
            targetLabel: $department?->name
        );

        return redirect()->route('departments.index')->with('success', 'Department created successfully.');
    }

    public function edit(Department $department): View
    {
        $headCandidates = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('departments.edit', compact('department', 'headCandidates'));
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:departments,code,' . $department->id],
            'name' => ['required', 'string', 'max:255'],
            'head_user_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $department->update([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'head_user_id' => $validated['head_user_id'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        AuditLogger::log(
            event: 'organization.management',
            action: 'department_updated',
            request: $request,
            targetType: Department::class,
            targetId: $department->id,
            targetLabel: $department->name
        );

        return redirect()->route('departments.index')->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->programStudies()->exists() || $department->userAffiliations()->exists()) {
            AuditLogger::log(
                event: 'organization.management',
                action: 'department_delete_blocked',
                request: request(),
                targetType: Department::class,
                targetId: $department->id,
                targetLabel: $department->name,
                metadata: ['reason' => 'in_use']
            );

            return redirect()->route('departments.index')->with('error', 'Department cannot be deleted because it is still in use.');
        }

        $label = $department->name;

        $department->delete();

        AuditLogger::log(
            event: 'organization.management',
            action: 'department_deleted',
            request: request(),
            targetType: Department::class,
            targetId: $department->id,
            targetLabel: $label
        );

        return redirect()->route('departments.index')->with('success', 'Department deleted successfully.');
    }
}
