<?php

namespace App\Services\QueryTransformers;

use App\Services\QueryTransformers\QueryTransformerInterface;
use Illuminate\Database\Eloquent\Collection;

class ResultCountHybridTransformer implements QueryTransformerInterface
{

    /**
     * @inheritDoc
     */
    public function transform(string $query, Collection $results): array
    {
        return $this->getTransformer(count($results))->transform($query, $results);
    }

    private function getTransformer(int $count): QueryTransformerInterface
    {
        return match (true) {
            $count <= 25 => new ChatbotRemoveWordsQueryTransformer(),
            $count <= 75 => new SnippetsQueryTransformer(),
            $count <= 200 => new SnippetsQueryTransformer(),
            default => new ChatbotAddWordsQueryTransformer()
        };
    }
}
