<?php

use App\Models\Car;
use App\Services\Gemini\GeminiClient;
use App\Services\Gemini\Tools\SearchInventoryTool;
use App\Services\Gemini\ToolCallingConversation;
use Illuminate\Support\Facades\Http;

test('runs a full search_inventory tool call and returns the final text', function () {
    Car::create([
        'brand' => 'Toyota', 'model' => 'Avanza', 'variant' => 'G', 'year' => 2021,
        'price' => 185_000_000, 'mileage' => 32000, 'transmission' => 'automatic',
        'fuel_type' => 'petrol', 'condition_notes' => 'Service history complete, one owner.',
        'status' => 'ready', 'stock_number' => 'STK-0099',
    ]);

    Http::fake([
        '*generateContent*' => Http::sequence()
            ->push(['candidates' => [['content' => ['role' => 'model', 'parts' => [[
                'functionCall' => ['id' => 'call-1', 'name' => 'search_inventory', 'args' => ['brand' => 'Toyota']],
            ]]]]]])
            ->push(['candidates' => [['content' => ['role' => 'model', 'parts' => [[
                'text' => 'We have a 2021 Toyota Avanza G in stock for Rp185,000,000.',
            ]]]]]]),
    ]);

    $client = new GeminiClient(apiKey: 'test-key', model: 'gemini-test', baseUrl: 'https://example.test');
    $conversation = (new ToolCallingConversation($client))->registerTool(new SearchInventoryTool());

    $result = $conversation->run([['role' => 'user', 'parts' => [['text' => 'Do you have any Toyota?']]]]);

    expect($result['text'])->toContain('Toyota Avanza');
    Http::assertSentCount(2);
});

test('stops after MAX_TURNS instead of looping forever on a stuck model', function () {
    Http::fake([
        '*generateContent*' => Http::response([
            'candidates' => [['content' => ['role' => 'model', 'parts' => [[
                'functionCall' => ['id' => 'x', 'name' => 'search_inventory', 'args' => []],
            ]]]]],
        ]),
    ]);

    $client = new GeminiClient(apiKey: 'test-key', model: 'gemini-test', baseUrl: 'https://example.test');
    $conversation = (new ToolCallingConversation($client))->registerTool(new SearchInventoryTool());

    $result = $conversation->run([['role' => 'user', 'parts' => [['text' => 'test']]]]);

    expect($result['text'])->not->toBeEmpty();
    Http::assertSentCount(4); // MAX_TURNS
});