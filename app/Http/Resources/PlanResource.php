<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
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
            'max_devices' => $this->max_devices,
            'max_alerts_per_month' => $this->max_alerts_per_month,
            'features' => $this->features,
            'price' => $this->price,
            'created_at' => $this->created_at,
        ];
    }
}
