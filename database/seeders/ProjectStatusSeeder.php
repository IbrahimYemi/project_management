<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProjectStatus;

class ProjectStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['name' => 'Completed', 'percentage' => 100],
            ['name' => 'In Review', 'percentage' => 70],
            ['name' => 'In Progress', 'percentage' => 40],
            ['name' => 'On Hold', 'percentage' => 20],
            ['name' => 'Not Started', 'percentage' => 0],
        ];

        foreach ($statuses as $status) {
            ProjectStatus::updateOrCreate(
                ['name' => $status['name']],
                ['percentage' => $status['percentage']]
            );
        }
    }
}
