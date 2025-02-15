<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attachment extends Model
{
    use HasFactory;
    
    protected $fillable = ['task_id', 'file_url', 'link_url'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
