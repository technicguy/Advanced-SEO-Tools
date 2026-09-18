<?php
/**
 * robots-txt-generator-api.php - API endpoint to generate robots.txt files
 * File path: api/robots-txt-generator-api.php
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

// Get parameters from POST data
$url = trim($_POST['url']);
$disallowPaths = isset($_POST['disallowPaths']) ? json_decode($_POST['disallowPaths'], true) : [];
$includeSitemap = isset($_POST['includeSitemap']) ? (bool)$_POST['includeSitemap'] : true;

// Validate URL format
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid URL format'
    ]);
    exit;
}

/**
 * Generate robots.txt content
 * @param string $url Base URL of the website
 * @param array $disallowPaths Paths to disallow
 * @param bool $includeSitemap Whether to include sitemap reference
 * @return array Result with robots.txt content
 */
function generateRobotsTxt($url, $disallowPaths = [], $includeSitemap = true) {
    // Extract domain from URL
    $domain = parse_url($url, PHP_URL_HOST);
    
    // Generate robots.txt content
    $robotsTxt = "# robots.txt for {$domain}\n";
    $robotsTxt .= "# Generated on " . date('Y-m-d') . "\n\n";
    
    $robotsTxt .= "User-agent: *\n";
    $robotsTxt .= "Allow: /\n\n";
    
    // Add disallow paths
    if (!empty($disallowPaths)) {
        $robotsTxt .= "# Disallow admin and private areas\n";
        foreach ($disallowPaths as $path) {
            $robotsTxt .= "Disallow: {$path}\n";
        }
        $robotsTxt .= "\n";
    }
    
    // Add sitemap
    if ($includeSitemap) {
        $sitemapUrl = rtrim($url, '/') . '/sitemap.xml';
        $robotsTxt .= "# Sitemap location\n";
        $robotsTxt .= "Sitemap: {$sitemapUrl}\n";
    }
    
    return [
        'content' => $robotsTxt,
        'generatedDate' => date('Y-m-d H:i:s')
    ];
}

// Generate robots.txt
$result = generateRobotsTxt($url, $disallowPaths, $includeSitemap);

// Return success response
echo json_encode([
    'success' => true,
    'data' => $result
]);
