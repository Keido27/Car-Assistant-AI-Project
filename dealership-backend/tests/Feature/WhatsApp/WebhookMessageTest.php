<?php

use App\Models\Conversation;
use App\Models\Lead;

function metaTextMessagePayload(
    string $from = '628123456789',
    string $text = 'Halo, mobil Avanza masih ada?',
    string $messageId = 'wamid.TEST123',
    string $name = 'Budi'
): array {
    return [
        'object' => 'whatsapp_business_account',
        'entry' => [[
            'id' => 'WABA_ID',
            'changes' => [[
                'value' => [
                    'messaging_product' => 'whatsapp',
                    'metadata' => [
                        'display_phone_number' => '15551234567',
                        'phone_number_id' => '123456123',
                    ],
                    'contacts' => [[
                        'profile' => ['name' => $name],
                        'wa_id' => $from,
                    ]],
                    'messages' => [[
                        'from' => $from,
                        'id' => $messageId,
                        'timestamp' => (string) now()->timestamp,
                        'text' => ['body' => $text],
                        'type' => 'text',
                    ]],
                ],
                'field' => 'messages',
            ]],
        ]],
    ];
}

function postSignedWebhook(array $payload)
{
    $body = json_encode($payload);
    $signature = 'sha256='.hash_hmac('sha256', $body, config('services.whatsapp.app_secret'));

    return test()->postJson('/api/webhook/whatsapp', $payload, [
        'X-Hub-Signature-256' => $signature,
    ]);
}

test('rejects webhook with missing signature', function () {
    $response = $this->postJson('/api/webhook/whatsapp', metaTextMessagePayload());

    $response->assertStatus(401);
});

test('rejects webhook with invalid signature', function () {
    $response = $this->postJson('/api/webhook/whatsapp', metaTextMessagePayload(), [
        'X-Hub-Signature-256' => 'sha256=not-the-right-signature',
    ]);

    $response->assertStatus(401);
});

test('creates a new lead and conversation from an inbound message', function () {
    $response = postSignedWebhook(metaTextMessagePayload(from: '628123456789', text: 'Avanza masih ada?', messageId: 'wamid.ABC'));

    $response->assertNoContent();

    $lead = Lead::where('wa_thread_id', '628123456789')->first();

    expect($lead)->not->toBeNull();
    expect($lead->source)->toBe('whatsapp');
    expect($lead->status)->toBe('bot_handling');
    expect($lead->name)->toBe('Budi');

    $conversation = $lead->conversations()->first();
    expect($conversation->sender)->toBe('customer');
    expect($conversation->message)->toBe('Avanza masih ada?');
    expect($conversation->wa_message_id)->toBe('wamid.ABC');
});

test('reuses an existing lead for the same wa_thread_id', function () {
    postSignedWebhook(metaTextMessagePayload(from: '628111', messageId: 'wamid.ONE'));
    postSignedWebhook(metaTextMessagePayload(from: '628111', messageId: 'wamid.TWO'));

    expect(Lead::where('wa_thread_id', '628111')->count())->toBe(1);
    expect(Conversation::count())->toBe(2);
});

test('does not duplicate a message redelivered by Meta', function () {
    $payload = metaTextMessagePayload(from: '628222', messageId: 'wamid.DUPLICATE');

    postSignedWebhook($payload);
    postSignedWebhook($payload); // simulates Meta's retry

    expect(Conversation::where('wa_message_id', 'wamid.DUPLICATE')->count())->toBe(1);
});

test('ignores status update payloads without erroring', function () {
    $payload = [
        'object' => 'whatsapp_business_account',
        'entry' => [[
            'id' => 'WABA_ID',
            'changes' => [[
                'value' => [
                    'messaging_product' => 'whatsapp',
                    'statuses' => [[
                        'id' => 'wamid.STATUS',
                        'status' => 'delivered',
                        'timestamp' => (string) now()->timestamp,
                        'recipient_id' => '628123456789',
                    ]],
                ],
                'field' => 'messages',
            ]],
        ]],
    ];

    $response = postSignedWebhook($payload);

    $response->assertNoContent();
    expect(Conversation::count())->toBe(0);
});