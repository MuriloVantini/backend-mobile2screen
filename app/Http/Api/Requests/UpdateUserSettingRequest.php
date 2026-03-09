<?php

namespace App\Http\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notify_alert_failed' => 'sometimes|boolean',
            'notify_device_offline' => 'sometimes|boolean',
            'notify_weekly_report' => 'sometimes|boolean',
            'notify_device_connected' => 'sometimes|boolean',
            'notify_limit_reached' => 'sometimes|boolean',
            'notification_email' => 'nullable|email',
            'notification_phone' => 'nullable|string|max:50',
            'timezone' => 'sometimes|string|max:50',
            'language' => 'sometimes|string|max:10',
            'theme' => 'sometimes|in:light,dark,auto',
        ];
    }
}
