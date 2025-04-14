<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory, HasUuids;
    
    protected $fillable = ['name', 'description', 'team_id', 'status_id', 'is_completed'];

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

    public function getProgressPercentage()
    {
        // Get all tasks for this project
        $tasks = $this->tasks()->with('status')->get();

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

