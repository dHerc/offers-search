<?php

namespace App\Services\QueryTransformers;

use App\Models\Offer;
use Illuminate\Database\Eloquent\Collection;

interface QueryTransformerInterface
{
    /**
     * @param string $query
     * @param Collection<Offer> $results
     * @return string|string[]
     */
    public function transform(string $query, Collection $results): string | array;
}
