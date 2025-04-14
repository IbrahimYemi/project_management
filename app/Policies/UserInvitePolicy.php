<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserInvite;
use Illuminate\Auth\Access\Response;

class UserInvitePolicy
{
    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyAppRole(['Super Admin', 'Admin']);
    }

    public function update(User $user): bool
    {
        return $user->hasAnyAppRole(['Super Admin', 'Admin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user): bool
    {
        return $user->hasAnyAppRole(['Super Admin', 'Admin']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function resend(User $user): bool
    {
        return $user->hasAnyAppRole(['Super Admin', 'Admin']);
    }
}
