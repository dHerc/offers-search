<?php

namespace App\Services;

use App\Console\Commands\Experiment\ExperimentScores;
use App\Models\Offer;

class ScoringService
{
    private const PERFECT_COUNT = 50;
    private const EXTERNAL_RELEVANT_DOCUMENTS_COUNT = 10;
    private const INTERNAL_RELEVANT_DOCUMENTS_COUNT = 100;
    /**
     * @param Offer[] $offers
     */
    public static function scoreResults(string $query, array $offers): ExperimentScores
    {
        $count = count($offers);
        $internalScores = self::getInternalRelevancyScores($offers);
        $externalScores = self::getExternalRelevancyScores($query, $offers);
        return new ExperimentScores(
            self::getPartialScores(1, $count, $internalScores, $externalScores),
            self::getPartialScores(3, $count, $internalScores, $externalScores),
            self::getPartialScores(5, $count, $internalScores, $externalScores),
            self::getPartialScores(10, $count, $internalScores, $externalScores),
            self::getPartialScores(50, $count, $internalScores, $externalScores),
            self::getPartialScores(100, $count, $internalScores, $externalScores),
            $internalScores,
            $externalScores,
            array_map(fn(Offer $offer) => $offer->id, $offers),
            $count,
        );
    }

    private static function getPartialScores(int $relevantOffers, int $count, array $internalScores, array $externalScores): float
    {
        if ($relevantOffers > self::EXTERNAL_RELEVANT_DOCUMENTS_COUNT) {
            return (
                self::getCountScore($count) +
                self::getSafePartialAverage($internalScores, $relevantOffers)
            ) / 2;
        }
        return (
            self::getCountScore($count) +
            self::getSafePartialAverage($internalScores, $relevantOffers) +
            self::getSafePartialAverage($externalScores, $relevantOffers)
        ) / 3;
    }

    private static function getCountScore(int $count): float
    {
        if ($count <= self::PERFECT_COUNT) {
            return ((float)$count) / self::PERFECT_COUNT;
        }
        if ($count <= 4 * self::PERFECT_COUNT) {
            return sqrt(((float)self::PERFECT_COUNT) / $count);
        }
        return 0.5;
    }

    /**
     * @param Offer[] $offers
     * @return float[]
     */
    private static function getInternalRelevancyScores(array $offers): array
    {
        $relevantOffers = array_slice($offers, 0, self::INTERNAL_RELEVANT_DOCUMENTS_COUNT);
        return array_map(fn ($offer) => max(0, 1 - $offer->cosine_distance), $relevantOffers);
    }

    /**
     * @param Offer[] $offers
     * @return float[]
     */
    private static function getExternalRelevancyScores(string $query, array $offers): array
    {
        if (!count($offers)) {
            return [];
        }
        $offerStrings = array_map(function (Offer $offer) {
            return implode('\n', $offer->toArray());
        }, $offers);
        $relevantOffers = array_slice($offerStrings, 0, self::EXTERNAL_RELEVANT_DOCUMENTS_COUNT);
        //TODO remove
        return array_map(fn() => 0.0, $relevantOffers);
        return GoogleService::getStringsScores($query, $relevantOffers);
    }

    private static function getSafePartialAverage(array $scores, int $relevantOffers): float
    {
        if (count($scores) === 0) {
            return 0;
        }
        $count = min($relevantOffers, count($scores));
        return array_sum(array_slice($scores, 0, $relevantOffers)) / $count;
    }
}
