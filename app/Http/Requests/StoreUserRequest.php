<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'plan_id' => 'nullable|integer|exists:plans,id',
            'status' => ['nullable', Rule::in(['active', 'suspended', 'pending'])],
            'role' => ['nullable', Rule::in(['user', 'admin'])],
            'last_active' => 'nullable|date',
        ];
    }
}
