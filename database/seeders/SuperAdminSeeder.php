<?php

namespace Database\Seeders;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run()
    {
        // Ensure the role exists
        // $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdminEmail = env('SUPER_ADMIN_EMAIL');
        $superAdminName = env('SUPER_ADMIN_NAME');

        if ($superAdminEmail) {
            // Create a super admin user
            $user = User::updateOrCreate(
                ['email' => $superAdminEmail],
                [
                    'app_role' => 'Super Admin',
                    'name' => $superAdminName ?? 'Super Admin',
                    'password' => Hash::make('password@SUPER'),
                    'email_verified_at' => now(),
                ]
            );

            // Assign the "Super Admin" role
            // $user->assignRole($superAdminRole);
        }
    }
}
