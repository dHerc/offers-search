<?php

namespace App\Console\Commands\Experiment;

use App\Services\QueryTransformers\QueryTransformerInterface;

class ExperimentResult extends ExperimentQueryResult
{
    public function __construct(
        public ExperimentType $type,
        public string $method,
        string $query,
        ExperimentScores $scores,
        int $queryTime,
        public int $suggestionTime,
        public int $fullTime,
        public array $suggestions = [],
    )
    {
        parent::__construct($query, $scores, $queryTime);
    }
}
