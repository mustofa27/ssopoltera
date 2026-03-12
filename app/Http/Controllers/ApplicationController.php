<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Role;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $applications = Application::query()
            ->withCount('roles')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('client_id', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('applications.index', compact('applications', 'search'));
    }

    public function create(): View
    {
        $roles = Role::orderBy('name')->get(['id', 'name', 'slug']);

        return view('applications.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:applications,slug'],
            'description' => ['nullable', 'string'],
            'redirect_uri' => ['required', 'url', 'max:255'],
            'logout_uri' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'allowed_scopes' => ['nullable', 'string'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $application = Application::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
            'redirect_uri' => $validated['redirect_uri'],
            'logout_uri' => $validated['logout_uri'] ?? null,
            'logo' => $validated['logo'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'allowed_scopes' => $this->normalizeScopes($validated['allowed_scopes'] ?? null),
        ]);

        $application->roles()->sync($validated['roles'] ?? []);

        AuditLogger::log(
            event: 'application.management',
            action: 'created',
            request: $request,
            targetType: Application::class,
            targetId: $application->id,
            targetLabel: $application->slug,
            metadata: [
                'redirect_uri' => $application->redirect_uri,
                'logout_uri' => $application->logout_uri,
                'roles' => $application->roles()->pluck('slug')->all(),
            ]
        );

        return redirect()->route('applications.view', $application)->with('success', 'Application created successfully.');
    }

    public function view(Application $application): View
    {
        $application->load('roles:id,name,slug', 'roles.users:id,name,email');

        $effectiveUsers = $application->accessibleUsers();

        return view('applications.view', compact('application', 'effectiveUsers'));
    }

    public function edit(Application $application): View
    {
        $roles = Role::orderBy('name')->get(['id', 'name', 'slug']);
        $application->load('roles:id');

        return view('applications.edit', compact('application', 'roles'));
    }

    public function update(Request $request, Application $application): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:applications,slug,' . $application->id],
            'description' => ['nullable', 'string'],
            'redirect_uri' => ['required', 'url', 'max:255'],
            'logout_uri' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'allowed_scopes' => ['nullable', 'string'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $application->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'redirect_uri' => $validated['redirect_uri'],
            'logout_uri' => $validated['logout_uri'] ?? null,
            'logo' => $validated['logo'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'allowed_scopes' => $this->normalizeScopes($validated['allowed_scopes'] ?? null),
        ]);

        $application->roles()->sync($validated['roles'] ?? []);

        AuditLogger::log(
            event: 'application.management',
            action: 'updated',
            request: $request,
            targetType: Application::class,
            targetId: $application->id,
            targetLabel: $application->slug,
            metadata: [
                'redirect_uri' => $application->redirect_uri,
                'logout_uri' => $application->logout_uri,
                'is_active' => $application->is_active,
            ]
        );

        return redirect()->route('applications.index')->with('success', 'Application updated successfully.');
    }

    public function regenerateSecret(Application $application): RedirectResponse
    {
        $application->update([
            'client_secret' => Str::random(64),
        ]);

        AuditLogger::log(
            event: 'application.management',
            action: 'secret_regenerated',
            request: request(),
            targetType: Application::class,
            targetId: $application->id,
            targetLabel: $application->slug
        );

        return redirect()
            ->back()
            ->with('success', 'Client secret regenerated successfully. Make sure the consuming application updates its configuration immediately.');
    }

    public function destroy(Application $application): RedirectResponse
    {
        $label = $application->slug;

        $application->roles()->detach();
        $application->delete();

        AuditLogger::log(
            event: 'application.management',
            action: 'deleted',
            request: request(),
            targetType: Application::class,
            targetId: $application->id,
            targetLabel: $label
        );

        return redirect()->route('applications.index')->with('success', 'Application deleted successfully.');
    }

    /**
     * @return array<int, string>
     */
    private function normalizeScopes(?string $scopes): array
    {
        if (! $scopes) {
            return [];
        }

        return collect(explode(',', $scopes))
            ->map(fn (string $scope) => trim($scope))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
