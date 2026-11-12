<?php

use App\Models\Car;
use App\Models\Lead;
use App\Services\Gemini\ConversationContext;
use App\Services\Gemini\Tools\HandoffToSalesTool;

function makeLead(array $overrides = []): Lead
{
    return Lead::create([
        'phone' => '6281200000001',
        'wa_thread_id' => '6281200000001',
        'source' => 'whatsapp',
        'status' => 'bot_handling',
        ...$overrides,
    ]);
}

function handoff(array $args): array
{
    return app(HandoffToSalesTool::class)->execute($args);
}

test('flags the current lead with reason, note and car', function () {
    $car = Car::create(['brand' => 'Toyota', 'model' => 'Fortuner', 'year' => 2021, 'price' => 485_000_000, 'status' => 'ready', 'stock_number' => 'STK-0101']);
    $lead = makeLead();
    app(ConversationContext::class)->setLead($lead);

    $result = handoff(['reason' => 'financing', 'note' => 'Mau kredit, DP sekitar 30 juta.', 'stock_number' => 'stk-0101']);

    $lead->refresh();
    expect($result['handed_off'])->toBeTrue()
        ->and($result['car_attached'])->toBe('STK-0101')
        ->and($lead->status)->toBe('needs_handoff')
        ->and($lead->handoff_reason)->toBe('financing')
        ->and($lead->car_id)->toBe($car->id)
        ->and($lead->interest_summary)->toBe('Mau kredit, DP sekitar 30 juta.');
});

test('hands off without a car when none was mentioned', function () {
    $lead = makeLead();
    app(ConversationContext::class)->setLead($lead);

    $result = handoff(['reason' => 'financing', 'note' => 'Tanya bisa kredit atau tidak.']);

    expect($result['handed_off'])->toBeTrue()
        ->and($result['car_attached'])->toBeNull()
        ->and($lead->refresh()->status)->toBe('needs_handoff')
        ->and($lead->car_id)->toBeNull();
});

test('unknown stock number still hands off and keeps any existing car', function () {
    $car = Car::create(['brand' => 'Honda', 'model' => 'Brio', 'year' => 2020, 'price' => 140_000_000, 'status' => 'ready', 'stock_number' => 'STK-0001']);
    $lead = makeLead(['car_id' => $car->id]);
    app(ConversationContext::class)->setLead($lead);

    $result = handoff(['reason' => 'test_drive', 'note' => 'Mau test drive besok.', 'stock_number' => 'STK-9999']);

    expect($result['handed_off'])->toBeTrue()
        ->and($result)->toHaveKey('car_note')
        ->and($lead->refresh()->car_id)->toBe($car->id);
});

test('does not downgrade a lead staff already took over', function () {
    $lead = makeLead(['status' => 'human_handling']);
    app(ConversationContext::class)->setLead($lead);

    handoff(['reason' => 'visit', 'note' => 'Mau datang Sabtu.']);

    expect($lead->refresh()->status)->toBe('human_handling')
        ->and($lead->handoff_reason)->toBe('visit');
});

test('fills name only when the lead has none', function () {
    $named = makeLead(['name' => 'Budi']);
    app(ConversationContext::class)->setLead($named);
    handoff(['reason' => 'other', 'note' => 'x', 'customer_name' => 'Andi']);
    expect($named->refresh()->name)->toBe('Budi');

    $unnamed = makeLead(['phone' => '6281200000002', 'wa_thread_id' => '6281200000002']);
    app(ConversationContext::class)->setLead($unnamed);
    handoff(['reason' => 'other', 'note' => 'x', 'customer_name' => 'Andi']);
    expect($unnamed->refresh()->name)->toBe('Andi');
});

test('invalid reason falls back to other', function () {
    $lead = makeLead();
    app(ConversationContext::class)->setLead($lead);

    handoff(['reason' => 'trade_in', 'note' => 'Mau tukar tambah.']);

    expect($lead->refresh()->handoff_reason)->toBe('other');
});

test('without a conversation context it reports failure instead of touching any lead', function () {
    $lead = makeLead();

    $result = handoff(['reason' => 'financing', 'note' => 'x']);

    expect($result['handed_off'])->toBeFalse()
        ->and($lead->refresh()->status)->toBe('bot_handling');
});

test('declares no phone or lead id params', function () {
    $declaration = app(HandoffToSalesTool::class)->declaration();

    expect(array_keys($declaration['parameters']['properties']))
        ->toEqualCanonicalizing(['reason', 'note', 'stock_number', 'customer_name'])
        ->and($declaration['parameters']['properties']['reason']['enum'])->toBe(HandoffToSalesTool::REASONS);
});
