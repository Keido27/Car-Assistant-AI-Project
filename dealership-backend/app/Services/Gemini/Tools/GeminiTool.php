<?php

namespace App\Services\Gemini\Tools;

interface GeminiTool
{
    /**
     * Must match the "name" key in declaration() exactly — this is how
     * ToolCallingConversation maps a functionCall back to this instance.
     */
    public function name(): string;

    /**
     * Gemini function declaration (a subset of OpenAPI schema) describing
     * this tool to the model — name, description, and parameter shape.
     */
    public function declaration(): array;

    /**
     * Run the tool with whatever args Gemini supplied and return a
     * JSON-serializable result to send back as the functionResponse.
     */
    public function execute(array $args): array;
}