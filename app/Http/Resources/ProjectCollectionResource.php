<?php

namespace App\Http\Resources;

use App\Models\PriorityStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectCollectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'project' => [
                'id' => $this->id,
                'name' => $this->name,
                'description' => $this->description,
                'task_count' => $this->tasks_count,
                'members_count' => $this->team->members_count,
                'team_name' => $this->team->name,
                'percentage' => $this->getProgressPercentage() ?? 0,
                'is_completed' => $this->is_completed ?? false,
                'created_at' => $this->created_at->toISOString(),
                'task_status' => $this->taskStatuses ?? [],
                'team_id' => $this->team->id ?? null,
                'status_id' => $this->status_id ?? null,
                'status' => $this->status ?? null,
            ],

            'tasks' => $this->tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'name' => $task->title,
                    'description' => $task->description,
                    'status' => [
                        'id' => $task->status?->id,
                        'name' => $task->status?->name,
                    ],
                    'percentage' => $task->status?->percentage,
                    'project' => [
                        'id' => $this->id,
                        'name' => $this->name,
                    ],
                    'owner' => [
                        'id' => $task->assignedUser?->id,
                        'name' => $task->assignedUser?->name,
                        'email' => $task->assignedUser?->email,
                        'avatar' => $task->assignedUser?->avatar,
                        'app_role' => $task->assignedUser?->app_role,
                    ],
                    'startDate' => $task->created_at->format('F j, Y') . ' (' . $task->created_at->diffForHumans() . ')',
                    'dueDate' => optional($task->due_date)?->format('F j, Y') . ' (' . optional($task->due_date)?->diffForHumans() . ')',
                    'isCompleted' => $task->is_completed,
                    'priority' => $task->priority?->name,
                ];
            }),

            'team' => [
                'id' => $this->team?->id,
                'name' => $this->team?->name,
                'teamLead' => $this->team?->teamLead?->name ?? null,
                'members' => $this->team?->members_count,
                'projectCount' => $this->team?->projects_count,
                'teamLeadId' => $this->team?->teamLead?->id ?? null,
            ],
            
            'members' => $this->team->members->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'avatar' => $member->avatar,
                    'role' => $member->id == $this->team->teamLead?->id ? 'Team Lead' : 'Member',
                    'isActive' => $member->is_active,
                ];
            }),

            'priorities' => PriorityStatus::query()->orderBy('name')->get()->toArray(),
        ];
    }
}
