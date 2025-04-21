<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'login_token',
        'login_token_requested_at',
        'avatar',
        'is_active'
    ];

    protected $appends = ['role', 'isActive'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'login_token_requested_at' => 'datetime',
        ];
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members');
    }

    public function projects(): HasManyThrough
    {
        return $this->hasManyThrough(
            Project::class,
            Team::class,
            'id',           // Foreign key on the Project table (team_id)
            'id',           // Local key on the User model (id)
            'id',           // Local key on the User model (id)
            'team_lead_id'  // Foreign key on the Team table (user id for lead)
        );
    }

    /**
     * Generate a new login token.
     */
    public function generateLoginToken(): string
    {
        $token = strtoupper(bin2hex(random_bytes(4)));
        $this->update([
            'login_token' => $token,
            'login_token_requested_at' => now()
        ]);

        return $token;
    }

    /**
     * Clear the login token after successful login.
     */
    public function clearLoginToken(): void
    {
        $this->update(['login_token' => null]);
    }

    // Accessor for Role
    public function getRoleAttribute(): string
    {
        return $this->app_role; 
    }

    // Convert is_active to isActive for JS compatibility
    public function getIsActiveAttribute()
    {
        return (bool) $this->attributes['is_active'];
    }

    // function to check for posible user app_role
    public function hasAnyAppRole(array | string $roles): bool
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }
        if (empty($roles)) {
            return false;
        }
        foreach ($roles as $role) {
            if ($this->app_role === $role) {
                return true;
            }
        }
        return false;
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

}
