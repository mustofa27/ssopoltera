<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LecturerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var Application $application */
        $application = $request->attributes->get('application');

        $perPage = (int) $request->query('per_page', 25);
        $perPage = max(1, min($perPage, 100));

        $search = trim((string) $request->query('q', ''));

        $lecturers = User::query()
            ->where('user_type', 'employee')
            ->where('employee_type', 'lecturer')
            ->with(['primaryAffiliation.department', 'primaryAffiliation.programStudy'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        AuditLogger::log(
            event: 'api.lecturers',
            action: 'listed',
            request: $request,
            targetType: Application::class,
            targetId: $application->id,
            targetLabel: $application->slug,
            metadata: ['page' => $lecturers->currentPage(), 'per_page' => $perPage]
        );

        return response()->json([
            'data' => $lecturers->getCollection()->map(fn (User $user): array => [
                'id' => $user->id,
                'nip' => $user->nip,
                'name' => $user->name,
                'email' => $user->email,
                'job_title' => $user->job_title,
                'is_active' => $user->is_active,
                'department' => optional(optional($user->primaryAffiliation)->department)->name,
                'program_study' => optional(optional($user->primaryAffiliation)->programStudy)->name,
            ]),
            'meta' => [
                'current_page' => $lecturers->currentPage(),
                'per_page' => $lecturers->perPage(),
                'total' => $lecturers->total(),
                'last_page' => $lecturers->lastPage(),
            ],
        ]);
    }
}
