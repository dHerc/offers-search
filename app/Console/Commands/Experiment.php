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
    public function handle()
    {
        $option = $this->argument('option');
        $transformer = self::TRANSFORMERS[$option] ?? null;
        if (!$transformer) {
            throw new \RuntimeException("No transformer found for given option: $option");
        }
        $transformer = new $transformer();
        $query = $this->argument('query');
        var_dump($this->runExperiment($transformer, $query));
    }

    /**
     * @param $transformer
     * @param string $query
     * @return array{ExperimentResult, ExperimentResult}
     */
    public function runExperiment($transformer, string $query): array
    {
        [ 'score' => $baseScore, 'results' => $baseResult, 'time' => $baseTime ] = $this->getEmbeddingsScore($query);
        [ 'score' => $fulltextScore, 'results' => $fulltextResults, 'time' => $fulltextTime ] = $this->getFulltextScore($query);
        $start = microtime(true);
        $newQueries = $transformer->transform($query, $baseResult);
        $transformTime = (microtime(true) - $start) * 1_000_000;
        $baseResult = new ExperimentResult(
            'embedding',
            $transformer::class,
            $query,
            $baseScore,
            $baseTime,
            $transformTime
        );
        $fulltextResult = new ExperimentResult(
            'fulltext',
            $transformer::class,
            $query,
            $fulltextScore,
            $fulltextTime,
            $transformTime
        );
        if (!is_array($newQueries)) {
            $newQueries = [$newQueries];
        }
        foreach ($newQueries as $newQuery) {
            ['score' => $newScore, 'time' => $newTime] = $this->getEmbeddingsScore($newQuery);
            $newResult = new ExperimentModificationResult(
                $newQuery,
                $newScore,
                $newTime
            );
            $baseResult->suggestions[] = $newResult;
        }
        $newFulltextQueries = $transformer->transform($query, $fulltextResults);
        if (!is_array($newFulltextQueries)) {
            $newFulltextQueries = [$newFulltextQueries];
        }
        foreach ($newFulltextQueries as $newFulltextQuery) {
            ['score' => $newFulltextScore, 'time' => $newFulltextTime] = $this->getFulltextScore($newFulltextQuery);
            $newFulltextResult = new ExperimentModificationResult(
                $newFulltextQuery,
                $newFulltextScore,
                $newFulltextTime
            );
            $fulltextResult->suggestions[] = $newFulltextResult;
        }
        return [
            $baseResult,
            $fulltextResult
        ];
    }

    /**
     * @param string $query
     * @return array{'score': float, 'results': Collection, 'time': float, 'count': int}
     */
    private function getEmbeddingsScore(string $query): array
    {
        $start = microtime(true);
        $baseResult = QueryService::getOffers($query);
        $time = (microtime(true) - $start) * 1_000_000;
        $baseScore = ScoringService::scoreResults($query, $baseResult->all(), $baseResult->count());
        return [
            'score' => $baseScore,
            'results' => $baseResult,
            'time' => $time,
            'count' => $baseResult->count()
        ];
    }

    /**
     * @param string $query
     * @return array{'score': float, 'results': Collection, 'time': float, 'count': int}
     */
    private function getFulltextScore(string $query): array
    {
        $start = microtime(true);
        $baseResult = QueryService::getOffersByFullText($query);
        $time = (microtime(true) - $start) * 1_000_000;
        $baseScore = ScoringService::scoreResults($query, $baseResult->all(), $baseResult->count());
        return [
            'score' => $baseScore,
            'results' => $baseResult,
            'time' => $time,
            'count' => $baseResult->count()
        ];
    }
}
