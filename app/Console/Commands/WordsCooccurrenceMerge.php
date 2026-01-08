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
        ini_set('memory_limit', '16G');
        echo 'Started merging words occurences' . PHP_EOL;
//        $files = Storage::disk('local')->files('words-cooccurrence-to-merge');
//        $readyFiles = array_filter($files, fn ($file) => str_contains($file, 'ready'));
//        foreach ($readyFiles as $file) {
//            $this->processFile($file);
//        }
//        $directories = [
//            'words-cooccurrence-merged-batch1',
//            'words-cooccurrence-merged-batch2',
//            'words-cooccurrence-merged-batch3',
//            'words-cooccurrence-merged-batch4',
//            'words-cooccurrence-merged-batch5'
//        ];
//        foreach ($directories as $directory) {
//            $files = Storage::disk('local')->files($directory);
//            foreach ($files as $file) {
//                //$data = Storage::disk('local')->get($file);
//                $data = file_get_contents(storage_path("app/private/$file"));
//                $outDir = str_replace($directory, 'words-cooccurrence-merged', $file);
//                //Storage::disk('local')->append($outDir, $data);
//                file_put_contents(storage_path("app/private/$outDir"), $data, FILE_APPEND);
//            }
//        }

        $batch = "batch5";
        $letterFiles = Storage::disk('local')->files("words-cooccurrence-merged-$batch");
        $output = fopen(storage_path("app/private/words-cooccurrence/merged-$batch.csv"), 'w');
        foreach ($letterFiles as $file) {
            $this->processLetterFile($output, $file);
        }
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
        echo "processing $filepath";
        $input = fopen(storage_path("app/private/$filepath"), 'r');
        $words = [];
        $processedLines = 0;
        while ($line = fgets($input)) {
            if ($processedLines % 1000000 === 0) {
                echo "Processed $processedLines lines";
            }
            $processedLines++;
            $data = str_getcsv($line, ';');
            if (count($data) < 5) {
                $data = str_getcsv($line, ',');
            }
            if (count($data) < 5) {
                throw new \Exception('Parsing error');
            }
            $firstWord = $data[0];
            $secondWord = $data[1];
            $prevCount = $words[$firstWord][$secondWord]['count'] ?? 0;
            $prevBefore = $words[$firstWord][$secondWord]['before'] ?? 0;
            $prevAfter = $words[$firstWord][$secondWord]['after'] ?? 0;
            $words[$firstWord][$secondWord]['count'] = $prevCount + (int)$data[2];
            $words[$firstWord][$secondWord]['before'] = $prevBefore + (int)$data[3];
            $words[$firstWord][$secondWord]['after'] = $prevAfter + (int)$data[4];
        }
        foreach ($words as $firstWord => $wordData) {
            foreach ($wordData as $secondWord => $counts) {
                fputcsv($output, [$firstWord, $secondWord, $counts['count'], $counts['before'], $counts['after']]);
            }
        }
    }
}
