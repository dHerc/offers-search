<?php

namespace App\Services\QueryTransformers;

use App\Models\Offer;
use App\Services\FlanT5Service;
use App\Services\GoogleService;
use Illuminate\Database\Eloquent\Collection;

class FlanPRFQueryTransformer implements QueryTransformerInterface
{
    private const RELEVANT_DOCUMENTS_COUNT = 10;
    private const FINAL_PASSAGE_COUNT = 3;

    public function transform(string $query, Collection $results): string|array
    {
        $context = $this->prepareContext($query, $results);
        return FlanT5Service::getSuggestion($query, $context);
    }

    private function prepareContext(string $query, Collection $results): array
    {
        $relevantResults = $results->slice(0, self::RELEVANT_DOCUMENTS_COUNT);
        $offerStrings = $relevantResults->map($this->buildOfferString(...))->toArray();
        return $this->getTopPassages($query, $offerStrings);
    }

    private function buildOfferString(Offer $offer): string
    {
        $parts = [];
        if ($offer->category) {
            $parts[] = $offer->category;
        }
        if ($offer->title) {
            $parts[] = $offer->title;
        }
        if ($offer->description) {
            $parts[] = $offer->description;
        }
        if ($offer->features) {
            $parts[] = $offer->features;
        }
        return implode("\n", $parts);
    }

    private function getTopPassages(string $query, array $offerStrings): array
    {
        $scores = GoogleService::rankStrings($query, $offerStrings);
        $contents = array_map(fn ($score) => $score['content'], $scores);
        return array_slice($contents, 0, self::FINAL_PASSAGE_COUNT);
    }
}
