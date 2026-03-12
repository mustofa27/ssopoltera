<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller
{
    /**
     * @return array<int, string>
     */
    private function availablePermissions(): array
    {
        return [
            'manage_users',
            'manage_roles',
            'manage_applications',
            'manage_sessions',
            'view_audit_logs',
            'manage_settings',
            'view_profile',
            'update_profile',
        ];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $roles = Role::query()
            ->withCount('users')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->orderBy('is_system', 'desc')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('roles.index', compact('roles', 'search'));
    }

    public function create(): View
    {
        $permissions = $this->availablePermissions();

        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:roles,slug'],
            'description' => ['nullable', 'string'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'permissions' => array_values($validated['permissions'] ?? []),
            'is_system' => false,
        ]);

        AuditLogger::log(
            event: 'role.management',
            action: 'created',
            request: $request,
            targetType: Role::class,
            targetId: $role->id,
            targetLabel: $role->slug,
            metadata: ['permissions' => $role->permissions]
        );

        return redirect()->route('roles.edit', $role)->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): View
    {
        $permissions = $this->availablePermissions();

        return view('roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $role->id],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:roles,slug,' . $role->id],
            'description' => ['nullable', 'string'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $role->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'permissions' => array_values($validated['permissions'] ?? []),
        ]);

        AuditLogger::log(
            event: 'role.management',
            action: 'updated',
            request: $request,
            targetType: Role::class,
            targetId: $role->id,
            targetLabel: $role->slug,
            metadata: ['permissions' => $role->permissions]
        );

        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_system) {
            AuditLogger::log(
                event: 'role.management',
                action: 'delete_blocked',
                request: request(),
                targetType: Role::class,
                targetId: $role->id,
                targetLabel: $role->slug,
                metadata: ['reason' => 'system_role']
            );

            return redirect()->route('roles.index')->with('error', 'System roles cannot be deleted.');
        }

        $roleLabel = $role->slug;

        $role->users()->detach();
        $role->delete();

        AuditLogger::log(
            event: 'role.management',
            action: 'deleted',
            request: request(),
            targetType: Role::class,
            targetId: $role->id,
            targetLabel: $roleLabel
        );

        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
    }
}
