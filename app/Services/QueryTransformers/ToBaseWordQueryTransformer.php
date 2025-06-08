<?php

namespace App\Services\QueryTransformers;

use Illuminate\Database\Eloquent\Collection;
use writecrow\Lemmatizer\Lemmatizer;

class ToBaseWordQueryTransformer implements QueryTransformerInterface
{

    public function transform(string $query, ?Collection $results = null): array
    {
        $words = explode(' ', $query);
        $baseWords = array_map(function ($word) {
            return Lemmatizer::getLemma(mb_strtolower($word));
        }, $words);
        $result = implode(' ', $baseWords);
        if ($result == $query) {
            return [];
        }
        return [$result];
    }
}
