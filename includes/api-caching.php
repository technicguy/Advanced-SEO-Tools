// Add this to config.php

/**
 * Cache class for API results
 */
class ApiCache {
    // Cache lifetime in seconds (default: 60 minutes)
    private $lifetime = 3600;
    
    // Cache directory
    private $cacheDir;
    
    // Constructor
    public function __construct($cacheDir = 'cache', $lifetime = 3600) {
        $this->cacheDir = rtrim($cacheDir, '/');
        $this->lifetime = $lifetime;
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Generate cache key from URL and parameters
     */
    private function generateKey($url, $params = []) {
        // Sort parameters to ensure consistent keys
        if (is_array($params)) {
            ksort($params);
        }
        
        // Create key from URL and serialized parameters
        $key = md5($url . serialize($params));
        return $key;
    }
    
    /**
     * Get the full path to a cache file
     */
    private function getCachePath($key) {
        return $this->cacheDir . '/' . $key . '.cache';
    }
    
    /**
     * Check if a cache file exists and is still valid
     */
    public function exists($url, $params = []) {
        $key = $this->generateKey($url, $params);
        $cachePath = $this->getCachePath($key);
        
        if (file_exists($cachePath)) {
            $modifiedTime = filemtime($cachePath);
            if (time() - $modifiedTime < $this->lifetime) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Store data in cache
     */
    public function set($url, $data, $params = []) {
        $key = $this->generateKey($url, $params);
        $cachePath = $this->getCachePath($key);
        
        return file_put_contents($cachePath, serialize($data));
    }
    
    /**
     * Get data from cache
     */
    public function get($url, $params = []) {
        $key = $this->generateKey($url, $params);
        $cachePath = $this->getCachePath($key);
        
        if ($this->exists($url, $params)) {
            return unserialize(file_get_contents($cachePath));
        }
        
        return null;
    }
    
    /**
     * Delete a cache entry
     */
    public function delete($url, $params = []) {
        $key = $this->generateKey($url, $params);
        $cachePath = $this->getCachePath($key);
        
        if (file_exists($cachePath)) {
            return unlink($cachePath);
        }
        
        return false;
    }
    
    /**
     * Clear all cache or cache older than specified time
     */
    public function clear($maxAge = null) {
        if (!is_dir($this->cacheDir)) {
            return false;
        }
        
        $files = glob($this->cacheDir . '/*.cache');
        
        foreach ($files as $file) {
            // Skip if we're only cleaning up old cache files and this one is still valid
            if ($maxAge !== null && time() - filemtime($file) < $maxAge) {
                continue;
            }
            
            // Delete the cache file
            unlink($file);
        }
        
        return true;
    }
}

// Create cache instance
$apiCache = new ApiCache('cache', 3600);

// Enhanced makeHttpRequest function with caching
function makeHttpRequest($url, $options = [], $useCache = true, $cacheLifetime = null) {
    global $apiCache;
    
    // Check cache first if caching is enabled
    if ($useCache && $apiCache->exists($url, $options)) {
        return $apiCache->get($url, $options);
    }
    
    // If not in cache or cache is disabled, make the actual request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    
    // Add headers to make the request more like a browser
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1',
        'Cache-Control: max-age=0'
    ]);
    
    // Apply proxy settings if enabled
    if (defined('USE_PROXY') && USE_PROXY && defined('PROXY_HOST') && !empty(PROXY_HOST)) {
        curl_setopt($ch, CURLOPT_PROXY, PROXY_HOST);
        
        if (defined('PROXY_PORT') && !empty(PROXY_PORT)) {
            curl_setopt($ch, CURLOPT_PROXYPORT, PROXY_PORT);
        }
        
        if (defined('PROXY_USERNAME') && !empty(PROXY_USERNAME) && defined('PROXY_PASSWORD') && !empty(PROXY_PASSWORD)) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, PROXY_USERNAME . ':' . PROXY_PASSWORD);
        }
    }
    
    // Apply any additional options
    foreach ($options as $option => $value) {
        curl_setopt($ch, $option, $value);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Log any errors for debugging
    if (curl_errno($ch)) {
        error_log("cURL Error for URL $url: " . curl_error($ch));
    }
    
    curl_close($ch);
    
    // Consider any 2xx status code as success
    if ($httpCode >= 200 && $httpCode < 300 && !empty($response)) {
        // Cache the successful response if caching is enabled
        if ($useCache) {
            // If a custom cache lifetime is provided, temporarily override the default
            if ($cacheLifetime !== null) {
                $oldLifetime = $apiCache->lifetime;
                $apiCache->lifetime = $cacheLifetime;
                $apiCache->set($url, $response, $options);
                $apiCache->lifetime = $oldLifetime;
            } else {
                $apiCache->set($url, $response, $options);
            }
        }
        
        return $response;
    }
    
    // Log failed requests
    error_log("HTTP Request failed for URL $url with status code $httpCode");
    return null;
}

// Enhanced callPageSpeedApi function with caching
function callPageSpeedApi($url, $strategy = 'mobile') {
    $apiUrl = PAGESPEED_API_ENDPOINT . '?url=' . urlencode($url) . '&strategy=' . $strategy;
    
    // Add API key if available
    if (defined('PAGESPEED_API_KEY') && !empty(PAGESPEED_API_KEY)) {
        $apiUrl .= '&key=' . PAGESPEED_API_KEY;
    }
    
    // Add category parameters to get more detailed information
    $apiUrl .= '&category=performance&category=accessibility&category=best-practices&category=seo';
    
    // Add locale parameter
    $apiUrl .= '&locale=en';
    
    // Cache PageSpeed results for 24 hours (86400 seconds) since they don't change frequently
    $response = makeHttpRequest($apiUrl, [], true, 86400);
    
    if ($response) {
        return json_decode($response, true);
    }
    
    return null;
}

// Create the cache directory if it doesn't exist
if (!is_dir('cache')) {
    mkdir('cache', 0755, true);
}

// Create a maintenance function to clear old cache
function clearOldCache() {
    global $apiCache;
    
    // Clear cache files older than 7 days (604800 seconds)
    $apiCache->clear(604800);
}

// Optionally run cache cleanup occasionally (e.g., 1% of requests)
if (rand(1, 100) == 1) {
    clearOldCache();
}
