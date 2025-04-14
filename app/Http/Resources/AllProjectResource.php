<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AllProjectResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'task_count' => $this->tasks->count(),
            'members_count' => $this->team->members->count(),
            'team_name' => $this->team->name,
            'percentage' => $this->getProgressPercentage() ?? 0,
            'is_completed' => $this->is_completed ?? false,
            'created_at' => $this->created_at->toISOString(),
            'task_status' => $this->taskStatuses ?? [],
            'team_id' => $this->team->id ?? null,
            'status_id' => $this->status_id ?? null,
            'status' => $this->status ?? null,
        ];
    }
}
