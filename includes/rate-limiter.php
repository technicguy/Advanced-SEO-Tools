<?php
/**
 * rate-limiter.php - Utility to prevent exceeding API quotas
 */

require_once __DIR__ . '/../config.php';

class RateLimiter {
    private $storageFile;
    private $limit;
    private $timeWindow; // in seconds

    /**
     * RateLimiter constructor
     * @param string $serviceName Name of the service (to create a unique storage file)
     * @param int $limit Max number of requests allowed within the window
     * @param int $timeWindow Time window in seconds
     */
    public function __construct($serviceName, $limit = 60, $timeWindow = 60) {
        $this->storageFile = CACHE_PATH . '/rate_limit_' . md5($serviceName) . '.json';
        $this->limit = $limit;
        $this->timeWindow = $timeWindow;

        // Ensure cache directory exists
        if (!is_dir(CACHE_PATH)) {
            mkdir(CACHE_PATH, 0755, true);
        }
    }

    /**
     * Check if a request is allowed
     * @return bool True if request is allowed, false otherwise
     */
    public function isAllowed() {
        $data = $this->loadData();
        $currentTime = time();
        
        // Remove requests older than the time window
        $data['requests'] = array_filter($data['requests'], function($timestamp) use ($currentTime) {
            return ($currentTime - $timestamp) < $this->timeWindow;
        });

        if (count($data['requests']) < $this->limit) {
            $data['requests'][] = $currentTime;
            $this->saveData($data);
            return true;
        }

        return false;
    }

    /**
     * Get the remaining requests in the current window
     * @return int
     */
    public function getRemaining() {
        $data = $this->loadData();
        $currentTime = time();
        $requests = array_filter($data['requests'], function($timestamp) use ($currentTime) {
            return ($currentTime - $timestamp) < $this->timeWindow;
        });
        
        return max(0, $this->limit - count($requests));
    }

    private function loadData() {
        if (file_exists($this->storageFile)) {
            $content = file_get_contents($this->storageFile);
            $data = json_decode($content, true);
            if (isset($data['requests'])) {
                return $data;
            }
        }
        return ['requests' => []];
    }

    private function saveData($data) {
        file_put_contents($this->storageFile, json_encode($data));
    }
}
