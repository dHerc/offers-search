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

class BuildResultsLine extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'experiment:build-results {directory} {complexity} {fields} {mult=1} {groupName?}';

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
        $directory = $this->argument('directory');
        $complexity = $this->argument('complexity');
        $groupName = $this->argument('groupName') ?? '';
        $fields = explode(',', $this->argument('fields'));
        $mult = (float)$this->argument('mult');
        $files = [
            'snippets',
            'ngrams',
            'bm25_rm3',
            'flan_qr',
            'flan_prf',
            'gemini_add',
            'gemini_paraphrase',
            'gemini_remove',
            'remove_word',
            'to_base'
        ];
        foreach ($fields as $field) {
            if($field === 'time') {
                $mult = 0.000001;
            }
            if($field === 'filled') {
                $mult = 1;
            }
            $values = [];
            foreach ($files as $file) {
                $text = Storage::disk('local')->get("processed_results/$directory/$file-results.ndjson");
                $data = json_decode($text, true);
                $values[] = number_format($data[$complexity][$field] * $mult, 1);
            }
            echo "$\mathbf{W_{}}$ & $groupName & " . implode(' & ', $values) . " \\\\ \hline". PHP_EOL;
        }
    }
}
