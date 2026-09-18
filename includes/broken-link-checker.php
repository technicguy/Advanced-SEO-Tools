<?php
/**
 * broken-link-checker.php - Checks for broken links on a website
 * File path: includes/broken-link-checker.php
 * 
 * This is a simplified version for integration with the SEO Analysis Tool.
 * It uses the standalone broken-link-checker.php script as a backend.
 */

class BrokenLinkChecker {
    private $url;
    private $maxLinks;
    private $maxDepth;
    private $checkExternal;
    private $excludePatterns;
    
    /**
     * Constructor
     *
     * @param string $url Base URL to check
     * @param int $maxLinks Maximum number of links to check
     * @param int $maxDepth Maximum depth to crawl
     * @param bool $checkExternal Whether to check external links
     * @param array $excludePatterns Patterns to exclude from checking
     */
    public function __construct($url, $maxLinks = 100, $maxDepth = 2, $checkExternal = false, $excludePatterns = []) {
        $this->url = $url;
        $this->maxLinks = $maxLinks;
        $this->maxDepth = $maxDepth;
        $this->checkExternal = $checkExternal;
        $this->excludePatterns = $excludePatterns;
    }
    
    /**
     * Check links on a website
     *
     * @return array The results of the link check
     */
    public function check() {
        // Build the URL for the standalone checker
        $params = [
            'action' => 'check',
            'format' => 'json',
            'url' => $this->url,
            'depth' => $this->maxDepth,
            'max_pages' => $this->maxLinks,
            'check_external' => $this->checkExternal ? '1' : '0'
        ];
        
        if (!empty($this->excludePatterns)) {
            $params['exclude_patterns'] = implode("\n", $this->excludePatterns);
        }
        
        $url = 'broken-link-checker.php?' . http_build_query($params);
        
        // Make the request
        $response = @file_get_contents($url);
        
        if ($response === false) {
            // If the standalone checker is not available, use a simplified check
            return $this->simplifiedCheck();
        }
        
        // Parse the response
        $result = json_decode($response, true);
        
        // If parsing failed, use a simplified check
        if (!$result || !isset($result['success'])) {
            return $this->simplifiedCheck();
        }
        
        return $result;
    }
    
    /**
     * Simplified check for broken links
     * Used as a fallback when the standalone checker is not available
     *
     * @return array The results of the link check
     */
    private function simplifiedCheck() {
        // Basic implementation to check a few links on the page
        $html = $this->fetchUrl($this->url);
        
        if (!$html) {
            return [
                'success' => false,
                'error' => 'Failed to fetch URL'
            ];
        }
        
        // Extract links
        $links = $this->extractLinks($html, $this->url);
        
        // Check each link
        $brokenLinks = [];
        $checkedLinks = [];
        
        foreach ($links as $link) {
            // Skip if already checked
            if (isset($checkedLinks[$link])) {
                continue;
            }
            
            // Check if it's an external link
            $isExternal = $this->isExternalUrl($link);
            
            // Skip external links if not checking them
            if ($isExternal && !$this->checkExternal) {
                continue;
            }
            
            // Check the link
            $status = $this->checkLink($link);
            
            $checkedLinks[$link] = [
                'url' => $link,
                'status' => $status
            ];
            
            // If broken, add to broken links
            if ($status['code'] >= 400 || $status['error']) {
                $brokenLinks[] = [
                    'url' => $link,
                    'status' => $status,
                    'parent' => $this->url
                ];
            }
            
            // Limit to max links
            if (count($checkedLinks) >= $this->maxLinks) {
                break;
            }
        }
        
        return [
            'success' => true,
            'base_url' => $this->url,
            'pages_checked' => 1,
            'links_checked' => count($checkedLinks),
            'broken_links' => $brokenLinks,
            'redirected_links' => [],
            'execution_time' => 0
        ];
    }
    
    /**
     * Fetch a URL and return the content
     * 
     * @param string $url URL to fetch
     * @return string|false Content of the URL or false on failure
     */
    private function fetchUrl($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SEO Analysis Tool/1.0');
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300 && $html) {
            return $html;
        }
        
        return false;
    }
    
    /**
     * Extract links from HTML content
     * 
     * @param string $html HTML content
     * @param string $baseUrl Base URL for resolving relative links
     * @return array Array of links
     */
    private function extractLinks($html, $baseUrl) {
        $links = [];
        
        // Parse the HTML
        $dom = new DOMDocument();
        
        // Suppress errors from malformed HTML
        libxml_use_internal_errors(true);
        @$dom->loadHTML($html);
        libxml_clear_errors();
        
        // Get all links
        $anchors = $dom->getElementsByTagName('a');
        foreach ($anchors as $anchor) {
            $href = $anchor->getAttribute('href');
            
            // Skip empty or javascript links
            if (empty($href) || strpos($href, 'javascript:') === 0 || $href === '#') {
                continue;
            }
            
            // Convert relative URLs to absolute
            if (strpos($href, 'http') !== 0) {
                if (strpos($href, '/') === 0) {
                    // Absolute path
                    $parsedUrl = parse_url($baseUrl);
                    $href = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $href;
                } else {
                    // Relative path
                    $href = rtrim(dirname($baseUrl), '/') . '/' . ltrim($href, '/');
                }
            }
            
            $links[] = $href;
        }
        
        return $links;
    }
    
    /**
     * Check if a URL is external
     * 
     * @param string $url URL to check
     * @return bool True if external, false otherwise
     */
    private function isExternalUrl($url) {
        $parsedBase = parse_url($this->url);
        $parsedUrl = parse_url($url);
        
        // If the URL doesn't have a host, it's internal
        if (!isset($parsedUrl['host'])) {
            return false;
        }
        
        // Compare hosts, ignoring 'www'
        $baseHost = preg_replace('/^www\./', '', $parsedBase['host']);
        $urlHost = preg_replace('/^www\./', '', $parsedUrl['host']);
        
        return $baseHost !== $urlHost;
    }
    
    /**
     * Check if a link is broken
     * 
     * @param string $url URL to check
     * @return array Status information
     */
    private function checkLink($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SEO Analysis Tool/1.0');
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        return [
            'code' => $httpCode,
            'error' => !empty($error) ? $error : false
        ];
    }
}
