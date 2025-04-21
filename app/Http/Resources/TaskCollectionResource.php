<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskCollectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'percentage' => $this->status?->percentage,
            'project' => [
                'id' => $this->project?->id,
                'name' => $this->project?->name,
            ],
            'owner' => $this->assignedUser,
            'startDate' => $this->created_at->format('F j, Y') . ' (' . $this->created_at->diffForHumans() . ')',
            'dueDate' => optional($this->due_date)?->format('F j, Y') . ' (' . optional($this->due_date)?->diffForHumans() . ')',
            'isCompleted' => $this->is_completed,
            'priority' => $this->priority?->name,
        ];
    }
}
