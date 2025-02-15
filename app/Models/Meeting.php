<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Meeting extends Model
{
    use HasFactory;
    
    protected $fillable = ['agenda', 'link', 'project_id', 'team_id', 'task_id', 'date', 'time'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}

