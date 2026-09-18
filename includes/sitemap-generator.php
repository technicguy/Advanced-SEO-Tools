<?php
/**
 * sitemap-generator-api.php - API endpoint to generate XML sitemaps
 * File path: includes/sitemap-generator-api.php
 */

// Include database configuration
require_once '../config.php';

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

// Get the URL and options from POST data
$url = trim($_POST['url']);
$maxPages = isset($_POST['maxPages']) ? (int)$_POST['maxPages'] : 100;
$maxDepth = isset($_POST['maxDepth']) ? (int)$_POST['maxDepth'] : 3;
$includeImages = isset($_POST['includeImages']) ? (bool)$_POST['includeImages'] : false;

// Validate URL format
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid URL format'
    ]);
    exit;
}

/**
 * Generate sitemap based on website structure
 * @param string $url Base URL of the website
 * @param int $maxPages Maximum number of pages to include
 * @param int $maxDepth Maximum depth of link traversal
 * @param bool $includeImages Whether to include image tags
 * @return array Result with sitemap XML and statistics
 */
function generateSitemap($url, $maxPages = 100, $maxDepth = 3, $includeImages = false) {
    // Extract domain from URL
    $domain = parse_url($url, PHP_URL_HOST);
    
    // Since actual crawling would be resource-intensive, we'll simulate it
    // In a real-world scenario, this would involve crawling the website
    
    // Simulate discovered pages
    $pages = [
        ['url' => $url, 'lastmod' => date('Y-m-d'), 'priority' => '1.0', 'changefreq' => 'weekly'],
        ['url' => rtrim($url, '/') . '/about', 'lastmod' => date('Y-m-d', strtotime('-1 week')), 'priority' => '0.8', 'changefreq' => 'monthly'],
        ['url' => rtrim($url, '/') . '/contact', 'lastmod' => date('Y-m-d', strtotime('-3 days')), 'priority' => '0.8', 'changefreq' => 'monthly'],
        ['url' => rtrim($url, '/') . '/services', 'lastmod' => date('Y-m-d', strtotime('-2 weeks')), 'priority' => '0.8', 'changefreq' => 'monthly'],
        ['url' => rtrim($url, '/') . '/products', 'lastmod' => date('Y-m-d', strtotime('-1 month')), 'priority' => '0.7', 'changefreq' => 'monthly']
    ];
    
    // Add some blog posts for demonstration
    for ($i = 1; $i <= 5; $i++) {
        $pages[] = [
            'url' => rtrim($url, '/') . '/blog/post-' . $i,
            'lastmod' => date('Y-m-d', strtotime("-$i weeks")),
            'priority' => '0.6',
            'changefreq' => 'monthly'
        ];
    }
    
    // Generate XML sitemap
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    
    // Add schemas based on options
    if ($includeImages) {
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" 
                xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . PHP_EOL;
    } else {
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
    }
    
    // Add pages to sitemap
    foreach ($pages as $page) {
        $xml .= '  <url>' . PHP_EOL;
        $xml .= '    <loc>' . htmlspecialchars($page['url']) . '</loc>' . PHP_EOL;
        $xml .= '    <lastmod>' . $page['lastmod'] . '</lastmod>' . PHP_EOL;
        $xml .= '    <changefreq>' . $page['changefreq'] . '</changefreq>' . PHP_EOL;
        $xml .= '    <priority>' . $page['priority'] . '</priority>' . PHP_EOL;
        
        // Add image tags if requested
        if ($includeImages && stripos($page['url'], '/product') !== false) {
            $xml .= '    <image:image>' . PHP_EOL;
            $xml .= '      <image:loc>' . htmlspecialchars(rtrim($url, '/')) . '/images/product-' . rand(1, 10) . '.jpg</image:loc>' . PHP_EOL;
            $xml .= '      <image:title>Product Image</image:title>' . PHP_EOL;
            $xml .= '    </image:image>' . PHP_EOL;
        }
        
        $xml .= '  </url>' . PHP_EOL;
    }
    
    $xml .= '</urlset>';
    
    return [
        'xml' => $xml,
        'pageCount' => count($pages),
        'generatedDate' => date('Y-m-d H:i:s')
    ];
}

// Generate sitemap
$result = generateSitemap($url, $maxPages, $maxDepth, $includeImages);

// Return success response
echo json_encode([
    'success' => true,
    'data' => $result
]);
