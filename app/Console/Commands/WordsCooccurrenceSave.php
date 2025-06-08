<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class WordsCooccurrenceSave extends Command
{
    private const int MAX_WORD_LENGTH = 2704;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:words-cooccurrence-save';

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
        echo 'Started saving words cooccurrence' . PHP_EOL;
        ini_set('memory_limit', '3G');
        while (true) {
            $readyFiles = Storage::disk('local')->files('words-cooccurrence');
            $readyFiles = array_values(array_filter($readyFiles, fn ($file) => str_contains($file, 'ready')));
            if (!$readyFiles) {
                sleep(60);
                continue;
            }
            $file = $readyFiles[0];
            echo "processing $file" . PHP_EOL;
            $this->insertWords($file);
            Storage::disk('local')->move($file, "processed/$file");
        }

    }

    private function insertWords(string $file): void
    {
        DB::transaction(function () use ($file) {
            $reader = fopen(storage_path("app/private/$file"), 'r');
            $batchSize = 60000 / 5; //arbitrary param limit divided by amount of params
            $chunk = [];
            while ($line = fgetcsv($reader)) {
                if ((strlen($line[0]) + strlen($line[1])) > self::MAX_WORD_LENGTH) {
                    continue;
                }
                $chunk[] = $line;
                if (count($chunk) > $batchSize) {
                    $this->saveChunk($chunk);
                    $chunk = [];
                }
            }
            if (count($chunk) > 0) {
                $this->saveChunk($chunk);
            }
        });
    }

    private function saveChunk(array $chunk): void
    {
        $values = [];
        foreach ($chunk as $ignored) {
            $values[] = '(?,?,?,?,?)';
        }
        $valuesStatement = implode(', ' . PHP_EOL, $values);
        $bindings = array_map(fn($item) => [
            $item[0],
            $item[1],
            (int)$item[2],
            (int)$item[3],
            (int)$item[4]
        ], $chunk);
        DB::insert("
                INSERT INTO word_cooccurrence AS wo (word_a, word_b, frequency, before, after)
                VALUES $valuesStatement
                ON CONFLICT (word_a, word_b)
                DO UPDATE SET
                    frequency = wo.frequency + EXCLUDED.frequency,
                    before = wo.before + EXCLUDED.before,
                    after = wo.after + EXCLUDED.after
            ", array_values(array_merge(...$bindings)));
    }
}
