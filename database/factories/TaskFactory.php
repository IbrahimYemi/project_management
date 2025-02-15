<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectTaskStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'status_id' => ProjectTaskStatus::factory(),
            'project_id' => Project::factory(),
            'assigned_to' => User::factory(),
        ];
    }
}
