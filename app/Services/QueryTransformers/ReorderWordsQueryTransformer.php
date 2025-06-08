<?php

namespace App\Services\QueryTransformers;

use Illuminate\Database\Eloquent\Collection;

class ReorderWordsQueryTransformer implements QueryTransformerInterface
{
    private const int QUERIES_TO_PICK = 10;
    private const int CHUNK_SIZE = 5;

    public function transform(string $query, ?Collection $results = null): array
    {
        $parts = explode(" ", $query);
        $chunks = array_chunk($parts, self::CHUNK_SIZE);
        $options = $this->generatePermutations($parts);
        $queries = array_map(function ($queryParts) {
            return implode(' ', $queryParts);
        }, $options);
        $validQueries = array_values(array_filter($queries, function ($query) use ($parts) {
            return $query != implode(' ', $parts);
        }));
        if (count($validQueries) < self::QUERIES_TO_PICK) {
            return $validQueries;
        }
        $pickedKeys = array_rand($validQueries, self::QUERIES_TO_PICK);
        $pickedQueries = [];
        foreach ($pickedKeys as $pickedKey) {
            $pickedQueries[] = $validQueries[$pickedKey];
        }
        return $pickedQueries;
    }

    /**
     * @param string[] $parts
     * @return string[][]
     */
    private function generatePermutations(array $parts): array
    {
        if (count($parts) < 2) {
            return [$parts];
        }
        $permutations = [];
        foreach ($parts as $part) {
            $others = $this->removeFromArray($parts, $part);
            foreach ($this->generatePermutations($others) as $otherPermutation) {
                $permutations[] = [$part, ...$otherPermutation];
            }
        }
        return $permutations;
    }

    /**
     * @param string[] $items
     * @param string $item
     * @return string[]
     */
    private function removeFromArray(array $items, string $item): array {
        $pos = array_search($item, $items);
        if ($pos !== false) {
            array_splice($items, $pos, 1);
        }
        return $items;
    }
}
