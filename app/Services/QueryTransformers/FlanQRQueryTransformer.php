<?php

namespace App\Services\QueryTransformers;

use App\Services\FlanT5Service;
use Illuminate\Database\Eloquent\Collection;

class FlanQRQueryTransformer implements QueryTransformerInterface
{

    public function transform(string $query, ?Collection $results = null): array
    {
        return [FlanT5Service::getSuggestion($query)];
    }
}
