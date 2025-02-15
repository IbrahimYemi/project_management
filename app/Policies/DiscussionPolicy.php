<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Discussion;

class DiscussionPolicy
{
    // Only the creator or Admin can update a discussion
    public function update(User $user, Discussion $discussion)
    {
        return $discussion->user_id === $user->id || $user->hasRole('Admin');
    }

    // Only the creator or Admin can delete a discussion
    public function delete(User $user, Discussion $discussion)
    {
        return $discussion->user_id === $user->id || $user->hasRole('Admin');
    }
}