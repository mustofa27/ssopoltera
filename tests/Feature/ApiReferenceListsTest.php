<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Department;
use App\Models\ProgramStudy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiReferenceListsTest extends TestCase
{
    use RefreshDatabase;

    protected function makeApplication(): Application
    {
        return Application::create([
            'name' => 'Test API Client',
            'slug' => 'test-api-client',
            'client_id' => '11111111-1111-1111-1111-111111111111',
            'client_secret' => 'super-secret-client-key',
            'is_active' => true,
        ]);
    }

    public function test_reference_list_endpoints_require_valid_application_credentials(): void
    {
        $application = $this->makeApplication();

        $department = Department::create([
            'code' => 'IT',
            'name' => 'Information Technology',
            'is_active' => true,
        ]);

        ProgramStudy::create([
            'department_id' => $department->id,
            'code' => 'TI',
            'name' => 'Informatics Engineering',
            'academic_degree' => 'S1',
            'is_active' => true,
        ]);

        $this->withHeaders([
            'X-Client-Id' => $application->client_id,
            'X-Client-Secret' => 'wrong-secret',
        ])->getJson('/api/departments')
            ->assertStatus(401)
            ->assertJsonPath('error', 'invalid_client');

        $this->withHeaders([
            'X-Client-Id' => $application->client_id,
            'X-Client-Secret' => $application->client_secret,
        ])->getJson('/api/departments')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Information Technology');

        $this->withHeaders([
            'X-Client-Id' => $application->client_id,
            'X-Client-Secret' => $application->client_secret,
        ])->getJson('/api/program-studies')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Informatics Engineering');
    }
}
