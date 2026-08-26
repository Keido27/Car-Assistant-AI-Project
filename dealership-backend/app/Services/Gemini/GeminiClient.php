<?php

namespace App\Services\Gemini;

use Illuminate\Support\Facades\Http;

/**
 * Bare wrapper around Gemini's REST API. No SDK — Google doesn't maintain
 * an official PHP SDK the way Anthropic/OpenAI do, so this is a thin
 * Laravel HTTP client call instead. Deliberately has no retry/backoff
 * logic yet: we want raw rate-limit/error behavior visible while
 * stress-testing the free tier, not silently smoothed over.
 */
class GeminiClient
{
    public function __construct(
        protected string $apiKey,
        protected string $model,
        protected string $baseUrl,
    ) {}

    public function generateText(string $prompt): string
    {
        $response = Http::withHeaders([
            'x-goog-api-key' => $this->apiKey,
        ])->post("{$this->baseUrl}/models/{$this->model}:generateContent", [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]],
            ],
        ]);

        $response->throw();

        return data_get($response->json(), 'candidates.0.content.parts.0.text', '');
    }
}