<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PriorityStatus;

class PriorityStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $priorities = [
            'low',
            'normal',
            'medium',
            'high',
            'urgent',
            'daily',
            'default',
        ];

        foreach ($priorities as $priority) {
            PriorityStatus::updateOrCreate(
                ['name' => $priority],
                ['name' => $priority]
            );
        }
    }
}
