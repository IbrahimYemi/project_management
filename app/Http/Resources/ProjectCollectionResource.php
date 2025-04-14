<?php

namespace App\Http\Resources;

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
            'project' => AllProjectResource::make($this),
            'team' => [
                'id' => $this->team?->id,
                'name' => $this->team?->name,
                'teamLead' => $this->team?->teamLead?->name ?? null,
                'members' => $this->team?->members->count(),
                'projectCount' => $this->team?->projects->count(),
                'teamLeadId' => $this->team?->teamLead?->id ?? null,
            ],
            'tasks' => TaskCollectionResource::collection($this->tasks),
            
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

            'meetings' => $this->meetings->map(function ($meeting) {
                return [
                    'id' => $meeting->id,
                    'title' => $meeting->title,
                    'description' => $meeting->description,
                    'start_time' => $meeting->start_time,
                    'end_time' => $meeting->end_time,
                    'participants' => $meeting->participants,
                    'created_at' => $meeting->created_at->toISOString(),
                ];
            })->toArray(),
        ];
    }
}
