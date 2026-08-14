<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlertResource extends JsonResource
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
            'user_id' => $this->user_id,
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'duration_seconds' => $this->duration_seconds,
            'priority' => $this->priority,
            'sent_at' => $this->sent_at,
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'devices_count' => $this->whenCounted('deliveries'),
            'received_devices_count' => $this->whenHas('received_devices_count'),
            'failed_devices_count' => $this->whenHas('failed_devices_count'),
            'pending_devices_count' => $this->whenHas('pending_devices_count'),
            'user' => new UserResource($this->whenLoaded('user')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'deliveries' => AlertDeliveryResource::collection($this->whenLoaded('deliveries')),
        ];
    }
}
