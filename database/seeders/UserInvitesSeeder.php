<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class UserInvitesSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();
        $userInvites = [];

        for ($i = 1; $i <= 18; $i++) {
            $userInvites[] = [
                'id'          => $faker->uuid,
                'email'       => $faker->unique()->safeEmail,
                'name'        => $faker->name(),
                'token'       => strtoupper(Str::random(8)),
                'is_accepted' => (bool) rand(0, 1),
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        }

        DB::table('user_invites')->insert($userInvites);
    }
}
