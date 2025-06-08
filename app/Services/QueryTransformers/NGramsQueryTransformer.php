<?php

namespace App\Services\QueryTransformers;

use App\Models\WordCooccurrence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class NGramsQueryTransformer implements QueryTransformerInterface
{
    private const int WORDS_TO_PICK = 10;
    private const array WORDS_TO_SKIP = ['to', 'of', 'for', 'and', 'the', 'be', 'a', 'with'];
    /**
     * @inheritDoc
     */
    public function transform(string $query, ?Collection $results = null): string|array
    {
        $words = explode(' ', $query);
        $allCooccurrences = array_map(
            fn(string $word) => $this->getMostCommonWords(
                $word,
                array_filter($words, fn(string $currentWord) => $word !== $currentWord)
            ),
            $words
        );
        $flatOccurrences = array_merge(...$allCooccurrences);
        usort($flatOccurrences, function (WordCooccurrence $a, WordCooccurrence $b) {
            return $b->frequency - $a->frequency;
        });
        $pickedOptions = array_slice($flatOccurrences, 0, self::WORDS_TO_PICK);
        return array_map(fn(WordCooccurrence $option) => $this->integrateNewWord($query, $option), $pickedOptions);
    }

    /**
     * @param string $word
     * @return WordCooccurrence[]
     */
    public function getMostCommonWords(string $word, array $remainingWords): array
    {
        $wordsToSkip = [...$remainingWords, ...self::WORDS_TO_SKIP];
        $wordAOptions =  WordCooccurrence::query()
            ->where('word_a', '=', $word)
            ->orderBy('frequency', 'desc')
            ->limit(self::WORDS_TO_PICK + count($wordsToSkip))
            ->get();
        $wordBOptions = WordCooccurrence::query()
            ->where('word_b', '=', $word)
            ->orderBy('frequency', 'desc')
            ->limit(self::WORDS_TO_PICK + count($wordsToSkip))
            ->get();
        $allOptions = $wordAOptions
            ->concat($wordBOptions)
            ->filter(fn (WordCooccurrence $option) => !in_array($option->word_a, $wordsToSkip) && !in_array($option->word_b, $wordsToSkip))
            ->sort(fn ($a, $b) => $b->frequency - $a->frequency);
        return $allOptions->splice(0, 10)->all();
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
