<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'type' => ['sometimes', Rule::in(['tv', 'rpi'])],
            'location' => 'nullable|string|max:255',
            'ip_address' => 'nullable|ip',
            'mac_address' => 'nullable|string',
            'metadata' => 'nullable|array',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ];
    }
}
