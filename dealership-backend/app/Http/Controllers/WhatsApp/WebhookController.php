<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Services\WhatsApp\InboundWebhookHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController extends Controller
{
    /**
     * GET — Meta's one-time verification handshake, triggered whenever the
     * webhook URL is (re)configured in the Meta App dashboard.
     *
     * Meta sends dotted query keys (hub.mode, hub.verify_token, hub.challenge)
     * but PHP automatically converts dots to underscores when parsing query
     * strings into $_GET — so we read them as hub_mode etc. here. Nothing to
     * configure differently on Meta's side; this is just how PHP parses it.
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && hash_equals((string) config('services.whatsapp.verify_token'), (string) $token)) {
            return response($challenge, 200);
        }

        return response('Verification failed.', 403);
    }

    /**
     * POST — actual inbound message delivery. Signature already verified
     * by the whatsapp.signature middleware before this runs.
     */
    public function receive(Request $request, InboundWebhookHandler $handler): Response
    {
        $handler->handle($request->all());

        // Meta requires a fast 200 regardless of what we did with the
        // payload — anything else triggers retries/backoff on their side.
        return response()->noContent();
    }
}