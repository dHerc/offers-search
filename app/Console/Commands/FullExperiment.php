<?php

namespace App\Console\Commands;

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

class FullExperiment extends Command
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
    protected $signature = 'experiment:full {options}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run an experiment for provided options';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('memory_limit', '2G');
        $options = explode(',', $this->argument('options'));
        foreach ($options as $option) {
            $transformer = self::TRANSFORMERS[$option] ?? null;
            if (!$transformer) {
                throw new \RuntimeException("No transformer found for given option: $option");
            }
            $transformer = new $transformer();
            $queries = explode("\n", Storage::disk('local')->get('testing_queries-full.txt'));
            $file = fopen(storage_path("app/private/$option-results.ndjson"), 'a');
            foreach ($queries as $query) {
                echo "processing $query\n";
                if (empty($query)) {
                    continue;
                }
                $handler = new Experiment();
                $results = $handler->runExperiment($transformer, $query);
                foreach ($results as $result) {
                    $resultData = json_encode($result);
                    fwrite($file, $resultData . PHP_EOL);
                }
            }
        }
    }
}
