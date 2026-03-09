<?php

namespace App\Http\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => ['required', Rule::in(['info', 'warning', 'critical', 'success'])],
            'duration_seconds' => 'nullable|integer|min:1',
            'priority' => 'nullable|integer|min:0|max:10',
            'expires_at' => 'nullable|date',
            'tags' => 'required|array|min:1',
            'tags.*' => 'exists:tags,id',
        ];
    }
}
