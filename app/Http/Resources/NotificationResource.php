<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'message' => $this->data['message'] ?? '',
            'type' => $this->data['type'] ?? 'general',
            'dataId' => $this->data['dataId'] ?? null,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
