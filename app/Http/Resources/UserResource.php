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
        ];
    }
}
