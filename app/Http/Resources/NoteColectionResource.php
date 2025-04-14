<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteColectionResource extends JsonResource
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
            'title' => $this->title,
            'content' => $this->content,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'owner' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
                'avatar' => $this->user?->avatar,
            ],
            'project' => [
                'id' => $this->project?->id,
                'name' => $this->project?->name,
                'description' => $this->project?->description,
                'status' => $this->project?->status,
                'created_at' => $this->project?->created_at,
                'updated_at' => $this->project?->updated_at,
            ],
        ];
    }
}
