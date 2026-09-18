<?php
/**
 * site-crawler.php - Server-side website crawler for generating sitemaps
 * File path: api/site-crawler.php
 */

// Include database configuration if needed
require_once '../config.php';

require_once __DIR__ . '/../includes/security.php';
api_session_boot();

// Set header to return JSON
header('Content-Type: application/json');

// Check if URL is submitted
if (!isset($_POST['url']) || empty($_POST['url'])) {
    echo json_encode([
        'success' => false,
        'message' => 'URL is required'
    ]);
    exit;
}

// Get parameters
$url = trim($_POST['url']);
$maxPages = isset($_POST['maxPages']) ? intval($_POST['maxPages']) : 20;
$includeImages = isset($_POST['includeImages']) && $_POST['includeImages'] === '1';
$includeDates = isset($_POST['includeDates']) && $_POST['includeDates'] === '1';

// Validate URL format
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid URL format'
    ]);
    exit;
}

// Set maximum pages limit
$maxPages = min(100, max(5, $maxPages)); // Limit between 5 and 100

/**
 * Generate a sitemap for a website by crawling it
 */
class SitemapCrawler {
    private $url;
    private $maxPages;
    private $includeImages;
    private $includeDates;
    private $domain;
    private $visitedUrls = [];
    private $sitemapUrls = [];
    private $imagesByUrl = [];
    private $errorMessage = '';

    /**
     * Constructor
     * 
     * @param string $url Website URL to crawl
     * @param int $maxPages Maximum number of pages to crawl
     * @param bool $includeImages Whether to include image tags
     * @param bool $includeDates Whether to include lastmod dates
     */
    public function __construct($url, $maxPages, $includeImages, $includeDates) {
        $this->url = $this->normalizeUrl($url);
        $this->maxPages = $maxPages;
        $this->includeImages = $includeImages;
        $this->includeDates = $includeDates;
        
        // Extract domain
        $parsedUrl = parse_url($this->url);
        $this->domain = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
    }
    
    /**
     * Main method to generate sitemap
     * 
     * @return array Result with sitemap and stats
     */
    public function generateSitemap() {
        try {
            // Start from the main URL
            $this->crawlPage($this->url);
            
            // Generate the XML sitemap
            $xml = $this->generateXml();
            
            return [
                'success' => true,
                'sitemap' => $xml,
                'pageCount' => count($this->sitemapUrls),
                'imageCount' => $this->getTotalImageCount()
            ];
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
            return [
                'success' => false,
                'message' => $this->errorMessage
            ];
        }
    }
    
    /**
     * Crawl a specific page
     * 
     * @param string $url URL to crawl
     * @param int $depth Current depth level
     */
    private function crawlPage($url, $depth = 0) {
        // Stop if maximum pages reached
        if (count($this->sitemapUrls) >= $this->maxPages) {
            return;
        }
        
        // Skip if already visited
        if (in_array($url, $this->visitedUrls)) {
            return;
        }
        
        // Add to visited URLs
        $this->visitedUrls[] = $url;
        
        // Skip if excessive depth
        if ($depth > 5) {
            return;
        }
        
        // Fetch the page
        $html = $this->fetchUrl($url);
        if (!$html) {
            return;
        }
        
        // Add page to sitemap URLs with priority calculation
        $priority = 1.0 - ($depth * 0.2);
        $priority = max(0.1, min(1.0, $priority)); // Keep between 0.1 and 1.0
        
        $changefreq = $depth === 0 ? 'daily' : ($depth <= 2 ? 'weekly' : 'monthly');
        
        $this->sitemapUrls[] = [
            'url' => $url,
            'priority' => number_format($priority, 1),
            'changefreq' => $changefreq,
            'lastmod' => date('Y-m-d')
        ];
        
        // Extract images if needed
        if ($this->includeImages) {
            $this->extractImages($html, $url);
        }
        
        // Extract links and crawl them
        $links = $this->extractLinks($html, $url);
        
        foreach ($links as $link) {
            // Skip if we've reached the maximum
            if (count($this->sitemapUrls) >= $this->maxPages) {
                break;
            }
            
            $this->crawlPage($link, $depth + 1);
        }
    }
    
    /**
     * Extract links from HTML
     * 
     * @param string $html HTML content
     * @param string $baseUrl Base URL for relative links
     * @return array List of URLs
     */
    private function extractLinks($html, $baseUrl) {
        $links = [];
        $dom = new DOMDocument();
        
        // Suppress errors for invalid HTML
        libxml_use_internal_errors(true);
        @$dom->loadHTML($html);
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        $anchors = $xpath->query('//a[@href]');
        
        foreach ($anchors as $anchor) {
            $href = $anchor->getAttribute('href');
            
            // Skip empty, javascript, mailto, tel, and anchor links
            if (empty($href) || 
                strpos($href, 'javascript:') === 0 || 
                strpos($href, 'mailto:') === 0 || 
                strpos($href, 'tel:') === 0 || 
                $href === '#') {
                continue;
            }
            
            // Skip links with nofollow
            if ($anchor->hasAttribute('rel') && 
                strpos($anchor->getAttribute('rel'), 'nofollow') !== false) {
                continue;
            }
            
            // Convert to absolute URL
            $absoluteUrl = $this->resolveUrl($href, $baseUrl);
            
            if ($absoluteUrl) {
                // Only include links from the same domain
                $parsedUrl = parse_url($absoluteUrl);
                $linkDomain = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
                
                if ($linkDomain === $this->domain) {
                    // Remove URL fragments
                    $absoluteUrl = preg_replace('/#.*$/', '', $absoluteUrl);
                    
                    // Add to links if not already visited
                    if (!in_array($absoluteUrl, $links) && 
                        !in_array($absoluteUrl, $this->visitedUrls)) {
                        $links[] = $absoluteUrl;
                    }
                }
            }
        }
        
        return $links;
    }
    
    /**
     * Extract images from HTML
     * 
     * @param string $html HTML content
     * @param string $url URL of the page
     */
    private function extractImages($html, $url) {
        $images = [];
        $dom = new DOMDocument();
        
        // Suppress errors for invalid HTML
        libxml_use_internal_errors(true);
        @$dom->loadHTML($html);
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        $imgTags = $xpath->query('//img[@src]');
        
        foreach ($imgTags as $img) {
            $src = $img->getAttribute('src');
            
            if (!empty($src)) {
                // Get alt and title attributes
                $alt = $img->hasAttribute('alt') ? $img->getAttribute('alt') : '';
                $title = $img->hasAttribute('title') ? $img->getAttribute('title') : $alt;
                
                // Handle relative URLs
                $imgUrl = $this->resolveUrl($src, $url);
                
                if ($imgUrl) {
                    $images[] = [
                        'src' => $imgUrl,
                        'title' => $title
                    ];
                }
            }
        }
        
        if (!empty($images)) {
            $this->imagesByUrl[$url] = $images;
        }
    }
    
    /**
     * Fetch URL content
     * 
     * @param string $url URL to fetch
     * @return string|bool HTML content or false on failure
     */
    private function fetchUrl($url) {
        // Set up cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SEO Analysis Tool Sitemap Generator/1.0');
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300 && $html) {
            return $html;
        }
        
        return false;
    }
    
    /**
     * Resolve relative URL to absolute
     * 
     * @param string $url Relative URL
     * @param string $baseUrl Base URL
     * @return string|bool Absolute URL or false on error
     */
    private function resolveUrl($url, $baseUrl) {
        // Already absolute
        if (preg_match('/^https?:\/\//i', $url)) {
            return $url;
        }
        
        // Protocol-relative URL
        if (strpos($url, '//') === 0) {
            $parsedBase = parse_url($baseUrl);
            return isset($parsedBase['scheme']) ? $parsedBase['scheme'] . ':' . $url : 'http:' . $url;
        }
        
        // Create absolute URL
        if (function_exists('curl_init')) {
            $ch = curl_init($baseUrl);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $headers = curl_exec($ch);
            $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            curl_close($ch);
            
            if ($effectiveUrl) {
                $baseUrl = $effectiveUrl;
            }
        }
        
        // Handle root-relative URLs
        if (strpos($url, '/') === 0) {
            $parsedBase = parse_url($baseUrl);
            return sprintf('%s://%s%s', 
                $parsedBase['scheme'], 
                $parsedBase['host'], 
                $url
            );
        } else {
            // Handle relative URLs
            return rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
        }
    }
    
    /**
     * Generate XML sitemap
     * 
     * @return string XML sitemap
     */
    private function generateXml() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        
        // Add necessary namespaces
        if ($this->includeImages) {
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
        } else {
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        }
        
        // Add each URL
        foreach ($this->sitemapUrls as $page) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($page['url']) . '</loc>' . "\n";
            
            if ($this->includeDates) {
                $xml .= '    <lastmod>' . $page['lastmod'] . '</lastmod>' . "\n";
            }
            
            $xml .= '    <changefreq>' . $page['changefreq'] . '</changefreq>' . "\n";
            $xml .= '    <priority>' . $page['priority'] . '</priority>' . "\n";
            
            // Add image tags if enabled and available
            if ($this->includeImages && isset($this->imagesByUrl[$page['url']])) {
                foreach ($this->imagesByUrl[$page['url']] as $image) {
                    $xml .= '    <image:image>' . "\n";
                    $xml .= '      <image:loc>' . htmlspecialchars($image['src']) . '</image:loc>' . "\n";
                    
                    if (!empty($image['title'])) {
                        $xml .= '      <image:title>' . htmlspecialchars($image['title']) . '</image:title>' . "\n";
                    }
                    
                    $xml .= '    </image:image>' . "\n";
                }
            }
            
            $xml .= '  </url>' . "\n";
        }
        
        $xml .= '</urlset>';
        
        return $xml;
    }
    
    /**
     * Get the total number of images found
     * 
     * @return int Image count
     */
    private function getTotalImageCount() {
        $count = 0;
        foreach ($this->imagesByUrl as $url => $images) {
            $count += count($images);
        }
        return $count;
    }
    
    /**
     * Normalize URL by removing trailing slashes and fragments
     * 
     * @param string $url URL to normalize
     * @return string Normalized URL
     */
    private function normalizeUrl($url) {
        // Remove URL fragments
        $url = preg_replace('/#.*$/', '', $url);
        
        // Ensure URL has a trailing slash if there's no path
        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['path']) || $parsedUrl['path'] === '') {
            $url = rtrim($url, '/') . '/';
        }
        
        return $url;
    }
}

// Set timeout for long-running operations
set_time_limit(60); // 60 seconds

try {
    // Create crawler and generate sitemap
    $crawler = new SitemapCrawler($url, $maxPages, $includeImages, $includeDates);
    $result = $crawler->generateSitemap();
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'data' => [
                'sitemap' => $result['sitemap'],
                'pageCount' => $result['pageCount'],
                'imageCount' => $result['imageCount']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error generating sitemap: ' . $e->getMessage()
    ]);
}
