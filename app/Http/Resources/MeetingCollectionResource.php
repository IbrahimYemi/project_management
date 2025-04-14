<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetingCollectionResource extends JsonResource
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
            'agenda' => $this->agenda,
            'date' => $this->date,
            'time' => $this->time,
            'link' => $this->link,
            'projectName' => $this->project?->name,
            'teamName' => $this->team?->name,
            'taskName' => $this->task?->name,
            'team_id' => $this->team_id,
            'project_id' => $this->project_id,
            'task_id' => $this->task_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
