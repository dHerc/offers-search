<?php

namespace App\Services;

use Google\Client;

class GoogleService
{
    private const PASSAGE_LENGTH = 256;

    /**
     * @param string[] $offers
     * @return array<array{'id': string, 'content': string, 'score': float}>
     */
    public static function rankStrings(string $query, array $offers): array
    {
        if (!$offers) {
            return [];
        }
        $client = new Client();
        $client->useApplicationDefaultCredentials();
        $httpClient = $client->authorize();
        $projectId = getenv('GOOGLE_PROJECT_ID');
        $records = array_merge(...self::buildRecords($offers));
        $response = $httpClient->post(
            "https://discoveryengine.googleapis.com/v1/projects/$projectId/locations/global/rankingConfigs/default_ranking_config:rank",
            [
                'body' => json_encode([
                    'model' => 'semantic-ranker-default@latest',
                    'query' => $query,
                    'records' => $records
                ])
            ]
        );
        $data = json_decode($response->getBody()->getContents(), true);
        return $data['records'];
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
}
