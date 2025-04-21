<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Team;

class TeamPolicy
{
    // Only Admins can create teams
    public function create(User $user)
    {
        return $user->hasAnyAppRole(['Super Admin', 'Admin']);
    }

    // Admins and Team Leads can view their own teams
    public function view(User $user, Team $team)
    {
        return $user->hasAnyAppRole(['Super Admin', 'Admin']) || $team->team_lead_id === $user->id;
    }

    // Only Admins can update or delete teams
    public function update(User $user, Team $team)
    {
        return $user->hasAnyAppRole(['Super Admin', 'Admin']);
    }

    public function delete(User $user, Team $team)
    {
        return $user->hasAnyAppRole(['Super Admin', 'Admin']);
    }

    public function member(User $user, Team $team)
    {
        return $user->hasAnyAppRole(['Admin', 'Super Admin']) || $team->members->contains($user);
    }

    public function add(User $user)
    {
        return $user->hasAnyAppRole(['Super Admin', 'Admin']);
    }

    public function remove(User $user)
    {
        return $user->hasAnyAppRole(['Super Admin', 'Admin']);
    }
}
