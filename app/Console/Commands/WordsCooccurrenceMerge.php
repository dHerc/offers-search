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

class WordsCooccurrenceMerge extends Command
{
    private array $files = [];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:words-cooccurrence-merge';

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
        ini_set('memory_limit', '6G');
        echo 'Started merging words occurences' . PHP_EOL;
        $files = Storage::disk('local')->files('words-cooccurrence-to-merge');
        $readyFiles = array_filter($files, fn ($file) => str_contains($file, 'ready'));
        foreach ($readyFiles as $file) {
            $this->processFile($file);
        }
//        $letterFiles = Storage::disk('local')->files('words-cooccurrence-merged');
//        $output = fopen(storage_path('app/private/words-cooccurrence/merged.csv'), 'w');
//        foreach ($letterFiles as $file) {
//            $this->processLetterFile($output, $file);
//        }
    }

    private function processFile(string $inputFilepath): void
    {
        echo "processing $inputFilepath" . PHP_EOL;
        $file = fopen(storage_path('app/private/' . $inputFilepath), 'r');
        while ($line = fgets($file)) {
            $firstWord = str_getcsv($line)[0];
            $filepath = $this->getOutputFilepath($firstWord);
            $newFile = $this->files[$filepath] ?? null;
            if (!$newFile) {
                $newFile = fopen(storage_path('app/private/' . $filepath), 'a');
                $this->files[$filepath] = $newFile;
            }
            fputs($newFile, $line);
        }
        Storage::disk('local')->move($inputFilepath, str_replace('words-cooccurrence', 'processed/words-cooccurrence-merged', $inputFilepath));
    }

    private function getOutputFilepath(string $word)
    {
        $isLetterWord = ctype_alpha($word[0]);
        $filename = $isLetterWord ? $word[0] : 'other';
        return 'words-cooccurrence-merged/' . $filename . '.csv';
    }

    private function processLetterFile($output, string $filepath): void
    {
        $input = fopen(storage_path("app/private/$filepath"), 'r');
        $words = [];
        while ($line = fgetcsv($input)) {
            $firstWord = $line[0];
            $secondWord = $line[1];
            $prevCount = $words[$firstWord][$secondWord]['count'] ?? 0;
            $prevBefore = $words[$firstWord][$secondWord]['before'] ?? 0;
            $prevAfter = $words[$firstWord][$secondWord]['after'] ?? 0;
            $words[$firstWord][$secondWord]['count'] = $prevCount + (int)$line[2];
            $words[$firstWord][$secondWord]['before'] = $prevBefore + (int)$line[3];
            $words[$firstWord][$secondWord]['after'] = $prevAfter + (int)$line[4];
        }
        foreach ($words as $firstWord => $wordData) {
            foreach ($wordData as $secondWord => $counts) {
                fputcsv($output, [$firstWord, $secondWord, $counts['count'], $counts['before'], $counts['after']]);
            }
        }
    }
}
