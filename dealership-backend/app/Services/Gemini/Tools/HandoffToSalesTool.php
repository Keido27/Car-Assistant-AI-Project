<?php

namespace App\Services\Gemini\Tools;

use App\Models\Car;
use App\Services\Gemini\ConversationContext;

/**
 * Flags the current customer's lead for a human salesperson. The lead row
 * already exists (InboundWebhookHandler creates it on first message, with
 * phone + WhatsApp profile name), so this updates it rather than creating
 * one: sets status=needs_handoff, the reason, an optional car of interest,
 * and a short note for sales.
 *
 * The model never passes a phone number or lead id — the lead comes from
 * ConversationContext. A missing car is fine: a warm lead shouldn't have to
 * answer questions before reaching a human, so the handoff goes through
 * either way.
 */
class HandoffToSalesTool implements GeminiTool
{
    /** Keep in sync with the leads.handoff_reason enum migration. */
    public const REASONS = [
        'financing',
        'test_drive',
        'visit',
        'negotiation',
        'deposit',
        'notify_when_available',
        'other',
    ];

    /** Statuses the bot may move to needs_handoff. Never downgrade a lead staff already own. */
    private const BOT_OWNED_STATUSES = ['bot_handling', 'needs_handoff'];

    public function name(): string
    {
        return 'handoff_to_sales';
    }

    public function declaration(): array
    {
        return [
            'name' => $this->name(),
            'description' => 'Hands this customer over to a human salesperson, who will contact them on '
                .'this same WhatsApp number. Call this whenever the customer asks about financing/credit/'
                .'installments, wants to negotiate, test drive, visit, or put down a deposit, asks for a '
                .'human, or wants to be notified when a car they want comes in. Do NOT ask for their phone '
                .'number — we already have it. Do NOT ask which car first if they haven\'t said; hand off '
                .'anyway.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'reason' => [
                        'type' => 'string',
                        'enum' => self::REASONS,
                        'description' => 'Main reason for the handoff.',
                    ],
                    'note' => [
                        'type' => 'string',
                        'description' => 'One or two sentences for the salesperson: what the customer wants, '
                            .'in their terms (e.g. budget, preferred day for a test drive). Only facts the '
                            .'customer actually said.',
                    ],
                    'stock_number' => [
                        'type' => 'string',
                        'description' => 'Stock number of the car they are interested in, only if one was '
                            .'identified in this conversation (e.g. "STK-0231"). Omit otherwise — never guess.',
                    ],
                    'customer_name' => [
                        'type' => 'string',
                        'description' => 'Only if the customer told you their name.',
                    ],
                ],
                'required' => ['reason', 'note'],
            ],
        ];
    }

    public function execute(array $args): array
    {
        $lead = app(ConversationContext::class)->lead();

        if (! $lead) {
            return [
                'handed_off' => false,
                'error' => 'No customer record for this conversation.',
                'instruction' => 'Tell the customer a team member will follow up with them. Do not promise a time.',
            ];
        }

        $reason = in_array($args['reason'] ?? null, self::REASONS, true) ? $args['reason'] : 'other';
        $note = trim((string) ($args['note'] ?? ''));

        $updates = [
            'handoff_reason' => $reason,
            'interest_summary' => $note !== '' ? $note : $lead->interest_summary,
        ];

        if (in_array($lead->status, self::BOT_OWNED_STATUSES, true)) {
            $updates['status'] = 'needs_handoff';
        }

        $name = trim((string) ($args['customer_name'] ?? ''));
        if ($name !== '' && blank($lead->name)) {
            $updates['name'] = $name;
        }

        $car = null;
        $stockNumber = trim((string) ($args['stock_number'] ?? ''));
        if ($stockNumber !== '') {
            $car = Car::whereRaw('LOWER(stock_number) = ?', [mb_strtolower($stockNumber)])->first();
            if ($car) {
                $updates['car_id'] = $car->id;
            }
        }

        $lead->update($updates);

        return [
            'handed_off' => true,
            'reason' => $reason,
            'car_attached' => $car?->stock_number,
            ...($stockNumber !== '' && ! $car
                ? ['car_note' => "Stock number {$stockNumber} not found; handed off without a car attached."]
                : []),
            'instruction' => 'Tell the customer our sales team will contact them on this WhatsApp number. '
                .'Do not promise a specific time. Do not give any financing, price-negotiation, or '
                .'availability commitments yourself.',
        ];
    }
}
