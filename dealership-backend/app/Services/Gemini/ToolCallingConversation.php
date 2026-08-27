<?php

namespace App\Services\Gemini;

use App\Services\Gemini\Tools\GeminiTool;

/**
 * Runs the multi-turn tool-calling loop: send contents + tool declarations,
 * check the response for a functionCall part, execute the matching local
 * tool, feed a functionResponse back, repeat. Gemini's API is stateless
 * (see HANDOFF-5.md on thought signatures), so the full conversation is
 * threaded through every call rather than relying on server-side memory.
 *
 * MAX_TURNS is a hard stop against a runaway tool-call loop (e.g. the model
 * repeatedly calling the same tool) — not expected in normal use, but
 * cheap insurance against burning through the Gemini free tier on one bad
 * conversation.
 */
class ToolCallingConversation
{
    private const MAX_TURNS = 4;

    /** @var array<string, GeminiTool> */
    private array $tools = [];

    public function __construct(private readonly GeminiClient $client) {}

    public function registerTool(GeminiTool $tool): static
    {
        $this->tools[$tool->name()] = $tool;

        return $this;
    }

    /**
     * @param  array  $contents  Conversation so far, in Gemini's format.
     * @return array{text: string, contents: array} Final reply text, plus
     *         the full updated conversation (including tool turns) so the
     *         caller can persist it or continue the thread later.
     */
public function run(array $contents, ?string $systemInstruction = null): array
{
    $declarations = collect($this->tools)->map->declaration()->values()->all();

    for ($turn = 0; $turn < self::MAX_TURNS; $turn++) {
        $response = $this->client->generateContent($contents, $declarations, $systemInstruction);

        $parts = data_get($response, 'candidates.0.content.parts', []);
        $functionCallPart = collect($parts)->first(fn ($part) => isset($part['functionCall']));

        if (! $functionCallPart) {
            $text = collect($parts)->pluck('text')->filter()->implode('');

            return ['text' => $text, 'contents' => $contents];
        }

        $modelTurn = data_get($response, 'candidates.0.content');
        $contents[] = JsonObjectCoercion::fix($modelTurn);

        $call = $functionCallPart['functionCall'];
        $result = $this->executeTool($call['name'], $call['args'] ?? []);

        $contents[] = [
            'role' => 'user',
            'parts' => [[
                'functionResponse' => [
                    'name' => $call['name'],
                    ...($call['id'] ?? null) !== null ? ['id' => $call['id']] : [],
                    'response' => JsonObjectCoercion::fix($result),
                ],
            ]],
        ];
    }

    return [
        'text' => "Maaf, saya butuh bantuan tim kami untuk pertanyaan ini — akan segera dihubungkan.",
        'contents' => $contents,
    ];
}

    private function forceEmptyArraysToObjects(array &$content): void
    {
        if (! isset($content['parts'])) {
            return;
        }

        foreach ($content['parts'] as &$part) {
            if (isset($part['functionCall']['args']) && $part['functionCall']['args'] === []) {
                $part['functionCall']['args'] = new \stdClass();
            }
        }
        unset($part);
    }

    private function executeTool(string $name, array $args): mixed
    {
        $tool = collect($this->tools)->first(fn ($t) => $t->name() === $name);

        if (! $tool) {
            return ['error' => "Unknown tool: {$name}"];
        }

        return $tool->execute($args);
    }
}

class JsonObjectCoercion
{
    /**
     * Gemini's proto-JSON parser is strict about object vs array. PHP
     * can't distinguish an empty JSON object {} from an empty array []
     * once decoded — both become []. Anywhere a value is round-tripped
     * back to Gemini (echoed functionCall args, tool results), an
     * empty array must be forced back into an object, or the next
     * request in the conversation gets rejected with a cryptic
     * "Unknown name" parse error.
     *
     * Recurses so nested empty arrays (e.g. a tool result containing
     * an empty "filters" sub-object) are also caught, not just the
     * top level.
     */
    public static function fix(mixed $value): mixed
    {
        if ($value === []) {
            return new \stdClass();
        }

        if (is_array($value)) {
            $isList = array_is_list($value);

            foreach ($value as $key => $item) {
                $value[$key] = self::fix($item);
            }

            // A non-empty list (real JSON array) stays an array.
            // A non-empty associative array (real JSON object) also
            // stays an array — json_encode already handles that
            // correctly; only the empty case needed forcing above.
            return $isList ? $value : $value;
        }

        return $value;
    }
}



