<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Models\Lead;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    /**
     * POST /api/leads/{lead}/conversations
     * Staff sending a manual WhatsApp reply from the dashboard. The actual
     * WhatsApp send call happens in a later phase (webhook/service layer);
     * this just logs it and stamps the lead. Wired to the send-to-WA job once
     * the Meta Cloud API integration lands.
     */
    public function store(Request $request, Lead $lead)
    {
        $request->validate(['message' => ['required', 'string', 'max:4096']]);

        $conversation = $lead->conversations()->create([
            'sender' => 'human',
            'user_id' => $request->user()->id,
            'message' => $request->string('message'),
            'sent_at' => now(),
        ]);

        $lead->update(['last_message_at' => now()]);

        return new ConversationResource($conversation);
    }
}
