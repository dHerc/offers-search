<?php

namespace App\Console\Commands\Experiment;

use App\Services\QueryTransformers\QueryTransformerInterface;

class ExperimentResult
{
    /**
     * @param 'embedding'|'fulltext' $type
     * @param class-string<QueryTransformerInterface> $method
     * @param string $query
     * @param float $score
     * @param int $queryTime
     * @param int $suggestionTime
     * @param ExperimentModificationResult[] $suggestions
     */
    public function __construct(
        public string $type,
        public string $method,
        public string $query,
        public float $score,
        public int $queryTime,
        public int $suggestionTime,
        public array $suggestions = [],
    )
    {
    }
}
