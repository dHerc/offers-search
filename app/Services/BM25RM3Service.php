<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BM25RM3Service
{
    /**
     * @param string $query
     * @return array<string>
     */
    public static function getSuggestions(string $query): array
    {
        $response = Http::post('localhost:5002', [
            'query' => str_replace(['(', ')'], '', $query)
        ])->getBody()->getContents();
        $queryWords = explode(' ', $query);
        $items = explode(' ', $response);
        $suggestions = [];
        for ($i = 1; $i < count($items); $i++) {
            $data = explode('^', $items[$i]);
            $word = $data[0];
            $relevance = (float)$data[1];
            if (in_array($word, $queryWords)) {
                continue;
            }
            $suggestions[] = [
                'word' => $word,
                'relevance' => $relevance
            ];
        }
        usort($suggestions, self::sortByRelevance(...));
        return array_map(fn ($item) => $item['word'], $suggestions);
    }

    private static function sortByRelevance($a, $b) {
        return $a['relevance'] - $b['relevance'];
    }
}
