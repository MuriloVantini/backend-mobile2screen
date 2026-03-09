<?php

namespace App\Http\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'url' => 'sometimes|url',
            'secret' => 'nullable|string|max:255',
            'events' => 'sometimes|array|min:1',
            'events.*' => 'string|in:alert.sent,alert.delivered,alert.failed,device.online,device.offline,device.added',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
