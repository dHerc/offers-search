<?php

namespace App\Services;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

class EmbeddingService
{
    public static function generateEmbeddings(string $text): string
    {
        return Http::post('localhost:5000', [
            'sentences' => [$text]
        ])->getBody()->getContents();
    }

    public static function generateBatchEmbeddings(array $texts): ?array
    {
        $response = Http::timeout(240)->post('localhost:5000', [
            'sentences' => $texts
        ]);
        if (!$response->successful()) {
            return null;
        }
        return explode("\n", $response->getBody()->getContents());
    }

    public static function generateRandomBatchEmbeddings(array $texts): ?array {
        return array_map(function () {
            return implode(',', array_map(
                function () {
                    $mult = rand(0, 1) ? 1 : -1;
                    $val = $mult * 2 * (mt_rand() / mt_getrandmax());
                    return (string)($val);
                } ,
                array_fill(0, 1024, null)
            ));
        }, $texts);
    }
}
