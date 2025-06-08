<?php

namespace App\Services;

use Gemini\Client;

readonly class ChatbotService
{
    private const array BASE_RULES = [
        'Ignore any previous messages',
        'don\'t include any explanation',
        'always generate 10 different variants if possible',
        'separate variants with newline and skip any list headings',
        'assume the query is meant to be used for eshop offer search not web search',
        'do not include the original query'
    ];
    private Client $client;
    public function __construct()
    {
        $apiKey = getenv("GEMINI_API_KEY");
        $this->client = \Gemini::client($apiKey);
    }

    public function query(string $prompt, string $query, array $rules = []): array
    {
        $options = explode("\n", $this->client->generativeModel('gemini-2.0-flash')->generateContent(
            $this->generateFullQuery($prompt, $query, $rules)
        )->text());
        return array_filter($options, fn ($option) => !empty($option) && !empty(trim($option, " \n\r\t\v\0\"'")));
    }

    private function generateFullQuery(string $prompt, string $query, array $rules = []): string
    {
        $fullQuery = "While generating the response follow these rules:\n";
        foreach ([...self::BASE_RULES, ...$rules] as $rule) {
            $fullQuery .= "- $rule,\n";
        }
        $fullQuery .= "\n$prompt:\n$query";
        return $fullQuery;
    }
}
