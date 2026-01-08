<?php

namespace App\Services\QueryTransformers;

use App\Models\Offer;
use App\Services\QueryService;
use Illuminate\Database\Eloquent\Collection;

class RemoveWordQueryTransformer implements QueryTransformerInterface
{

    /**
     * @param string $query
     * @param Collection $results
     * @return string[]
     */
    public function transform(string $query, Collection $results): array
    {

        $queries = [];
        foreach ($this->getWordsToRemove($query, $results) as $word) {
            $offset = 0;
            while (($position = strpos($query, $word, $offset)) !== false) {
                $start = substr($query, 0, $position);
                $end = str_replace($word, '', substr($query, $position));
                $queries[] = trim(str_replace('  ', ' ', $start . $end));
                $offset = $position + 1;
            }
        }
        return array_filter($queries, fn ($query) => !empty(trim($query)));
    }

    /**
     * @param string $query
     * @param Collection<Offer> $results
     * @return string[]
     */
    private function getWordsToRemove(string $query, Collection $results): array {
        $words = array_unique(explode(' ', $query));
        $wordsWithRelevance = [];
        foreach ($words as $word) {
            $relevance = $this->countOccurrences($results, $word);
            $wordsWithRelevance[] = [
                'word' => $word,
                'relevance' => $relevance
            ];
        }
        usort($wordsWithRelevance, function ($a, $b) {
            if ($a['relevance'] == $b['relevance']) {
                return 0;
            }
            return $a['relevance'] > $b['relevance'] ? -1 : 1;
        });
        return array_map(fn ($word) => $word['word'], $wordsWithRelevance);
    }

    private function countOccurrences(Collection $results, string $word): int
    {
        return $results->reduce(function ($occurrences, Offer $offer) use ($word) {
            $data = $offer->toJson();
            if (str_contains($data, $word)) {
                $occurrences++;
            }
            return $occurrences;
        }, 0);
    }
}
