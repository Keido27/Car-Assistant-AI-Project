<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null; // Sanctum guard already applied at the route level
    }

    public function rules(): array
    {
        $carId = $this->route('car')?->id;

        return [
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'variant' => ['nullable', 'string', 'max:100'],
            'year' => ['required', 'integer', 'min:1990', 'max:' . (date('Y') + 1)],
            'price' => ['required', 'integer', 'min:0'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'transmission' => ['nullable', Rule::in(['manual', 'automatic', 'cvt'])],
            'fuel_type' => ['nullable', Rule::in(['petrol', 'diesel', 'hybrid', 'electric'])],
            'condition_notes' => ['nullable', 'string', 'max:5000'],
            'color' => ['nullable', 'string', 'max:50'],
            'plate_region' => ['nullable', 'string', 'max:10'],
            'status' => ['required', Rule::in(['ready', 'booked', 'sold'])],
            'stock_number' => [
                'required', 'string', 'max:50',
                Rule::unique('cars', 'stock_number')->ignore($carId),
            ],
        ];
    }
}
