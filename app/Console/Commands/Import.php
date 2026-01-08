<?php

namespace App\Console\Commands;

use App\Models\Offer;
use App\Services\EmbeddingService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class Import extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private const BATCH_LIMIT = 100;
    private int $failedToLoad = 0;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('memory_limit', '1G');
        $files = Storage::disk('amazon_data')->files();
		foreach ($files as $gzip) {
			$uncompressedFileName = substr($gzip, 0, strlen($gzip) - 3);
			$file = gzopen(storage_path("app\\amazon_data\\$gzip"), 'rb');
			$outFile = fopen(storage_path("app\\amazon_data_uncompressed\\$uncompressedFileName"), 'wb+');

			while (!gzeof($file)) {
				fwrite($outFile, gzread($file, 4096));
			}
			rewind($outFile);
            $batch = [];
			while ($line = fgets($outFile)) {
                if ($start > 0) {
                    $start--;
                    continue;
                }
				$data = json_decode($line, true);
                $description = $data['description'];
                $features = $data['features'];
				$fixedData = [
					'category' => $data['main_category'] ?? 'unknown',
					'title' => $data['title'] ?? '',
					'features' => is_array($features) ? implode("\n", $data['features']) : (string)$features,
					'description' => is_array($description) ? implode("\n", $description) : (string)$description,
					'details' => $this->generateDetails($data['details']),
				];
                $batch[] = $fixedData;
                if (count($batch) >= self::BATCH_LIMIT) {
                    $this->insertBatch($batch);
                    unset($batch);
                    $batch = [];
                }
			}
            if (count($batch)) {
                $this->insertBatch($batch);
            }
			Storage::disk('amazon_data_uncompressed')->delete($uncompressedFileName);;
		}
        echo "Failed to import {$this->failedToLoad} files\n";
    }

    private function insertBatch(array $offersData): void {
        $data = [];
        $embeddings = $this->getEmbeddings(
            array_map(fn ($offer) => implode('\n', $offer), $offersData)
        );
        foreach ($offersData as $index => $offerData) {
            $offerData['embeddings'] = '[' . $embeddings[$index] . ']';
            $data[] = $offerData;
        }
        unset($offersData);
        Offer::query()->insert($data);
    }

    private function generateDetails(mixed $details, string $glue = "\n"): string
    {
        if (!is_array($details)) {
            return (string)$details;
        }
        $mappedDetails = [];
        foreach ($details as $name => $detail) {
            $fixedName = is_numeric($name) ? '' : "$name: ";
            $mappedDetails[] = $fixedName . $this->generateDetails($detail, ",");
        }
        return implode($glue, $mappedDetails);
    }

    /**
     * @param array $fullTexts
     * @param $remainingSplits
     * @return string[]
     */
    private function getEmbeddings(array $fullTexts, $remainingSplits = 5): array
    {
        $embeddings = EmbeddingService::generateBatchEmbeddings($fullTexts);
        if (!$embeddings) {
            if ($remainingSplits <= 0) {
                throw new \RuntimeException('Cannot generate embeddings');
            }
            $splitPoint = (int)(count($fullTexts) / 2);
            $embeddings = [
                ...$this->getEmbeddings(array_slice($fullTexts, 0, $splitPoint), $remainingSplits - 1),
                ...$this->getEmbeddings(
                    array_slice($fullTexts, $splitPoint, count($fullTexts) - $splitPoint),
                    $remainingSplits - 1
                )
            ];
        }
        return $embeddings;
    }
}
