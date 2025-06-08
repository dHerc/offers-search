<?php

namespace App\Services;

use App\Models\Offer;

class ScoringService
{
    private const PERFECT_COUNT = 50;
    private const RELEVANT_DOCUMENTS_COUNT = 10;
    /**
     * @param Offer[] $offers
     */
    public static function scoreResults(string $query, array $offers, int $count): float
    {
        $offerStrings = array_map(function (Offer $offer) {
            return implode('\n', $offer->toArray());
        }, $offers);
        return (
            self::getCountScore($count) +
            self::getInternalRelevancyScore($query, $offers) +
            self::getExternalRelevancyScore($query, $offerStrings)
        ) / 3;
    }

    private static function getCountScore(int $count): float
    {
        if ($count <= self::PERFECT_COUNT) {
            return ((float)$count) / self::PERFECT_COUNT;
        }
        if ($count <= 200) {
            return sqrt(((float)self::PERFECT_COUNT) / $count);
        }
        return 0.5;
    }

    /**
     * @param string $query
     * @param Offer[] $offers
     * @return float
     */
    private static function getInternalRelevancyScore(string $query, array $offers): float
    {
        if (!count($offers)) {
            return 0;
        }
        $relevantOffers = array_slice($offers, 0, self::RELEVANT_DOCUMENTS_COUNT);
        $scores = array_map(fn ($offer) => max(0, 1 - $offer->cosine_distance), $relevantOffers);
        return array_sum($scores) / count($scores);
    }

    /**
     * @param string[] $offers
     */
    private static function getExternalRelevancyScore(string $query, array $offers): float
    {
        return 0;
        if (!count($offers)) {
            return 0;
        }
        $relevantOffers = array_slice($offers, 0, self::RELEVANT_DOCUMENTS_COUNT);
        $scores = self::extractRelevance(GoogleService::rankStrings($query, $relevantOffers));
        return array_sum($scores) / count($scores);
    }

    private static function extractRelevance(array $records): array
    {
        $relevances = [];
        foreach ($records as $record) {
            $documentId = explode('-', $record['id'])[0];
            $relevance = $record['score'];
            $currentRelevance = $relevances[$documentId] ?? 0;
            $relevances[$documentId] = max($currentRelevance, $relevance);
        }
        return array_values($relevances);
    }
}
