<?php

namespace App\Services\Gemini\Tools;

use App\Models\Car;

/**
 * The single field mapping for cars sent to Gemini, shared by
 * search_inventory and get_car_details so the two tools can never drift
 * apart on what the bot is allowed to know about a car. Every value comes
 * straight from the DB row - nothing derived, nothing the model could
 * mistake for a fact we don't actually have on file.
 */
class CarPayload
{
    public static function summary(Car $car): array
    {
        return [
            'stock_number' => $car->stock_number,
            'brand' => $car->brand,
            'model' => $car->model,
            'variant' => $car->variant,
            'year' => $car->year,
            'price_idr' => $car->price,
            'mileage_km' => $car->mileage,
            'transmission' => $car->transmission,
            'fuel_type' => $car->fuel_type,
            'color' => $car->color,
            'condition_notes' => $car->condition_notes,
        ];
    }
}
