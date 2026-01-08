<?php

namespace App\Services\QueryTransformers;

use App\Models\WordCooccurrence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class NGramsQueryTransformer implements QueryTransformerInterface
{
    //TODO restore
    private const int WORDS_TO_PICK = 10;
    private const array WORDS_TO_SKIP = ['to', 'of', 'for', 'and', 'the', 'be', 'a', 'with', 'in'];
    /**
     * @inheritDoc
     */
    public function transform(string $query, ?Collection $results = null): array
    {
        $words = explode(' ', $query);
        $allCooccurrences = $this->getMostCommonWords(array_filter($words, fn ($word) => !in_array($word, self::WORDS_TO_SKIP)));
        return array_map(fn(WordCooccurrence $option) => $this->integrateNewWord($query, $option), $allCooccurrences);
    }

    /**
     * @return WordCooccurrence[]
     */
    public function getMostCommonWords(array $words): array
    {
        $combinations = $this->getAmountOfCombinations(count($words));
        $wordAOptions =  WordCooccurrence::query()
            ->whereIn('word_a', $words)
            ->orderBy('frequency', 'desc')
            ->limit(self::WORDS_TO_PICK + $combinations + count(self::WORDS_TO_SKIP) * count($words))
            ->get();
        $wordBOptions = WordCooccurrence::query()
            ->whereIn('word_b', $words)
            ->orderBy('frequency', 'desc')
            ->limit(self::WORDS_TO_PICK + $combinations + count(self::WORDS_TO_SKIP) * count($words))
            ->get();
        $allOptions = $wordAOptions
            ->concat($wordBOptions)
            ->filter(fn (WordCooccurrence $option) => !(in_array($option->word_a, $words) && in_array($option->word_b, $words)))
            ->filter(fn (WordCooccurrence $option) => !in_array($option->word_a, self::WORDS_TO_SKIP) && !in_array($option->word_b, self::WORDS_TO_SKIP))
            ->sort(fn ($a, $b) => $b->frequency - $a->frequency);
        return $allOptions->splice(0, self::WORDS_TO_PICK)->all();
    }

    private function getAmountOfCombinations(int $count)
    {
        if ($count === 1) {
            return 1;
        }
        return ($this->factorial($count) / ($this->factorial(2) * $this->factorial($count - 2)));
    }

    private function factorial(int $n)
    {
        if ($n <= 0) {
            return 1;
        }
        return $n * $this->factorial($n - 1);
    }

    private function integrateNewWord(string $query, WordCooccurrence $data): string
    {
        $word = $data->word_a;
        $otherWord = $data->word_b;
        $pos = strpos($query, $data->word_a);
        if ($pos === false) {
            $word = $data->word_b;
            $otherWord = $data->word_a;
            $pos = strpos($query, $data->word_b);
        }
        if ($pos === false) {
            throw new \Exception('Generated a suggestion for word not present in query');
        }
        if ($data->after) {
            $wordEndPos = $pos + strlen($word);
            return substr($query, 0, $wordEndPos) . " $otherWord" . substr($query, $wordEndPos);
        }
        return substr($query, 0, $pos) . "$otherWord " . substr($query, $pos);
    }
}
