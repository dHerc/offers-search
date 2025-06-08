<?php

namespace App\Services;

use App\Models\Offer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class QueryService
{
    public static function getOffers(string $query, int | null $paginate = null): LengthAwarePaginator | Collection
    {
        DB::statement('SET hnsw.ef_search = 250');
        $embeddingVector = '[' . EmbeddingService::generateEmbeddings($query) . ']';
        $baseQuery = Offer::query()->fromSub(
            Offer::query()->selectRaw(
                '*, embeddings <=> ? AS cosine_distance',
                [$embeddingVector]
            )->orderBy('cosine_distance')->limit(1000),
            'offers'
        )->where('cosine_distance', '<=', 0.4);
        if (!$paginate) {
            return $baseQuery->get();
        }
        return $baseQuery->paginate($paginate);
    }

    public static function getOffersByFullText(string $query): Collection
    {
        $fixedQuery = implode(' & ', explode(' ', preg_replace('/[^a-zA-Z0-9-_]/', '', $query)));
        $embeddingVector = '[' . EmbeddingService::generateEmbeddings($query) . ']';
        return Offer::query()->selectRaw(
            '*, embeddings <=> ? AS cosine_distance',
            [$embeddingVector]
        )->whereRaw(
            "to_tsvector('english', category || title || features || description || details) @@ to_tsquery('english', ?)",
            [$fixedQuery]
        )->limit(1000)->get();
    }
}
