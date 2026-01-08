<?php

namespace App\Console\Commands;

use App\Console\Commands\Experiment\ExperimentQueryResult;
use App\Console\Commands\Experiment\ExperimentResult;
use App\Services\QueryService;
use App\Services\QueryTransformers\BM25RM3QueryTransformer;
use App\Services\QueryTransformers\ChatbotAddWordsQueryTransformer;
use App\Services\QueryTransformers\ChatbotParaphraseQueryTransformer;
use App\Services\QueryTransformers\ChatbotRemoveWordsQueryTransformer;
use App\Services\QueryTransformers\DummyQueryTransformer;
use App\Services\QueryTransformers\FlanPRFQueryTransformer;
use App\Services\QueryTransformers\FlanQRQueryTransformer;
use App\Services\QueryTransformers\NGramsQueryTransformer;
use App\Services\QueryTransformers\QueryTransformerInterface;
use App\Services\QueryTransformers\RemoveWordQueryTransformer;
use App\Services\QueryTransformers\ReorderWordsQueryTransformer;
use App\Services\QueryTransformers\SnippetsQueryTransformer;
use App\Services\QueryTransformers\ToBaseWordQueryTransformer;
use App\Services\ScoringService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

class ProcessAllExperimentResults extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'experiment:results';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run an experiment for provided option';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $files = Storage::disk('local')->files();
        $resultFiles = array_filter($files, fn ($file) => str_contains($file, 'results.ndjson'));
        foreach ($resultFiles as $file) {
            echo "Processing $file\n";
            (new ProcessExperimentResults())->processFile($file);
        }
    }
}
