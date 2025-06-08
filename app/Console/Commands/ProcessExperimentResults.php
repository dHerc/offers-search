<?php

namespace App\Console\Commands;

use App\Console\Commands\Experiment\ExperimentModificationResult;
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
        $scores = [];
        $results = explode("\n", \Storage::disk('local')->get($file));
        foreach ($results as $index => $line) {
            $complexity = $this->getQueryComplexity($index);
            $data = json_decode($line, true, JSON_THROW_ON_ERROR);
            if (!$data) {
                continue;
            }
            $type = $data['type'];
            $resultScores = array_map(fn ($suggestion) => $suggestion['score'], $data['suggestions']);
            if (!count($resultScores)) {
                continue;
            }
            $scores[$type]['total']['time'][] = $data['suggestionTime'];
            $scores[$type]['total']['avg'][] = $this->safeAverage($resultScores) - $data['score'];
            $scores[$type]['total']['top'][] = $resultScores[0] - $data['score'];
            $scores[$type]['total']['med'][] = $this->safeMedian($resultScores) - $data['score'];
            $scores[$type]['total']['pos'][] = max($resultScores) - $data['score'];
            $scores[$type][$complexity]['time'][] = $data['suggestionTime'];
            $scores[$type][$complexity]['avg'][] = $this->safeAverage($resultScores) - $data['score'];
            $scores[$type][$complexity]['top'][] = $resultScores[0] - $data['score'];
            $scores[$type][$complexity]['med'][] = $this->safeMedian($resultScores) - $data['score'];
            $scores[$type][$complexity]['pos'][] = max($resultScores) - $data['score'];
        }
        foreach ($scores as $type => $score) {
            $output = [];
            $fileName = "processed_results/$type/$file";
            $complexities = ['total', 'very_small', 'small', 'medium', 'large'];
            $metricTypes = ['time', 'avg', 'top', 'pes', 'pos'];
            foreach ($complexities as $complexity) {
                $complexityData = $score[$complexity] ?? [];
                foreach ($metricTypes as $metricType) {
                    $values = $complexityData[$metricType] ?? null;
                    if (!$values) {
                        $output[$metricType] = null;
                        continue;
                    }
                    $output[$complexity][$metricType] = $this->safeAverage($values);
                }
            }
            \Storage::disk('local')->put($fileName, json_encode($output, JSON_PRETTY_PRINT));
        }
    }

    private function getQueryComplexity(int $index)
    {
        $reminder = $index % 8;
        return match ($reminder) {
            0, 1 => 'very_small',
            2, 3 => 'small',
            4, 5 => 'medium',
            6, 7 => 'large'
        };
    }

    private function safeAverage(array $data)
    {
        if (!count($data)) {
            return 0;
        }
        return array_sum($data) / count($data);
    }

    private function safeMedian(array $data)
    {
        if (!count($data)) {
            return 0;
        }
        sort($data);
        $count = count($data);
        $halfPoint = (int)($count / 2);
        if ($count % 2) {
            return $data[$halfPoint];
        }
        return ($data[$halfPoint] + $data[$halfPoint + 1]);
    }
}
