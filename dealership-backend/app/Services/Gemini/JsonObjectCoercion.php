<?php

namespace App\Services\Gemini;

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
     *
     * Note: this can't distinguish a field that's semantically a list
     * (e.g. SearchInventoryTool's "cars") from one that's semantically
     * an object — an empty "cars" array gets coerced to {} the same as
     * an empty "filters" object would. Gemini's Struct/Value encoding
     * accepts either for an empty collection, so this hasn't caused a
     * parse error, but keep it in mind if a future reasoning-quality
     * pass shows the model misreading an empty list as an empty object.
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
