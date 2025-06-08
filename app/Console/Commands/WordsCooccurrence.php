<?php

namespace App\Console\Commands;

use App\Models\Offer;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Markard\Lemma;
use Markard\Lemmatizer;

class WordsCooccurrence extends Command
{
    private const int BATCH_SIZE = 20000;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:words-cooccurrence';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('memory_limit', '8G');
        echo 'Started loading words occurences' . PHP_EOL;
        $start = (int)Storage::disk('local')->get('words-cooccurrence.txt') + 1;
        //$maxId = (int)(Offer::query()->selectRaw('MAX(id) as max')->first()->max);
        $maxId = 35393189;
        $reader = fopen('D:\\magisterka_db_sorted.csv', 'r');
        for ($i = $start; $i < $maxId; $i += self::BATCH_SIZE) {
            $this->processBatch($i, $i + self::BATCH_SIZE - 1, $reader);
        }

    }

    private function processBatch(int $start, int $end, $reader): void
    {
        /** @var array<string, array<string, array{'count': int, 'before': int, 'after': int }>> $words */
        $words = [];
        /** @var Collection<Offer> $offers */
        //$offers = Offer::query()->whereBetween('id', [$start, $end])->get();
        while($line = fgetcsv($reader, escape: "'")) {
            if ($line[0] < $start) {
                continue;
            }
            if ($line[0] % 10000 == 0) {
                echo "Mid processing of {$line[0]}\n";
            }
            $this->processField($line[1], $words);
            $this->processField($line[2], $words);
            $this->processField($line[3], $words);
            $this->processField($line[4], $words);
            if ($line[0] >= $end) {
                break;
            }
        }
//        foreach ($offers as $offer) {
//            $this->processField($offer->title, $words);
//            $this->processField($offer->features, $words);
//            $this->processField($offer->description, $words);
//            $this->processField($offer->details, $words);
//        }
        $this->insertWords($this->flattenWords($words));
        echo "processed $end entries \n";
        Storage::disk('local')->put('words-cooccurrence.txt', $end);
    }

    /**
     * @param string $field
     * @param array<string, array<string, array{'count': int, 'before': int, 'after': int }>> $words
     * @return void
     */
    private function processField(string $field, array &$words): void {
        $lastWords = [];
        $fieldWords = explode(' ', $field);
        foreach ($fieldWords as $word) {
            foreach ($lastWords as $lastWord) {
                $data = $this->normalizeWords($lastWord, $word);
                if (!$data) {
                    continue;
                }
                $first = $data['first'];
                $second = $data['second'];
                if (!isset($words[$first][$second])) {
                    $words[$first][$second] = ['count' => 0, 'before' => 0, 'after' => 0];
                }
                $words[$first][$second]['count']++;
                if ($data['type'] === 'before') {
                    $words[$first][$second]['before']++;
                }
                if ($data['type'] === 'after') {
                    $words[$first][$second]['after']++;
                }
            }
            $lastWords[] = $word;
            $lastWords = array_slice($lastWords, -5, 5);
        }
    }

    /**
     * @param string $first
     * @param string $second
     * @return array{'first': string, 'second': string, 'type': 'before' | 'after'} | null
     */
    private function normalizeWords(string $first, string $second): array | null
    {
        $lemmatizer = new Lemmatizer();
        $firstLemma = $this->getLemmaPrioritizingNouns($lemmatizer, mb_strtolower(preg_replace('/[^\p{L}0-9]/', '', $first)));
        $secondLemma = $this->getLemmaPrioritizingNouns($lemmatizer, mb_strtolower(preg_replace('/[^\p{L}0-9]/', '', $second)));
        if (!$firstLemma || !$secondLemma) {
            return null;
        }
        if ($firstLemma === $secondLemma) {
            return null;
        }
        if (strcmp($firstLemma, $secondLemma) < 0) {
            return [
                'first' => $firstLemma,
                'second' => $secondLemma,
                'type' => 'after'
            ];
        }
        return [
            'first' => $secondLemma,
            'second' => $firstLemma,
            'type' => 'before'
        ];
    }

    private function getLemmaPrioritizingNouns(Lemmatizer $lemmatizer, string $word): string
    {
        $lemmas = $lemmatizer->getLemmas($word);
        foreach ($lemmas as $lemma) {
            if ($lemma->getPartOfSpeech() === Lemma::POS_NOUN) {
                return $lemma->getLemma();
            }
        }
        return $lemmas[0]->getLemma();
    }

    /**
     * @param array<string, array<string, array{'count': int, 'before': int, 'after': int }>> $words
     * @return array{string, string, int, int, int}[]
     */
    private function flattenWords(array $words): array
    {
        $flat = [];
        foreach ($words as $first => $map) {
            foreach ($map as $second => $data) {
                $flat[] = [
                    $first,
                    $second,
                    $data['count'],
                    $data['before'],
                    $data['after']
                ];
            }
        }
        return $flat;
    }

    /**
     * @param array{string, string, int, int, int}[] $words
     * @return void
     */
    private function insertWords(array $words): void
    {
        $date = Carbon::now()->timestamp;
        $filename = 'words-cooccurrence/pending-' . $date;
        $output = fopen(storage_path("app/private/$filename"), 'w');
        foreach ($words as $item) {
            fputcsv($output, $item);
        }
        Storage::disk('local')->move($filename, str_replace('pending', 'ready', $filename));
        return;
//        $batchSize = 30000 / 5; //arbitrary param limit divided by amount of params
//        $chunks = array_chunk($words, $batchSize);
//        foreach ($chunks as $chunk) {
//            $values = [];
//            foreach ($chunk as $ignored) {
//                $values[] = '(?,?,?,?,?)';
//            }
//            $valuesStatement = implode(', ' . PHP_EOL, $values);
//            $bindings = array_map(fn($item) => [
//                $item['first'],
//                $item['second'],
//                $item['count'],
//                $item['before'],
//                $item['after']
//            ], $chunk);
//            DB::statement("
//                INSERT INTO word_cooccurrence AS wo (word_a, word_b, frequency, before, after)
//                VALUES $valuesStatement
//                ON CONFLICT (word_a, word_b)
//                DO UPDATE SET
//                    frequency = wo.frequency + EXCLUDED.frequency,
//                    before = wo.before + EXCLUDED.before,
//                    after = wo.after + EXCLUDED.after
//            ", array_values(array_merge(...$bindings)));
//        }
    }
}
