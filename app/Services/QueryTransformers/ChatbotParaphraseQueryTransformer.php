<?php

namespace App\Services\QueryTransformers;

use App\Services\ChatbotService;
use App\Services\QueryTransformers\QueryTransformerInterface;
use Illuminate\Database\Eloquent\Collection;

class ChatbotParaphraseQueryTransformer implements QueryTransformerInterface
{

    /**
     * @inheritDoc
     */
    public function transform(string $query, ?Collection $results = null): array
    {
        $service = new ChatbotService();
        return $service->query(
            'Please rephrase the following search query',
            $query,
            ['make each variant increasingly differ from base query', 'avoid reorganizing the words']
        );
    }
}
