<?php

namespace App\Http\Controllers;

use App\Models\SupportUnit;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportUnitController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $supportUnits = SupportUnit::query()
            ->with('head:id,name,email')
            ->withCount('userAffiliations')
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

        return view('support-units.index', compact('supportUnits', 'search'));
    }

    public function create(): View
    {
        return view('support-units.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:support_units,code'],
            'name' => ['required', 'string', 'max:255'],
            'head_user_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $supportUnit = SupportUnit::create([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'head_user_id' => $validated['head_user_id'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        AuditLogger::log(
            event: 'organization.management',
            action: 'support_unit_created',
            request: $request,
            targetType: SupportUnit::class,
            targetId: $supportUnit->id,
            targetLabel: $supportUnit->name
        );

        return redirect()->route('support-units.index')->with('success', 'Support unit created successfully.');
    }

    public function edit(SupportUnit $supportUnit): View
    {
        $supportUnit->load('head:id,name,email');

        return view('support-units.edit', compact('supportUnit'));
    }

    public function update(Request $request, SupportUnit $supportUnit): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:support_units,code,' . $supportUnit->id],
            'name' => ['required', 'string', 'max:255'],
            'head_user_id' => ['nullable', 'exists:users,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $supportUnit->update([
            'code' => strtoupper($validated['code']),
            'name' => $validated['name'],
            'head_user_id' => $validated['head_user_id'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        AuditLogger::log(
            event: 'organization.management',
            action: 'support_unit_updated',
            request: $request,
            targetType: SupportUnit::class,
            targetId: $supportUnit->id,
            targetLabel: $supportUnit->name
        );

        return redirect()->route('support-units.index')->with('success', 'Support unit updated successfully.');
    }

    public function destroy(SupportUnit $supportUnit): RedirectResponse
    {
        if ($supportUnit->userAffiliations()->exists()) {
            AuditLogger::log(
                event: 'organization.management',
                action: 'support_unit_delete_blocked',
                request: request(),
                targetType: SupportUnit::class,
                targetId: $supportUnit->id,
                targetLabel: $supportUnit->name,
                metadata: ['reason' => 'in_use']
            );

            return redirect()->route('support-units.index')->with('error', 'Support unit cannot be deleted because it is still in use.');
        }

        $label = $supportUnit->name;

        $supportUnit->delete();

        AuditLogger::log(
            event: 'organization.management',
            action: 'support_unit_deleted',
            request: request(),
            targetType: SupportUnit::class,
            targetId: $supportUnit->id,
            targetLabel: $label
        );

        return redirect()->route('support-units.index')->with('success', 'Support unit deleted successfully.');
    }
}
