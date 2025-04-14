<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserInvite extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'email',
        'name',
        'token',
        'is_accepted',
    ];

    /**
     * Scope to filter only pending invites.
     */
    public function scopePending($query)
    {
        return $query->where('is_accepted', false);
    }

    /**
     * Check if the invite is accepted.
     */
    public function isAccepted(): bool
    {
        return $this->is_accepted;
    }

    /**
     * Mark the invite as accepted.
     */
    public function markAsAccepted(): bool
    {
        return $this->update(['is_accepted' => true]);
    }
}