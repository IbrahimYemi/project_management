<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProjectStatus extends Model
{
    use HasFactory, HasUuids;
    
    protected $fillable = ['name', 'percentage'];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'status_id');
    }
}

