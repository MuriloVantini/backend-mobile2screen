<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlertDeliveryResource extends JsonResource
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
            'alert_id' => $this->alert_id,
            'device_id' => $this->device_id,
            'status' => $this->status,
            'delivered_at' => $this->delivered_at,
            'acknowledged_at' => $this->acknowledged_at,
            'dismissed_at' => $this->dismissed_at,
            'error_message' => $this->error_message,
            'retry_count' => $this->retry_count,
            'created_at' => $this->created_at,
            'alert' => new AlertResource($this->whenLoaded('alert')),
            'device' => new DeviceResource($this->whenLoaded('device')),
        ];
    }
}
