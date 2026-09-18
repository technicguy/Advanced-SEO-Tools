<?php
/**
 * batch-processor.php - Utility for handling large-scale SEO analysis in chunks
 */

require_once __DIR__ . '/../config.php';

class BatchProcessor {
    private $db;
    private $batchSize;
    private $delayBetweenBatches;

    public function __construct($db, $batchSize = 10, $delayBetweenBatches = 1) {
        $this->db = $db;
        $this->batchSize = $batchSize;
        $this->delayBetweenBatches = $delayBetweenBatches;
    }

    /**
     * Process a list of items in batches
     * @param array $items Array of items to process
     * @param callable $callback Function to call for each item
     * @return array Results of the processing
     */
    public function process($items, $callback) {
        $results = [];
        $totalItems = count($items);
        $batches = array_chunk($items, $this->batchSize);
        $totalBatches = count($batches);

        foreach ($batches as $batchIndex => $batch) {
            foreach ($batch as $item) {
                try {
                    $results[] = call_user_func($callback, $item);
                } catch (Exception $e) {
                    error_log("Error processing item in batch: " . $e->getMessage());
                    $results[] = ['error' => $e->getMessage(), 'item' => $item];
                }
            }

            // Optional delay between batches to avoid server load/rate limits
            if ($batchIndex < $totalBatches - 1 && $this->delayBetweenBatches > 0) {
                sleep($this->delayBetweenBatches);
            }
        }

        return $results;
    }

    /**
     * Set the batch size
     * @param int $size
     */
    public function setBatchSize($size) {
        $this->batchSize = $size;
    }

    /**
     * Set the delay between batches (seconds)
     * @param int $delay
     */
    public function setDelay($delay) {
        $this->delayBetweenBatches = $delay;
    }
}
