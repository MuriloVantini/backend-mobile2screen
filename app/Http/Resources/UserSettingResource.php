<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'notify_alert_failed' => $this->notify_alert_failed,
            'notify_device_offline' => $this->notify_device_offline,
            'notify_weekly_report' => $this->notify_weekly_report,
            'notify_device_connected' => $this->notify_device_connected,
            'notify_limit_reached' => $this->notify_limit_reached,
            'notification_email' => $this->notification_email,
            'notification_phone' => $this->notification_phone,
            'timezone' => $this->timezone,
            'language' => $this->language,
            'theme' => $this->theme,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
