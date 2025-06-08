<?php

namespace App\Services\QueryTransformers;

use App\Models\Offer;
use Illuminate\Database\Eloquent\Collection;

class SnippetsQueryTransformer implements QueryTransformerInterface
{
    private const int WORDS_TO_PICK = 10;
    private const array WORDS_TO_SKIP = ['to', 'of', 'for', 'and', 'the', 'be', 'a', 'with'];
    /**
     * @inheritDoc
     */
    public function transform(string $query, Collection $results): string|array
    {
        $queryWords = explode(' ', $query);
        $totalWords = [];
        foreach ($results->toBase() as $offer) {
            $offerWords = $this->countWords($offer);
            foreach ($offerWords as $word => $count) {
                $totalWords[$word] = ($totalWords[$word] ?? 0) + $count;
            }
        }
        $wordsData = [];
        foreach ($totalWords as $word => $count) {
            $wordsData[] = [
                'word' => $word,
                'count' => $count,
            ];
        }
        $filteredWordsData = array_filter($wordsData, fn($word) => !in_array($word['word'], $queryWords) && !in_array($word['word'], self::WORDS_TO_SKIP));
        usort($filteredWordsData, fn($a, $b) => $b['count'] - $a['count']);
        $pickedWords = array_slice($filteredWordsData, 0, self::WORDS_TO_PICK);
        return array_map(fn($word) => $query . ' ' . $word['word'], $pickedWords);
    }

    /**
     * @param Offer $offer
     * @return array<string, int>
     */
    private function countWords(Offer $offer): array
    {
        $words = [];
        foreach ($this->splitToFixedWords($offer->category) as $word) {
            $words[$word] = ($words[$word] ?? 0) + 1;
        }
        foreach ($this->splitToFixedWords($offer->features) as $word) {
            $words[$word] = ($words[$word] ?? 0) + 1;
        }
        foreach ($this->splitToFixedWords($offer->description) as $word) {
            $words[$word] = ($words[$word] ?? 0) + 1;
        }
        foreach ($this->splitToFixedWords($offer->title) as $word) {
            $words[$word] = ($words[$word] ?? 0) + 1;
        }
        return $words;
    }

    private function splitToFixedWords(string $field): array
    {
        $base = explode(' ', $field);
        $fixedWords = array_map(fn($word) => trim(mb_strtolower(preg_replace('/[^\p{L}0-9]/', '', $word))), $base);
        return array_filter($fixedWords, fn($word) => strlen($word));
    }
}
