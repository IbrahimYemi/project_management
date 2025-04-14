<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        $roles = [
            'Super Admin' => 'superadmin@example.com',
            'Admin' => 'admin@example.com',
            'Team Lead' => 'teamlead@example.com',
            'Member' => 'member@example.com',
        ];

        foreach ($roles as $roleName => $email) {
            $role = Role::firstOrCreate(['name' => $roleName]);

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $roleName,
                    'password' => Hash::make('password@' . strtoupper(str_replace(' ', '_', $roleName))),
                    'email_verified_at' => now(),
                ]
            );
            
            $user->assignRole($role);
        }

        // Insert 200 users as "Member"
        $memberRole = Role::firstOrCreate(['name' => 'Member']);
        $bulkUsers = [];

        for ($i = 0; $i < 20; $i++) {
            $bulkUsers[] = [
                'id' => $faker->uuid,
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'password' => Hash::make('password@MEMBER'),
                'email_verified_at' => now(),
                'avatar' => "https://i.pravatar.cc/150?img=" . (($i % 70) + 1),
            ];
        }

        // Insert users in bulk
        User::insert($bulkUsers);

        // Assign the "Member" role to all 200 users
        $members = User::whereIn('email', array_column($bulkUsers, 'email'))->get();
        foreach ($members as $member) {
            $member->assignRole($memberRole);
        }
    }
}
