<?php

use App\Models\Car;
use App\Services\Gemini\Tools\GetCarDetailsTool;

function carForDetailsTest(array $overrides = []): Car
{
    return Car::create([
        'brand' => 'Toyota', 'model' => 'Avanza', 'variant' => 'G', 'year' => 2021,
        'price' => 185_000_000, 'mileage' => 32000, 'transmission' => 'automatic',
        'fuel_type' => 'petrol', 'color' => 'Silver', 'plate_region' => 'B',
        'condition_notes' => 'Service history complete, one owner.',
        'status' => 'ready', 'stock_number' => 'STK-0099',
        ...$overrides,
    ]);
}

test('returns full details for a ready car', function () {
    carForDetailsTest();

    $result = (new GetCarDetailsTool())->execute(['stock_number' => 'STK-0099']);

    expect($result['found'])->toBeTrue()
        ->and($result['available'])->toBeTrue()
        ->and($result['price_idr'])->toBe(185_000_000)
        ->and($result['mileage_km'])->toBe(32000)
        ->and($result['plate_region'])->toBe('B')
        ->and($result['photo_count'])->toBe(0);
});

test('matches stock number case-insensitively and ignores whitespace', function () {
    carForDetailsTest();

    $result = (new GetCarDetailsTool())->execute(['stock_number' => '  stk-0099 ']);

    expect($result['found'])->toBeTrue();
});

test('never returns a price for a car that is not ready', function () {
    carForDetailsTest(['status' => 'sold']);

    $result = (new GetCarDetailsTool())->execute(['stock_number' => 'STK-0099']);

    expect($result['found'])->toBeTrue()
        ->and($result['available'])->toBeFalse()
        ->and($result)->not->toHaveKey('price_idr');
});

test('reports not found for an unknown stock number', function () {
    $result = (new GetCarDetailsTool())->execute(['stock_number' => 'STK-9999']);

    expect($result['found'])->toBeFalse();
});

test('reports not found for a soft-deleted car', function () {
    carForDetailsTest()->delete();

    $result = (new GetCarDetailsTool())->execute(['stock_number' => 'STK-0099']);

    expect($result['found'])->toBeFalse();
});

test('handles missing args without erroring', function () {
    $result = (new GetCarDetailsTool())->execute([]);

    expect($result['found'])->toBeFalse();
});
