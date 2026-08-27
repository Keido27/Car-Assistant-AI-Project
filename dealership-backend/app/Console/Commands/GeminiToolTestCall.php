<?php

namespace App\Console\Commands;

use App\Services\Gemini\BotSystemPrompt;
use App\Services\Gemini\ToolCallingConversation;
use Illuminate\Console\Command;

/**
 * Manual round-trip check for the tool-calling loop, same role as
 * gemini:test but exercising search_inventory instead of a bare prompt.
 * Run this against the real API before trusting the loop in shadow mode.
 */
class GeminiToolTestCall extends Command
{
    protected $signature = 'gemini:test-tools {prompt? : What to ask the bot}';
    protected $description = 'Send a prompt through the tool-calling loop and print the final reply.';

    public function handle(ToolCallingConversation $conversation): int
    {
        $prompt = $this->argument('prompt') ?? 'Ada Toyota Avanza gak?';

        $this->info('Prompt: '.$prompt);
        $this->newLine();

        try {
            $result = $conversation->run(
                [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                BotSystemPrompt::text(),
            );
        } catch (\Throwable $e) {
            $this->error("Call failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->line($result['text']);

        return self::SUCCESS;
    }
}