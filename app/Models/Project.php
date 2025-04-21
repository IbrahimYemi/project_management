<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;

class Project extends Model
{
    use HasFactory, HasUuids;
    
    protected $fillable = ['name', 'description', 'team_id', 'status_id', 'is_completed'];

    protected $preventLazyLoading = true;

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ProjectStatus::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function taskStatuses(): HasMany
    {
        return $this->hasMany(ProjectTaskStatus::class);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function markAsCompleted()
    {
        $this->is_completed = true;
        $statusId = ProjectStatus::where('name', 'Completed')->first()?->id;
        if ($statusId) {
            $this->status_id = $statusId;
        }
        $this->save();
    }
    
    // scope to return projects that are active
    public function scopeIsActive($query)
    {
        return $query->where('is_completed', false);
    }

    /**
     * Summary of scopeAccessible
     * @param mixed $query
     * @param \App\Models\User|\Illuminate\Contracts\Auth\Authenticatable|null $user
     */
    public function scopeAccessible($query, User | Auth $user = null)
    {
        if ($user === null) {
            $user = Auth::user();
        }
        // If no user is authenticated, return an empty query
        if ($user === null) {
            return $query->whereRaw('1 = 0'); // No results
        }

        // Check if the user is an admin or super admin
        if ($user->hasAnyAppRole(['Super Admin', 'Admin'])) {
            return $query;
        }

        // Team Members - Projects where user is a member
        return $query->whereHas('team.members', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }

    public function getProgressPercentage()
    {
        // Get all tasks for this project
        $tasks = $this->tasks;

        // If no tasks, progress is 0
        if ($tasks->isEmpty()) {
            return 0;
        }

        // Sum the percentages from each task's status
        $totalPercentage = $tasks->sum(function ($task) {
            return $task->status?->percentage ?? 0;
        });

        // Each task contributes a max of 100, so total possible = count × 100
        $maxPercentage = count($tasks) * 100;

        // Normalize to get value between 0-100
        return round(($totalPercentage / $maxPercentage) * 100);
    }
    
}

