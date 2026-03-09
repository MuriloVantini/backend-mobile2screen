<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['tv', 'rpi'])],
            'location' => 'nullable|string|max:255',
            'ip_address' => 'nullable|ip',
            'mac_address' => 'nullable|string',
            'metadata' => 'nullable|array',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ];
    }
}
