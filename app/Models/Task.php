<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Task extends Model
{
    use HasFactory, HasUuids;
    
    protected $fillable = ['title', 'description', 'status_id', 'project_id', 'assigned_to', 'is_completed', 'priority_id', 'task_image', 'start_date', 'due_date'];

    protected $casts = [
        'is_completed' => 'boolean',
        'start_date' => 'datetime',
        'due_date' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ProjectTaskStatus::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }

    public function markAsCompleted()
    {
        $this->is_completed = true;
        $this->save();
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(PriorityStatus::class);
    }
}

