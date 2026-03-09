<?php

namespace App\Http\Api\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => ['sometimes', 'confirmed', Password::defaults()],
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'plan_id' => 'nullable|integer|exists:plans,id',
            'status' => ['sometimes', Rule::in(['active', 'suspended', 'pending'])],
            'role' => ['sometimes', Rule::in(['user', 'admin'])],
            'last_active' => 'nullable|date',
        ];
    }
}
