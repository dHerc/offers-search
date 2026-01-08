<?php

namespace App\Services;

use Google\Client;

class GoogleService
{
    private const PASSAGE_LENGTH = 256;

    /**
     * @param string[] $offers
     * @return float[]
     */
    public static function getStringsScores(string $query, array $offers): array
    {
        $data = self::rankStrings($query, $offers);
        return self::extractRelevance($data);
    }
    /**
     * @param string[] $offers
     * @return array{'id': string, 'content': string, 'score': float}[]
     */
    public static function rankStrings(string $query, array $offers): array
    {
        if (!$offers) {
            return [];
        }
        $records = array_merge(...self::buildRecords($offers));
        $client = new Client();
        $client->useApplicationDefaultCredentials();
        $httpClient = $client->authorize();
        $projectId = getenv('GOOGLE_PROJECT_ID');
        $batches = array_chunk($records, 1000);
        $results = [];
        foreach ($batches as $batch) {
            $response = $httpClient->post(
                "https://discoveryengine.googleapis.com/v1/projects/$projectId/locations/global/rankingConfigs/default_ranking_config:rank",
                [
                    'body' => json_encode([
                        'model' => 'semantic-ranker-default@latest',
                        'query' => $query,
                        'records' => $batch
                    ])
                ]
            );
            array_push(
                $results,
                ...json_decode($response->getBody()->getContents(), true)['records']
            );
        }
        usort($results, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                return 0;
            }
            return ($a['score'] > $b['score']) ? -1 : 1;
        });
        return $results;
    }

    private static function buildRecords(array $offers): array
    {
        $data = [];
        foreach ($offers as $index => $offer) {
            $passages = self::splitToPassages($offer);
            $data[$index] = [];
            foreach ($passages as $pIndex => $passage) {
                $data[$index][$pIndex] = [
                    "id" => "$index-$pIndex",
                    "content" => $passage
                ];
            }
        }
        return $data;
    }

    /**
     * @param string $text
     * @return string[]
     */
    private static function splitToPassages(string $text): array
    {
        if (!strlen($text)) {
            return [];
        }
        $passages = [];
        $words = explode(' ', $text);
        for ($i = 0; $i < count($words); $i += self::PASSAGE_LENGTH / 2) {
            $passages[] = implode(' ', array_slice($words, $i, self::PASSAGE_LENGTH));
        }
        if (!count($passages)) {
            return [$text];
        }
        return $passages;
    }

    /**
     * @param array{'id': string, 'content': string, 'score': float}[] $records
     * @return array
     */
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
