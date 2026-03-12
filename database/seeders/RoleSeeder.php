<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'description' => 'Full system access with all permissions',
                'is_system' => true,
                'permissions' => [
                    'manage_users',
                    'manage_roles',
                    'manage_applications',
                    'manage_sessions',
                    'view_audit_logs',
                    'manage_settings',
                ],
            ],
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Administrative access to manage users and applications',
                'is_system' => true,
                'permissions' => [
                    'manage_users',
                    'manage_applications',
                    'view_audit_logs',
                ],
            ],
            [
                'name' => 'User',
                'slug' => 'user',
                'description' => 'Standard user with basic access',
                'is_system' => true,
                'permissions' => [
                    'view_profile',
                    'update_profile',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );
        }
    }
}
