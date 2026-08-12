<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Department;
use App\Models\ProgramStudy;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferenceDataController extends Controller
{
    public function departments(Request $request): JsonResponse
    {
        /** @var Application $application */
        $application = $request->attributes->get('application');

        $perPage = (int) $request->query('per_page', 25);
        $perPage = max(1, min($perPage, 100));

        $search = trim((string) $request->query('q', ''));

        $departments = Department::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        AuditLogger::log(
            event: 'api.departments',
            action: 'listed',
            request: $request,
            targetType: Application::class,
            targetId: $application->id,
            targetLabel: $application->slug,
            metadata: ['page' => $departments->currentPage(), 'per_page' => $perPage]
        );

        return response()->json([
            'data' => $departments->getCollection()->map(fn (Department $department): array => [
                'id' => $department->id,
                'code' => $department->code,
                'name' => $department->name,
                'is_active' => $department->is_active,
            ])->values(),
            'meta' => [
                'current_page' => $departments->currentPage(),
                'per_page' => $departments->perPage(),
                'total' => $departments->total(),
                'last_page' => $departments->lastPage(),
            ],
        ]);
    }

    public function programStudies(Request $request): JsonResponse
    {
        /** @var Application $application */
        $application = $request->attributes->get('application');

        $perPage = (int) $request->query('per_page', 25);
        $perPage = max(1, min($perPage, 100));

        $search = trim((string) $request->query('q', ''));

        $programStudies = ProgramStudy::query()
            ->with('department:id,code,name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('academic_degree', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        AuditLogger::log(
            event: 'api.program_studies',
            action: 'listed',
            request: $request,
            targetType: Application::class,
            targetId: $application->id,
            targetLabel: $application->slug,
            metadata: ['page' => $programStudies->currentPage(), 'per_page' => $perPage]
        );

        return response()->json([
            'data' => $programStudies->getCollection()->map(function (ProgramStudy $programStudy): array {
                return [
                    'id' => $programStudy->id,
                    'department_id' => $programStudy->department_id,
                    'code' => $programStudy->code,
                    'name' => $programStudy->name,
                    'academic_degree' => $programStudy->academic_degree,
                    'is_active' => $programStudy->is_active,
                    'department' => $programStudy->department ? [
                        'id' => $programStudy->department->id,
                        'code' => $programStudy->department->code,
                        'name' => $programStudy->department->name,
                    ] : null,
                ];
            })->values(),
            'meta' => [
                'current_page' => $programStudies->currentPage(),
                'per_page' => $programStudies->perPage(),
                'total' => $programStudies->total(),
                'last_page' => $programStudies->lastPage(),
            ],
        ]);
    }
}
