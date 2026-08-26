<?php

namespace App\Console\Commands;

use App\Services\Gemini\GeminiClient;
use Illuminate\Console\Command;

/**
 * Manual round-trip check + the free-tier stress-testing tool flagged in
 * HANDOFF-3.md. Run this repeatedly with varied prompts to observe
 * hallucination behavior, latency, and when 429s start showing up.
 */
class GeminiTestCall extends Command
{
    protected $signature = 'gemini:test {prompt? : The prompt to send to Gemini}';
    protected $description = 'Send a single bare prompt to Gemini and print the reply.';

    public function handle(GeminiClient $client): int
    {
        $prompt = $this->argument('prompt') ?? 'Hello, who are you?';

        $this->info('Model: '.config('services.gemini.model'));
        $this->info('Prompt: '.$prompt);
        $this->newLine();

        try {
            $reply = $client->generateText($prompt);
        } catch (\Throwable $e) {
            $this->error("Gemini call failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->line($reply);

        return self::SUCCESS;
    }
}