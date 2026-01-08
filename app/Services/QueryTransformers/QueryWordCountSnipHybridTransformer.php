<?php

namespace App\Services\QueryTransformers;

use App\Services\QueryTransformers\QueryTransformerInterface;
use Illuminate\Database\Eloquent\Collection;

class QueryWordCountSnipHybridTransformer implements QueryTransformerInterface
{

    /**
     * @inheritDoc
     */
    public function transform(string $query, Collection $results): array
    {
        return $this->getTransformer($query)->transform($query, $results);
    }

    private function getTransformer(string $query): QueryTransformerInterface
    {
        $count = count(explode(' ', $query));
        return match (true) {
            $count <= 2 => new ChatbotAddWordsQueryTransformer(),
            $count <= 4 => new ChatbotParaphraseQueryTransformer(),
            default => new ChatbotRemoveWordsQueryTransformer()
        };
    }
}
