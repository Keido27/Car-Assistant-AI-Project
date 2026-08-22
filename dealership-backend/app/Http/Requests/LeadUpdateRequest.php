<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeadUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'car_id' => ['sometimes', 'nullable', 'exists:cars,id'],
            'interest_summary' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'status' => ['sometimes', Rule::in([
                'bot_handling', 'needs_handoff', 'human_handling',
                'visit_scheduled', 'converted', 'lost',
            ])],
            'assigned_to' => ['sometimes', 'nullable', 'exists:users,id'],
        ];
    }
}
