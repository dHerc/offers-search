<?php

namespace App\Console\Commands\Experiment;

readonly class ExperimentScores
{
    /**
     * @param float[] $similarities
     * @param float[] $fitness
     * @param int[] $offers
     */
    public function __construct(
        public float $score1,
        public float $score3,
        public float $score5,
        public float $score10,
        public float $partialScore50,
        public float $partialScore100,
        public array $similarities,
        public array $fitness,
        public array $offers,
        public int $count
    )
    {
    }
}
