<?php

namespace App\Services\Gemini;

use App\Models\Lead;

/**
 * Which lead the bot is currently talking to. Set by whoever starts the
 * conversation (webhook handler, gemini:test-tools --thread=...) and read
 * by tools that act on "this customer" — e.g. handoff_to_sales.
 *
 * Exists so the model never supplies a lead id or phone number itself:
 * identity comes from the server side, never from tool args.
 *
 * Bound as `scoped` in AppServiceProvider, so it resets per request / queue
 * job. Tools must resolve it at execute() time via app(), NOT inject it in
 * their constructor — ToolCallingConversation is a singleton, so a
 * constructor-injected context would be the first one ever created and
 * leak one customer's lead into another's conversation.
 */
class ConversationContext
{
    private ?Lead $lead = null;

    public function setLead(?Lead $lead): static
    {
        $this->lead = $lead;

        return $this;
    }

    public function lead(): ?Lead
    {
        return $this->lead;
    }
}
