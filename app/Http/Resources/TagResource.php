<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagResource extends JsonResource
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
            'color' => $this->color,
            'created_at' => $this->created_at,
            'devices_count' => $this->whenCounted('devices'),
            'alerts_count' => $this->whenCounted('alerts'),
            'user' => new UserResource($this->whenLoaded('user')),
            'devices' => DeviceResource::collection($this->whenLoaded('devices')),
            'alerts' => AlertResource::collection($this->whenLoaded('alerts')),
        ];
    }
}
