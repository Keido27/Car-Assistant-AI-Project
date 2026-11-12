<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Services\Gemini\BotSystemPrompt;
use App\Services\Gemini\ConversationContext;
use App\Services\Gemini\ToolCallingConversation;
use Illuminate\Console\Command;

/**
 * Manual round-trip check for the tool-calling loop, same role as
 * gemini:test but exercising search_inventory instead of a bare prompt.
 * Run this against the real API before trusting the loop in shadow mode.
 */
class GeminiToolTestCall extends Command
{
    protected $signature = 'gemini:test-tools {prompt? : What to ask the bot} {--thread= : wa_thread_id of an existing lead, so handoff_to_sales has a customer to update}';
    protected $description = 'Send a prompt through the tool-calling loop and print the final reply.';

    public function handle(ToolCallingConversation $conversation): int
    {
        $prompt = $this->argument('prompt') ?? 'Ada Toyota Avanza gak?';

        if ($thread = $this->option('thread')) {
            $lead = Lead::where('wa_thread_id', $thread)->first();
            if (! $lead) {
                $this->error("No lead with wa_thread_id {$thread}.");

                return self::FAILURE;
            }
            app(ConversationContext::class)->setLead($lead);
            $this->info("Lead: #{$lead->id} ({$lead->phone})");
        }

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

        if (isset($lead)) {
            $lead->refresh();
            $this->newLine();
            $this->info("Lead after: status={$lead->status}, reason=".($lead->handoff_reason ?? '-')
                .', car_id='.($lead->car_id ?? '-').', note='.($lead->interest_summary ?? '-'));
        }

        return self::SUCCESS;
    }
}