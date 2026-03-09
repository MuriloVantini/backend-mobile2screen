<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebhookResource extends JsonResource
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
            'name' => $this->name,
            'url' => $this->url,
            'secret' => $this->secret,
            'events' => $this->events,
            'is_active' => $this->is_active,
            'last_triggered' => $this->last_triggered,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'logs_count' => $this->whenCounted('logs'),
            'user' => new UserResource($this->whenLoaded('user')),
            'logs' => WebhookLogResource::collection($this->whenLoaded('logs')),
        ];
    }
}
