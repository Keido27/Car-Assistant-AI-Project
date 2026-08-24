<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies Meta's X-Hub-Signature-256 header against the raw request body.
 * Meta signs the exact bytes it sent, so this must run against
 * $request->getContent() — not a re-encoded/re-parsed version of the body,
 * or the HMAC will never match.
 */
class VerifyWhatsAppSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.whatsapp.app_secret');

        abort_if(blank($secret), 500, 'WHATSAPP_APP_SECRET is not configured.');

        $signatureHeader = $request->header('X-Hub-Signature-256', '');

        if (! str_starts_with($signatureHeader, 'sha256=')) {
            abort(401, 'Missing WhatsApp signature.');
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);
        $provided = substr($signatureHeader, strlen('sha256='));

        if (! hash_equals($expected, $provided)) {
            abort(401, 'Invalid WhatsApp signature.');
        }

        return $next($request);
    }
}