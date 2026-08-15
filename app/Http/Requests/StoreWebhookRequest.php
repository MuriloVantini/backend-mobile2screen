<?php

namespace App\Http\Requests;

use App\Rules\PublicWebhookUrl;
use Illuminate\Foundation\Http\FormRequest;

class StoreWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'url' => ['required', 'string', 'max:2048', new PublicWebhookUrl],
            'secret' => 'required|string|min:16|max:255',
            'events' => 'required|array|min:1',
            'events.*' => 'string|in:alert.sent,alert.delivered,alert.failed,device.online,device.offline,device.added',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
