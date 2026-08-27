<?php

namespace App\Services\Gemini\Tools;

use App\Models\Car;

/**
 * Reuses Car::scopeMatching() — the same scope the admin dashboard's
 * filter is meant to share (see Car.php's docblock) — so the bot and the
 * admin UI never drift apart on what counts as a match. Defaults to
 * status=ready (scopeMatching's own default), since a customer should
 * only ever hear about cars that are actually sellable right now.
 *
 * Deliberately returns a lean, hand-picked field set rather than
 * CarResource — photos/URLs add tokens without helping the model answer,
 * and every field returned here comes straight from the DB row, so there's
 * no room for the bot to embellish beyond what's actually on file.
 */
class SearchInventoryTool implements GeminiTool
{
    private const MAX_RESULTS = 8;

    public function name(): string
    {
        return 'search_inventory';
    }

    public function declaration(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'Searches the dealership\'s real, current inventory of cars. '
                .'Always call this instead of guessing what cars are in stock, their prices, '
                .'or their condition — never answer inventory questions from memory, even if '
                .'you searched earlier in this conversation, since stock changes daily. '
                .'Returns up to '.self::MAX_RESULTS.' matching cars.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'brand' => [
                        'type' => 'string',
                        'description' => 'Car brand/make, e.g. "Toyota". Partial matches allowed.',
                    ],
                    'model' => [
                        'type' => 'string',
                        'description' => 'Car model, e.g. "Avanza". Partial matches allowed.',
                    ],
                    'year_min' => [
                        'type' => 'integer',
                        'description' => 'Minimum model year, inclusive.',
                    ],
                    'year_max' => [
                        'type' => 'integer',
                        'description' => 'Maximum model year, inclusive.',
                    ],
                    'price_max' => [
                        'type' => 'integer',
                        'description' => 'Maximum price in IDR, inclusive.',
                    ],
                    'transmission' => [
                        'type' => 'string',
                        'enum' => ['manual', 'automatic', 'cvt'],
                        'description' => 'Only set this if the customer specified a preference.',
                    ],
                ],
                'required' => [],
            ],
        ];
    }

    public function execute(array $args): array
    {
        $cars = Car::query()
            ->matching($args)
            ->orderByDesc('created_at')
            ->limit(self::MAX_RESULTS)
            ->get();

        return [
            'count' => $cars->count(),
            'cars' => $cars->map(fn (Car $car) => [
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
            ])->all(),
        ];
    }
}