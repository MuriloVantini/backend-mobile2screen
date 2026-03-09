<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatisticDailyResource extends JsonResource
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
            'date' => $this->date,
            'alerts_sent' => $this->alerts_sent,
            'alerts_delivered' => $this->alerts_delivered,
            'alerts_failed' => $this->alerts_failed,
            'devices_online_avg' => $this->devices_online_avg,
            'delivery_rate' => $this->delivery_rate,
            'created_at' => $this->created_at,
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
