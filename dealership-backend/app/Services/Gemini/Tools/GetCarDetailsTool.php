<?php

namespace App\Services\Gemini\Tools;

use App\Models\Car;

/**
 * Looks up one specific car by stock number - the identifier
 * search_inventory already hands the model - for follow-up questions like
 * "the Avanza you mentioned, how many km?" without re-running a search.
 *
 * Keyed on stock_number rather than the DB id: the bot never sees ids, so
 * a stock_number is the only reference it can legitimately have.
 *
 * Unlike search_inventory, this does NOT filter to status=ready: a
 * customer may ask about a car they saw earlier that has since sold, and
 * the bot should say so plainly. For any car that isn't ready, only
 * identity fields are returned - never a price - so the bot can't quote
 * a price for a car that's no longer for sale.
 */
class GetCarDetailsTool implements GeminiTool
{
    public function name(): string
    {
        return 'get_car_details';
    }

    public function declaration(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'Gets the current, full details of one specific car by its stock number '
                .'(e.g. "STK-0099"), as returned by search_inventory. Use this for follow-up '
                .'questions about a car already mentioned in the conversation. Always call it '
                .'rather than repeating details from earlier in the conversation, since price and '
                .'availability can change. If you do not have a stock number, use '
                .'search_inventory instead - never guess one.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'stock_number' => [
                        'type' => 'string',
                        'description' => 'The car\'s stock number exactly as returned by search_inventory.',
                    ],
                ],
                'required' => ['stock_number'],
            ],
        ];
    }

    public function execute(array $args): array
    {
        $stockNumber = strtoupper(trim((string) ($args['stock_number'] ?? '')));

        if ($stockNumber === '') {
            return [
                'found' => false,
                'reason' => 'No stock_number given. Use search_inventory to find the car first.',
            ];
        }

        $car = Car::query()
            ->withCount('photos')
            ->whereRaw('upper(stock_number) = ?', [$stockNumber])
            ->first();

        if ($car === null) {
            return [
                'found' => false,
                'reason' => 'No car with this stock number. Do not guess details; use search_inventory instead.',
            ];
        }

        if ($car->status !== 'ready') {
            return [
                'found' => true,
                'available' => false,
                'stock_number' => $car->stock_number,
                'brand' => $car->brand,
                'model' => $car->model,
                'year' => $car->year,
                'note' => 'This car is no longer available for sale. Do not quote its price; '
                    .'offer to search for similar cars instead.',
            ];
        }

        return [
            'found' => true,
            'available' => true,
            ...CarPayload::summary($car),
            'plate_region' => $car->plate_region,
            'photo_count' => $car->photos_count,
        ];
    }
}
