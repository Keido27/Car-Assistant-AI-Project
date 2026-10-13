<?php

use App\Services\Gemini\GeminiClient;
use App\Services\Gemini\Tools\GeminiTool;
use App\Services\Gemini\ToolCallingConversation;
use Illuminate\Support\Facades\Http;

test('empty functionCall args and an empty tool result round-trip as JSON objects, not arrays', function () {
    Http::fake([
        '*generateContent*' => Http::sequence()
            ->push(['candidates' => [['content' => ['role' => 'model', 'parts' => [[
                // Simulates Gemini calling a tool with no args at all (e.g. the
                // "Vague" prompt) — Meta sends "args": {}, which PHP decodes to [].
                'functionCall' => ['id' => 'call-1', 'name' => 'noop_tool', 'args' => []],
            ]]]]]])
            ->push(['candidates' => [['content' => ['role' => 'model', 'parts' => [[
                'text' => 'ok',
            ]]]]]]),
    ]);

    $client = new GeminiClient(apiKey: 'test-key', model: 'gemini-test', baseUrl: 'https://example.test');

    // A minimal tool whose result is itself an empty array — distinct from
    // SearchInventoryTool's {"count":0,"cars":[]}, which is never empty at
    // the top level even when nothing matches.
    $tool = new class implements GeminiTool
    {
        public function name(): string
        {
            return 'noop_tool';
        }

        public function declaration(): array
        {
            return [
                'name' => 'noop_tool',
                'description' => 'Test tool with no meaningful args or result.',
                'parameters' => ['type' => 'object', 'properties' => new stdClass, 'required' => []],
            ];
        }

        public function execute(array $args): array
        {
            return [];
        }
    };

    $conversation = (new ToolCallingConversation($client))->registerTool($tool);

    $conversation->run([['role' => 'user', 'parts' => [['text' => 'test']]]]);

    $requests = Http::recorded();
    expect($requests)->toHaveCount(2);

    $secondRequestBody = $requests[1][0]->body();

    expect($secondRequestBody)
        ->toContain('"args":{}')
        ->not->toContain('"args":[]')
        ->toContain('"response":{}')
        ->not->toContain('"response":[]');
});
