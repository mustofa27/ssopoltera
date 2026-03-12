<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class EmergencyAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@poltera.ac.id'],
            [
                'name' => 'Emergency Admin',
                'password' => bcrypt('SSOAdmin@2026!'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $superAdminRole = Role::where('slug', 'super-admin')->first();

        if ($superAdminRole && ! $user->roles()->where('roles.id', $superAdminRole->id)->exists()) {
            $user->roles()->attach($superAdminRole->id);
        }
    }
}
