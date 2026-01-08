<?php

namespace App\Services\QueryTransformers;

use App\Services\ChatbotService;
use App\Services\QueryTransformers\QueryTransformerInterface;
use Illuminate\Database\Eloquent\Collection;

class ChatbotRemoveWordsQueryTransformer implements QueryTransformerInterface
{

    /**
     * @inheritDoc
     */
    public function transform(string $query, ?Collection $results = null): array
    {
        $service = new ChatbotService();
        return $service->query(
            'Please generate suggestions for removing parts of the following search query',
            $query,
            ['do not alter the existing words in the query', 'you can remove any words in any place in the query']
        );
    }
}
