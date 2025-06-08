<?php

namespace App\Console\Commands\Experiment;

class ExperimentModificationResult
{
    public function __construct(
        public string $query,
        public float $score,
        public int $queryTime,
    )
    {
    }
}
