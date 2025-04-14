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
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'percentage' => $this->status?->percentage,
            'project' => [
                'id' => $this->project?->id,
                'name' => $this->project?->name,
                'description' => $this->project?->description,
                'task_count' => $this->project?->tasks->count(),
                'members_count' => $this->project?->team->members->count(),
                'team_name' => $this->project?->team->name,
                'percentage' => $this->project?->progress_percentage ?? 0,
                'is_completed' => $this->project?->is_completed ?? false,
                'created_at' => $this->project?->created_at->toISOString(),
                'task_status' => $this->project?->task_status ?? [],
            ],
            'owner' => $this->assignedUser,
            'startDate' => $this->start_date->toISOString(),
            'dueDate' => $this->due_date->toISOString(),
            'createdDate' => $this->created_at->toISOString(),
            'updatedDate' => $this->updated_at->toISOString(),
            'isCompleted' => $this->is_completed,
            'priority' => $this->priority?->name,
            'taskImage' => $this->task_image,
            'taskDiscussionCount' => $this->discussions?->count(),
            'discussions' => $this->discussions->map(function ($discussion) {
                return [
                    'id' => $discussion->id,
                    'message' => $discussion->content,
                    'user' => $discussion->user,
                    'created_at' => $discussion->created_at->toISOString(),
                    'updated_at' => $discussion->updated_at->toISOString(),
                ];
            }),
            'taskFileCount' => $this->attachments?->count(),
            'files' => $this->attachments?->map(function ($file) {
                return [
                    'id' => $file->id,
                    'name' => $file->name,
                    'url' => $file->url,
                    'type' => $file->type,
                    'user' => $file->user,
                    'created_at' => $file->created_at->toISOString(),
                ];
            }),
        ];
    }
}
