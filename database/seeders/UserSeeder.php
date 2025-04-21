<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
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
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $roleName,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'app_role' => $roleName,
                ]
            );
        }

        // Insert users as "Member"
        $bulkUsers = [];

        for ($i = 0; $i < 5; $i++) {
            $bulkUsers[] = [
                'id' => $faker->uuid,
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'avatar' => "https://i.pravatar.cc/150?img=" . (($i % 70) + 1),
            ];
        }

        // Insert users in bulk
        User::insert($bulkUsers);
    }
}
