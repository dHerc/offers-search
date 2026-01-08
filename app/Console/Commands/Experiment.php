<?php

namespace App\Console\Commands;

use App\Console\Commands\Experiment\ExperimentQueryResult;
use App\Console\Commands\Experiment\ExperimentResult;
use App\Console\Commands\Experiment\ExperimentScores;
use App\Console\Commands\Experiment\ExperimentType;
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
use RuntimeException;

class Experiment extends Command
{
    /**
     * @var array<string, class-string<QueryTransformerInterface>>
     */
    private const TRANSFORMERS = [
        'dummy' => DummyQueryTransformer::class,
        'remove_word' => RemoveWordQueryTransformer::class,
        'reorder' => ReorderWordsQueryTransformer::class,
        'to_base' => ToBaseWordQueryTransformer::class,
        'snippets' => SnippetsQueryTransformer::class,
        'ngrams' => NGramsQueryTransformer::class,
        'gemini_add' => ChatbotAddWordsQueryTransformer::class,
        'gemini_paraphrase' => ChatbotParaphraseQueryTransformer::class,
        'gemini_remove' => ChatbotRemoveWordsQueryTransformer::class,
        'bm25_rm3' => BM25RM3QueryTransformer::class,
        'flan_qr' => FlanQRQueryTransformer::class,
        'flan_prf' => FlanPRFQueryTransformer::class,
    ];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'experiment {query} {option}';

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
    public function handle(): void
    {
        $option = $this->argument('option');
        $transformer = self::TRANSFORMERS[$option] ?? null;
        if (!$transformer) {
            throw new RuntimeException("No transformer found for given option: $option");
        }
        $transformer = new $transformer();
        $query = $this->argument('query');
        var_dump($this->runExperiment($transformer, $query));
    }

    /**
     * @return array{ExperimentResult, ExperimentResult}
     */
    public function runExperiment(QueryTransformerInterface $transformer, string $query): array
    {
        return [
            $this->getResult($transformer, ExperimentType::FULLTEXT, $query),
            $this->getResult($transformer, ExperimentType::EMBEDDINGS, $query),
        ];
    }

    private function getResult(QueryTransformerInterface $transformer, ExperimentType $type, string $query): ExperimentResult
    {
        $fullStart = microtime(true);
        [ 'scores' => $scores, 'results' => $results, 'time' => $time ] = $this->getScore($type, $query);
        $start = microtime(true);
        $newQueries = $transformer->transform($query, $results);
        $transformTime = (microtime(true) - $start) * 1_000_000;
        $fullTime = (microtime(true) - $fullStart) * 1_000_000;
        $result = new ExperimentResult(
            $type,
            $transformer::class,
            $query,
            $scores,
            $time,
            $transformTime,
            $fullTime
        );
        if (!is_array($newQueries)) {
            $newQueries = [$newQueries];
        }
        foreach ($newQueries as $newQuery) {
            ['scores' => $newScores, 'time' => $newTime] = $this->getScore($type, $newQuery);
            $newResult = new ExperimentQueryResult(
                $newQuery,
                $newScores,
                $newTime
            );
            $result->suggestions[] = $newResult;
        }
        return $result;
    }

    /**
     * @return array{'scores': ExperimentScores, 'results': Collection, 'time': float}
     */
    private function getScore(ExperimentType $type, string $query): array
    {
        $start = microtime(true);
        $results = match ($type) {
            ExperimentType::EMBEDDINGS => QueryService::getOffers($query),
            ExperimentType::FULLTEXT => QueryService::getOffersByFullText($query)
        };
        $time = (microtime(true) - $start) * 1_000_000;
        $scores = ScoringService::scoreResults($query, $results->all());
        return [
            'scores' => $scores,
            'results' => $results,
            'time' => $time
        ];
    }
}
