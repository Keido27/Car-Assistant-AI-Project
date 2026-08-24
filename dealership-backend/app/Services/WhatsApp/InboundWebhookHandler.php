<?php

namespace App\Services\WhatsApp;

use App\Models\Conversation;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;

/**
 * Parses an inbound WhatsApp Cloud API webhook payload and turns it into
 * Lead + Conversation rows. No AI involved yet (see HANDOFF-3.md) — this
 * just gets messages safely into the database so the bot layer has
 * something real to work against once Gemini is wired in.
 */
class InboundWebhookHandler
{
    public function handle(array $payload): void
    {
        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                // Meta reuses this webhook for message *status* updates
                // (sent/delivered/read) as well as actual messages — those
                // live under value.statuses instead of value.messages, and
                // we don't care about them yet.
                if (! isset($change['value']['messages'])) {
                    continue;
                }

                $this->handleMessages($change['value']);
            }
        }
    }

    private function handleMessages(array $value): void
    {
        $contactsByWaId = collect($value['contacts'] ?? [])->keyBy('wa_id');

        foreach ($value['messages'] as $message) {
            $this->handleSingleMessage($message, $contactsByWaId);
        }
    }

    private function handleSingleMessage(array $message, $contactsByWaId): void
    {
        $waMessageId = $message['id'] ?? null;

        // Meta redelivers webhooks that weren't ack'd fast enough. Skip
        // anything we've already logged rather than double-posting it.
        if ($waMessageId && Conversation::where('wa_message_id', $waMessageId)->exists()) {
            return;
        }

        $from = $message['from'] ?? null;

        if (! $from) {
            Log::warning('WhatsApp webhook message missing "from"', ['message' => $message]);

            return;
        }

        $contactName = $contactsByWaId->get($from)['profile']['name'] ?? null;

        $lead = Lead::firstOrCreate(
            ['wa_thread_id' => $from],
            [
                'phone' => $from,
                'name' => $contactName,
                'source' => 'whatsapp',
                'status' => 'bot_handling',
            ]
        );

        $lead->conversations()->create([
            'sender' => 'customer',
            'message' => $this->extractText($message),
            'wa_message_id' => $waMessageId,
            'meta' => ['type' => $message['type'] ?? 'unknown'],
            'sent_at' => isset($message['timestamp'])
                ? now()->createFromTimestamp((int) $message['timestamp'])
                : now(),
        ]);

        $lead->update(['last_message_at' => now()]);
    }

    /**
     * Only plain text is handled for now. Media (image/audio/document/etc.)
     * gets logged as a placeholder so nothing crashes — actually reading
     * media content is a later-phase problem.
     */
    private function extractText(array $message): string
    {
        if (($message['type'] ?? null) === 'text') {
            return $message['text']['body'] ?? '';
        }

        return '[Unsupported message type: '.($message['type'] ?? 'unknown').']';
    }
}