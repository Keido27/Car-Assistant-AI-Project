<?php

use App\Models\Car;
use App\Services\Gemini\Tools\SearchInventoryTool;

test('search_inventory only returns ready cars and respects filters', function () {
    Car::create(['brand' => 'Honda', 'model' => 'Brio', 'year' => 2020, 'price' => 140_000_000, 'status' => 'ready', 'stock_number' => 'STK-1']);
    Car::create(['brand' => 'Honda', 'model' => 'Brio', 'year' => 2020, 'price' => 140_000_000, 'status' => 'sold', 'stock_number' => 'STK-2']);
    Car::create(['brand' => 'Toyota', 'model' => 'Avanza', 'year' => 2020, 'price' => 180_000_000, 'status' => 'ready', 'stock_number' => 'STK-3']);

    $result = (new SearchInventoryTool())->execute(['brand' => 'Honda']);

    expect($result['count'])->toBe(1)
        ->and($result['cars'][0]['stock_number'])->toBe('STK-1');
});

test('search_inventory returns zero results without erroring when nothing matches', function () {
    $result = (new SearchInventoryTool())->execute(['brand' => 'Ferrari']);

    expect($result['count'])->toBe(0)
        ->and($result['cars'])->toBe([]);
});