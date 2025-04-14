<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Project;

class ProjectPolicy
{
    // Only Admins can create projects
    public function create(User $user)
    {
        return $user->hasAnyAppRole(['Super Admin', 'Admin']);
    }

    // Admins, Team Leads, and Team Members can view projects
    public function view(User $user, Project $project)
    {
        return $user->hasAnyAppRole(['Super Admin', 'Admin']) ||
               $project->team->team_lead_id === $user->id ||
               $project->team->members->contains($user);
    }

    // Only Admins can update or delete projects
    public function update(User $user, Project $project)
    {
        return $user->hasAnyAppRole(['Super Admin', 'Admin']) ||
        $project->team->team_lead_id === $user->id;
    }

    public function delete(User $user, Project $project)
    {
        return $user->hasAnyAppRole(['Super Admin', 'Admin']);
    }
}
