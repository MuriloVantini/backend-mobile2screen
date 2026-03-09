<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DailyStatisticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'days' => 'nullable|integer|min:1|max:90',
        ];
    }
}
