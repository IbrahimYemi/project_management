<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeamCollectionResource extends JsonResource
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
            'teamLead' => $this->teamLead?->name ?? null,
            'teamLeadId' => $this->teamLead?->id ?? null,
            'projectCount' => $this->projects->count(),
            'members' => $this->members->count(),
            'team_members' => $this->members->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'avatar' => $member->avatar,
                    'role' => $member->name == $this->teamLead?->name ? 'Team Lead' : 'Member',
                    'isActive' => $member->is_active,
                ];
            }),
            'project_on' => $this->projects->map(function ($project) {
                return [
                    'id' => $project->id,
                ];
            }),
        ];
    }
}
