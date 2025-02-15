<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Models\Task;

class TaskPolicy
{
    // Only Team Leads can create tasks
    public function create(User $user, Project $project)
    {
        return $project->team->team_lead_id === $user->id;
    }

    // All Members can view tasks
    public function view(User $user, Task $task)
    {
        return $task->project->team->members->contains($user);
    }

    // Only Team Leads can update or delete tasks
    public function update(User $user, Task $task)
    {
        return $task->project->team->team_lead_id === $user->id;
    }

    public function delete(User $user, Task $task)
    {
        return $task->project->team->team_lead_id === $user->id;
    }
}
