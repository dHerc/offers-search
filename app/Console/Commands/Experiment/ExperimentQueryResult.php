<?php

namespace App\Console\Commands\Experiment;

class ExperimentQueryResult
{
    public function __construct(
        public string $query,
        public ExperimentScores $scores,
        public int $queryTime,
    )
    {
    }
}
