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

class BuildResultComparison extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'experiment:build-comparison {type} {metric} {hybridMethod}';

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
        $type = $this->argument('type');
        $hybridMethod = $this->argument('hybridMethod');
        $printMetric = $this->argument('metric');
        $files = Storage::disk('local')->files();
        $resultFiles = array_filter($files, fn ($file) => str_contains($file, 'results.ndjson'));
        $nonHybridFiles = array_filter($resultFiles, fn ($file) => !str_contains($file, 'hybrid'));
        $topResults = [];
        foreach ($nonHybridFiles as $file) {
            $methodNameBase = str_replace('-results.ndjson', '', $file);
            $methodNameParts = explode('/', $methodNameBase);
            $methodName = end($methodNameParts);
            $results = explode(PHP_EOL, \Storage::disk('local')->get($file));
            foreach ($results as $index => $result) {
                $queryId = (int)floor($index/2) + 1;
                if (!strlen($result)) {
                    continue;
                }
                $data = json_decode($result, true, JSON_THROW_ON_ERROR);
                if ($data['type'] !== $type) {
                    continue;
                }
                $this->processRecord($queryId, $methodName, $data['suggestions'], $data['scores']['score10'], $topResults);
            }
        }
        $hybridTopResults = [];
        $queries = [];
        $resultCounts = [];
        $hybridMethodResults = explode(PHP_EOL, Storage::disk('local')->get("hybrid_$hybridMethod-results.ndjson"));
        foreach ($hybridMethodResults as $index => $hybridMethodResult) {
            if (!strlen($hybridMethodResult)) {
                continue;
            }
            $queryId = (int)floor($index/2) + 1;
            $data = json_decode($hybridMethodResult, true, JSON_THROW_ON_ERROR);
            $query = $data['query'];
            $queries[$queryId] = $query;
            $resultCounts[$queryId] = count($data['scores']['offers']);
            $this->processRecord($queryId, $hybridMethod, $data['suggestions'], $data['scores']['score10'], $hybridTopResults);
        }
        foreach ($queries as $queryId => $query) {
            //echo "$queryId: $query\n";
        }
        foreach ($queries as $queryId => $query) {
            $topResult = $topResults[$queryId][$printMetric];
            $topMethod = $topResult['method'];
            $topValue = $topResult['value'] * 100;
            $topHybridValue = $hybridTopResults[$queryId][$printMetric]['value'] * 100;
            if ($topMethod === $this->getUsedMethodName($hybridMethod, $resultCounts[$queryId], $query)) {
                continue;
            }
            if ($topValue < $topHybridValue) {
                continue;
            }
            $topHybridValueText = number_format($topHybridValue, 1);
            $topValueText = number_format($topValue, 1);
            echo "$queryId & $topHybridValueText & $topValueText & $topMethod \\\\ \hline \n";
        }
    }

    private function processRecord(int $queryId, string $methodName, array $suggestions, float $baseScore, array &$results): void
    {
        $metrics = [
            'avg3' => fn(array $resultScores) => $this->safeAverage(array_slice($resultScores, 0, 3)) - $baseScore,
            'avg5' => fn(array $resultScores) => $this->safeAverage(array_slice($resultScores, 0, 5)) - $baseScore,
            'avg' => fn(array $resultScores) => $this->safeAverage($resultScores) - $baseScore,
            'top' => fn(array $resultScores) => $resultScores[0] - $baseScore,
            'pos' => fn(array $resultScores) => max($resultScores) - $baseScore,
        ];
        foreach ($metrics as $metric => $callback) {
            $currentTop = $results[$queryId][$metric]['value'] ?? -1;
            $scores = array_map(fn (array $suggestion) => $suggestion['scores']['score10'], $suggestions);
            if (count($scores) === 0) {
                continue;
            }
            $metricValue = $callback($scores);
            if ($currentTop < $metricValue) {
                $results[$queryId][$metric] = [
                    'method' => $methodName,
                    'value' => $metricValue
                ];
            }
        }
    }

    private function safeAverage(array $data): float
    {
        if (!count($data)) {
            return 0.0;
        }
        return array_sum($data) / count($data);
    }

    private function getUsedMethodName(string $hybridMethod, int $count, string $query)
    {
        return match ($hybridMethod) {
            'result_count' => $this->getFulltextUsedMethodName($count),
            'word_count_cbp' => $this->getEmbeddingsUsedMethodName($query, 'gemini_paraphrase'),
            'word_count_cbz' => $this->getEmbeddingsUsedMethodName($query, 'gemini_add'),
            'word_count_snip' => $this->getEmbeddingsUsedMethodName($query, 'snippets'),
            default => throw new \Exception('Unknown method used')
        };
    }

    private function getFulltextUsedMethodName(int $count): string
    {
        return match (true) {
            $count <= 25 => 'gemini_remove',
            $count <= 75 => 'snippets',
            $count <= 200 => 'snippets',
            default => 'gemini_add'
        };
    }

    private function getEmbeddingsUsedMethodName(string $query, string $middle): string
    {
        $count = count(explode(' ', $query));
        return match (true) {
            $count <= 2 => 'gemini_add',
            $count <= 4 => $middle,
            default => 'gemini_remove'
        };
    }
}
