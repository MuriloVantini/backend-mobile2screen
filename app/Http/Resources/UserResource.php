<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
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
            'email' => $this->email,
            'company' => $this->company,
            'phone' => $this->phone,
            'profile_image_url' => $this->profile_image_path
                ? Storage::disk('public')->url($this->profile_image_path)
                : null,
            'plan_id' => $this->plan_id,
            'status' => $this->status,
            'role' => $this->role,
            'last_active' => $this->last_active,
            'joined_at' => $this->joined_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'plan' => new PlanResource($this->whenLoaded('plan')),
            'settings' => new UserSettingResource($this->whenLoaded('settings')),
            'devices_count' => $this->whenCounted('devices'),
            'alerts_count' => $this->whenCounted('alerts'),
            'activity_logs_count' => $this->whenCounted('activityLogs'),
            'delivery_rate' => $this->when(
                isset($this->deliveries_count, $this->received_deliveries_count),
                fn () => $this->deliveries_count > 0
                    ? round(($this->received_deliveries_count / $this->deliveries_count) * 100, 2)
                    : 0,
            ),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
        ];
    }
}
