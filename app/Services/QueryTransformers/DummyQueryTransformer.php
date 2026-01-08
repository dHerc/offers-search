<?php

namespace App\Services\QueryTransformers;

use Illuminate\Database\Eloquent\Collection;

class DummyQueryTransformer implements QueryTransformerInterface
{
    public function transform(string $query, Collection $results): array
    {
        return [$query];
    }
}
