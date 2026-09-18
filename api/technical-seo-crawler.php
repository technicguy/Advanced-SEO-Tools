<?php
/**
 * site-crawler.php - Crawls a website and generates a sitemap
 * File path: api/site-crawler.php
 */

// Include database configuration
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

// Get the URL and options
$url = trim($_POST['url']);
$maxPages = isset($_POST['maxPages']) ? intval($_POST['maxPages']) : 20;
$includeImages = isset($_POST['includeImages']) && $_POST['includeImages'] == '1';
$includeDates = isset($_POST['includeDates']) && $_POST['includeDates'] == '1';

// Validate URL format
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid URL format'
    ]);
    exit;
}

// Class to crawl a website
class SiteCrawler {
    private $baseUrl;
    private $visitedUrls = [];
    private $pages = [];
    private $images = [];
    private $domain;
    private $maxPages;
    private $includeImages;
    
    /**
     * Constructor
     * 
     * @param string $baseUrl The base URL to crawl
     * @param int $maxPages Maximum number of pages to crawl
     * @param bool $includeImages Whether to include images in the sitemap
     */
    public function __construct($baseUrl, $maxPages = 20, $includeImages = false) {
        $this->baseUrl = $baseUrl;
        $this->maxPages = $maxPages;
        $this->includeImages = $includeImages;
        
        // Extract domain
        $parsedUrl = parse_url($baseUrl);
        $this->domain = $parsedUrl['host'];
    }
    
    /**
     * Starts crawling the website
     * 
     * @return array The crawled pages
     */
    public function crawl() {
        // Start with the base URL
        $this->crawlPage($this->baseUrl, 0);
        
        // Return the crawled pages
        return $this->pages;
    }
    
    /**
     * Crawls a single page and extracts links
     * 
     * @param string $url The URL to crawl
     * @param int $depth The current depth level
     */
    private function crawlPage($url, $depth) {
        // Check if we've reached the maximum number of pages
        if (count($this->pages) >= $this->maxPages) {
            return;
        }
        
        // Check if we've already visited this URL
        if (in_array($url, $this->visitedUrls)) {
            return;
        }
        
        // Add to visited URLs
        $this->visitedUrls[] = $url;
        
        // Get page content
        $html = $this->fetchPage($url);
        if (!$html) {
            return;
        }
        
        // Add page to the list
        $this->pages[] = [
            'url' => $url,
            'lastmod' => date('Y-m-d'),
            'priority' => ($depth === 0) ? '1.0' : (1.0 - ($depth * 0.2)),
            'changefreq' => ($depth === 0) ? 'weekly' : 'monthly'
        ];
        
        // Extract images if required
        if ($this->includeImages) {
            $this->extractImages($html, $url);
        }
        
        // Stop crawling if we've reached the max depth (3 levels)
        if ($depth >= 3) {
            return;
        }
        
        // Extract links
        $links = $this->extractLinks($html, $url);
        
        // Crawl each link
        foreach ($links as $link) {
            // Limit the crawling to the maximum number of pages
            if (count($this->pages) >= $this->maxPages) {
                break;
            }
            
            // Crawl the page
            $this->crawlPage($link, $depth + 1);
        }
    }
    
    /**
     * Fetches a page content
     * 
     * @param string $url The URL to fetch
     * @return string|bool The page content or false if failed
     */
    private function fetchPage($url) {
        // Initialize cURL
        $ch = curl_init();
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 seconds timeout
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        
        // Execute cURL
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Close cURL
        curl_close($ch);
        
        // Check if the request was successful
        if ($httpCode >= 200 && $httpCode < 300 && $html) {
            return $html;
        }
        
        return false;
    }
    
    /**
     * Extracts links from a page
     * 
     * @param string $html The page HTML
     * @param string $baseUrl The base URL for relative links
     * @return array The extracted links
     */
    private function extractLinks($html, $baseUrl) {
        $links = [];
        
        // Create a DOMDocument
        $dom = new DOMDocument();
        
        // Load HTML with error suppression
        @$dom->loadHTML($html);
        
        // Create a DOMXPath
        $xpath = new DOMXPath($dom);
        
        // Get all anchors
        $anchors = $xpath->query('//a[@href]');
        
        // Process each anchor
        foreach ($anchors as $anchor) {
            $href = $anchor->getAttribute('href');
            
            // Skip empty links
            if (empty($href)) {
                continue;
            }
            
            // Skip fragment links
            if (strpos($href, '#') === 0) {
                continue;
            }
            
            // Skip JavaScript links
            if (strpos($href, 'javascript:') === 0) {
                continue;
            }
            
            // Skip mailto links
            if (strpos($href, 'mailto:') === 0) {
                continue;
            }
            
            // Skip tel links
            if (strpos($href, 'tel:') === 0) {
                continue;
            }
            
            // Convert relative URL to absolute
            if (strpos($href, 'http') !== 0) {
                if (strpos($href, '/') === 0) {
                    // Absolute path
                    $parsedUrl = parse_url($baseUrl);
                    $href = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $href;
                } else {
                    // Relative path
                    $href = rtrim($baseUrl, '/') . '/' . ltrim($href, '/');
                }
            }
            
            // Skip links to other domains
            $parsedUrl = parse_url($href);
            if (!isset($parsedUrl['host']) || $parsedUrl['host'] !== $this->domain) {
                continue;
            }
            
            // Add to links
            $links[] = $href;
        }
        
        // Remove duplicates
        $links = array_unique($links);
        
        return $links;
    }
    
    /**
     * Extracts images from a page
     * 
     * @param string $html The page HTML
     * @param string $pageUrl The page URL
     */
    private function extractImages($html, $pageUrl) {
        // Create a DOMDocument
        $dom = new DOMDocument();
        
        // Load HTML with error suppression
        @$dom->loadHTML($html);
        
        // Create a DOMXPath
        $xpath = new DOMXPath($dom);
        
        // Get all images
        $images = $xpath->query('//img[@src]');
        
        // Process each image
        foreach ($images as $image) {
            $src = $image->getAttribute('src');
            $alt = $image->getAttribute('alt') ?: 'Image';
            
            // Skip empty sources
            if (empty($src)) {
                continue;
            }
            
            // Skip data URLs
            if (strpos($src, 'data:') === 0) {
                continue;
            }
            
            // Convert relative URL to absolute
            if (strpos($src, 'http') !== 0) {
                if (strpos($src, '/') === 0) {
                    // Absolute path
                    $parsedUrl = parse_url($this->baseUrl);
                    $src = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $src;
                } else {
                    // Relative path
                    $src = rtrim(dirname($pageUrl), '/') . '/' . ltrim($src, '/');
                }
            }
            
            // Skip images from other domains
            $parsedUrl = parse_url($src);
            if (!isset($parsedUrl['host']) || $parsedUrl['host'] !== $this->domain) {
                continue;
            }
            
            // Add to images
            if (!isset($this->images[$pageUrl])) {
                $this->images[$pageUrl] = [];
            }
            
            $this->images[$pageUrl][] = [
                'src' => $src,
                'alt' => $alt
            ];
        }
    }
    
    /**
     * Generates an XML sitemap
     * 
     * @param bool $includeDates Whether to include lastmod dates
     * @return string The XML sitemap
     */
    public function generateSitemap($includeDates = true) {
        // XML header
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        
        // Add schemas
        if ($this->includeImages) {
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . PHP_EOL;
        } else {
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        }
        
        // Add pages
        foreach ($this->pages as $page) {
            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . htmlspecialchars($page['url']) . '</loc>' . PHP_EOL;
            
            if ($includeDates) {
                $xml .= '    <lastmod>' . $page['lastmod'] . '</lastmod>' . PHP_EOL;
            }
            
            $xml .= '    <changefreq>' . $page['changefreq'] . '</changefreq>' . PHP_EOL;
            $xml .= '    <priority>' . $page['priority'] . '</priority>' . PHP_EOL;
            
            // Add images
            if ($this->includeImages && isset($this->images[$page['url']])) {
                foreach ($this->images[$page['url']] as $image) {
                    $xml .= '    <image:image>' . PHP_EOL;
                    $xml .= '      <image:loc>' . htmlspecialchars($image['src']) . '</image:loc>' . PHP_EOL;
                    $xml .= '      <image:title>' . htmlspecialchars($image['alt']) . '</image:title>' . PHP_EOL;
                    $xml .= '    </image:image>' . PHP_EOL;
                }
            }
            
            $xml .= '  </url>' . PHP_EOL;
        }
        
        // Close urlset
        $xml .= '</urlset>';
        
        return $xml;
    }
    
    /**
     * Gets the discovered pages
     * 
     * @return array The discovered pages
     */
    public function getPages() {
        return $this->pages;
    }
    
    /**
     * Gets the discovered images
     * 
     * @return array The discovered images
     */
    public function getImages() {
        return $this->images;
    }
}

// Create and run the crawler
try {
    // Limit execution time for large sites
    set_time_limit(60); // 60 seconds
    
    // Create a crawler
    $crawler = new SiteCrawler($url, $maxPages, $includeImages);
    
    // Crawl the website
    $crawler->crawl();
    
    // Generate the sitemap
    $sitemap = $crawler->generateSitemap($includeDates);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => [
            'sitemap' => $sitemap,
            'pages' => $crawler->getPages(),
            'pageCount' => count($crawler->getPages()),
            'url' => $url
        ]
    ]);
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Failed to crawl website: ' . $e->getMessage()
    ]);
}
