<?php

namespace App\Console\Commands\Experiment;

enum ExperimentType: string
{
    case EMBEDDINGS = 'embeddings';
    case FULLTEXT = 'fulltext';
}
