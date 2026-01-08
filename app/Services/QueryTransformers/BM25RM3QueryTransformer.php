<?php

namespace App\Services\QueryTransformers;

use App\Services\BM25RM3Service;
use Illuminate\Database\Eloquent\Collection;

class BM25RM3QueryTransformer implements QueryTransformerInterface
{

    /**
     * @param string $query
     * @param Collection $results
     * @inheritDoc
     */
    public function transform(string $query, ?Collection $results = null): array
    {
        $options = BM25RM3Service::getSuggestions($query);
        return array_map(fn ($item) => "$query $item", $options);
    }
}
