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

class ProcessExperimentResults extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'experiment:result {file}';

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
        $file = $this->argument('file');
        $this->processFile($file);
    }

    public function processFile(string $file): void
    {
        $scores = [];
        $results = explode("\n", \Storage::disk('local')->get($file));
        $groupCounts = [];
        foreach ($results as $index => $line) {
            $data = json_decode($line, true, JSON_THROW_ON_ERROR);
            if (!$data) {
                continue;
            }
            $complexity = $this->getQueryComplexity($index);
            $countBucket = $this->getCountBucket($data['scores']['count']);
            $queryLengthBucket = $this->getWordCountBucket($data['query']);
            $toFill = ['total', $complexity, $countBucket, $queryLengthBucket];
            $type = $data['type'];
            foreach ($toFill as $group) {
                $groupCounts[$type][$group] = ($groupCounts[$type][$group] ?? 0) + 1;
            }
            $baseScore = $data['scores']['score10'];
            $resultScores = array_map(fn ($suggestion) => $suggestion['scores']['score10'], $data['suggestions']);
            if (!count($resultScores)) {
                continue;
            }
            foreach ($toFill as $group) {
                $scores[$type][$group]['time'][] = $data['suggestionTime'];
                $scores[$type][$group]['avg3'][] = $this->safeAverage(array_slice($resultScores, 0, 3)) - $baseScore;
                $scores[$type][$group]['avg5'][] = $this->safeAverage(array_slice($resultScores, 0, 5)) - $baseScore;
                $scores[$type][$group]['avg'][] = $this->safeAverage($resultScores) - $baseScore;
                $scores[$type][$group]['top'][] = $resultScores[0] - $baseScore;
                $scores[$type][$group]['pos'][] = max($resultScores) - $baseScore;
                $scores[$type][$group]['filled'] = ($scores[$type][$group]['filled'] ?? 0) + 1;
            }
        }
        foreach ($scores as $type => $score) {
            $output = [];
            $fileName = "processed_results/$type/$file";
            $groups = ['total', 'very_small', 'small', 'medium', 'large', 'not_enough', 'enough', 'a_lot', 'too_much', 's', 'm', 'l'];
            $metricTypes = ['time', 'avg3', 'avg5', 'avg', 'top', 'pos'];
            foreach ($groups as $group) {
                $complexityData = $score[$group] ?? [];
                if (isset($groupCounts[$type][$group])) {
                    $output[$group]['filled'] = (int)(100.0 * ($complexityData['filled'] ?? 0) / $groupCounts[$type][$group]);
                    $output[$group]['count'] = $groupCounts[$type][$group];
                } else {
                    $output[$group]['filled'] = null;
                    $output[$group]['count'] = null;
                }
                foreach ($metricTypes as $metricType) {
                    $values = $complexityData[$metricType] ?? null;
                    if (!$values) {
                        $output[$group][$metricType] = null;
                        continue;
                    }
                    $output[$group][$metricType] = $this->safeAverage($values);
                }
            }
            \Storage::disk('local')->put($fileName, json_encode($output, JSON_PRETTY_PRINT));
        }
    }

    private function getQueryComplexity(int $index): string
    {
        return match (true) {
            $index < 50 => 'very_small',
            $index < 100 => 'small',
            $index < 150 => 'medium',
            $index < 200 => 'large',
            default => throw new \Exception('To many results detected'),
        };
//        $reminder = $index % 8;
//        return match ($reminder) {
//            0, 1 => 'very_small',
//            2, 3 => 'small',
//            4, 5 => 'medium',
//            6, 7 => 'large'
//        };
    }

    private function getCountBucket(int $count): string
    {
        return match (true) {
            $count <= 25 => 'not_enough',
            $count <= 75 => 'enough',
            $count <= 200 => 'a_lot',
            default => 'too_much'
        };
    }

    private function getWordCountBucket(string $query): string
    {
        $count = count(explode(' ', $query));
        return match (true) {
            $count <= 2 => 's',
            $count <= 4 => 'm',
            default => 'l'
        };
    }

    private function safeAverage(array $data): float
    {
        if (!count($data)) {
            return 0.0;
        }
        return array_sum($data) / count($data);
    }
}
