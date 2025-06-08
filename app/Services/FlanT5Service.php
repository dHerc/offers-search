<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FlanT5Service
{
    public static function getSuggestion(string $query, ?array $context = null)
    {
        $data = [
            'query' => $query
        ];
        if ($context !== null) {
            $data['context'] = implode(', ', $context);
        }
        $response = Http::timeout(180)->post('localhost:5001', $data)->getBody()->getContents();
        return trim(str_replace(['<pad>', '</s>'], '', $response));
    }
}
