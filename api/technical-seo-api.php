<?php
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

// Get the URL from POST data
$url = trim($_POST['url']);

// Validate URL format
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid URL format'
    ]);
    exit;
}

// Extract domain name from URL
$domain = parse_url($url, PHP_URL_HOST);
if (substr($domain, 0, 4) === 'www.') {
    $domain = substr($domain, 4);
}

// Function to check if a URL is accessible
function isUrlAccessible($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ($httpCode >= 200 && $httpCode < 400);
}

// Function to fetch robots.txt and analyze it
function analyzeRobotsTxt($url) {
    $parsedUrl = parse_url($url);
    $robotsUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . '/robots.txt';
    
    // Use the globally defined makeHttpRequest function
    $robotsTxt = makeHttpRequest($robotsUrl);
    $issues = [];
    
    if (!$robotsTxt) {
        $issues[] = [
            'description' => 'robots.txt file not found',
            'severity' => 'medium'
        ];
        return ['issues' => $issues, 'score' => 70];
    }
    
    // Check for Disallow: / which blocks entire site
    if (preg_match('/Disallow:\s*\/\s*$/m', $robotsTxt)) {
        $issues[] = [
            'description' => 'robots.txt is blocking the entire site',
            'severity' => 'high'
        ];
    }
    
    // Check for sitemap declaration
    if (!preg_match('/Sitemap:/i', $robotsTxt)) {
        $issues[] = [
            'description' => 'No sitemap declared in robots.txt',
            'severity' => 'low'
        ];
    }
    
    // Determine score based on issues
    $score = 100;
    foreach ($issues as $issue) {
        if ($issue['severity'] === 'high') {
            $score -= 30;
        } elseif ($issue['severity'] === 'medium') {
            $score -= 15;
        } elseif ($issue['severity'] === 'low') {
            $score -= 5;
        }
    }
    
    return ['issues' => $issues, 'score' => max(0, $score)];
}

// Function to check sitemap.xml
function analyzeSitemap($url) {
    $parsedUrl = parse_url($url);
    $sitemapUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . '/sitemap.xml';
    
    // Use the globally defined makeHttpRequest function
    $sitemap = makeHttpRequest($sitemapUrl);
    $issues = [];
    
    if (!$sitemap) {
        $issues[] = [
            'description' => 'sitemap.xml file not found',
            'severity' => 'medium'
        ];
        return ['issues' => $issues, 'score' => 70];
    }
    
    // Check if sitemap is valid XML
    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($sitemap);
    if ($xml === false) {
        $issues[] = [
            'description' => 'sitemap.xml is not valid XML',
            'severity' => 'high'
        ];
    }
    
    // Determine score based on issues
    $score = 100;
    foreach ($issues as $issue) {
        if ($issue['severity'] === 'high') {
            $score -= 30;
        } elseif ($issue['severity'] === 'medium') {
            $score -= 15;
        } elseif ($issue['severity'] === 'low') {
            $score -= 5;
        }
    }
    
    return ['issues' => $issues, 'score' => max(0, $score)];
}

// Function to check HTTP vs HTTPS
function checkHttps($url) {
    return parse_url($url, PHP_URL_SCHEME) === 'https';
}

// Function to check URL structure
function analyzeUrlStructure($url) {
    $parsedUrl = parse_url($url);
    $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
    
    $issues = [];
    
    // Check for query parameters
    if (isset($parsedUrl['query'])) {
        $issues[] = [
            'description' => 'URL contains query parameters which are not SEO friendly',
            'severity' => 'medium'
        ];
    }
    
    // Check for long URLs
    if (strlen($url) > 100) {
        $issues[] = [
            'description' => 'URL is too long (over 100 characters)',
            'severity' => 'low'
        ];
    }
    
    // Check for uppercase letters in URL
    if (preg_match('/[A-Z]/', $path)) {
        $issues[] = [
            'description' => 'URL contains uppercase letters, which can cause duplicate content issues',
            'severity' => 'medium'
        ];
    }
    
    // Check for non-alphanumeric characters
    if (preg_match('/[^a-zA-Z0-9\-\_\/\.]/', $path)) {
        $issues[] = [
            'description' => 'URL contains special characters which should be avoided',
            'severity' => 'low'
        ];
    }
    
    // Determine score based on issues
    $score = 100;
    foreach ($issues as $issue) {
        if ($issue['severity'] === 'high') {
            $score -= 30;
        } elseif ($issue['severity'] === 'medium') {
            $score -= 15;
        } elseif ($issue['severity'] === 'low') {
            $score -= 5;
        }
    }
    
    return ['issues' => $issues, 'score' => max(0, $score)];
}

// Function to check for schema markup
function checkSchemaMarkup($html) {
    // Look for schema.org in the HTML
    $hasSchema = (strpos($html, 'schema.org') !== false);
    
    // Look for common schema types
    $hasJsonLd = (strpos($html, 'application/ld+json') !== false);
    
    return $hasSchema || $hasJsonLd;
}

// Function to analyze technical SEO without paid APIs
function analyzeTechnicalSEO($url, $domain) {
    $issues = [];
    $html = makeHttpRequest($url);
    
    // Check if URL is accessible
    if (!isUrlAccessible($url)) {
        $issues[] = [
            'description' => 'URL is not accessible (returns error code)',
            'severity' => 'high'
        ];
    }
    
    // Check HTTPS
    $hasSSL = checkHttps($url);
    if (!$hasSSL) {
        $issues[] = [
            'description' => 'Site is not using HTTPS, which is important for security and SEO',
            'severity' => 'high'
        ];
    }
    
    // Check robots.txt
    $robotsAnalysis = analyzeRobotsTxt($url);
    $issues = array_merge($issues, $robotsAnalysis['issues']);
    $crawlabilityScore = $robotsAnalysis['score'];
    
    // Check sitemap.xml
    $sitemapAnalysis = analyzeSitemap($url);
    $issues = array_merge($issues, $sitemapAnalysis['issues']);
    $indexabilityScore = $sitemapAnalysis['score'];
    
    // Check URL structure
    $urlAnalysis = analyzeUrlStructure($url);
    $issues = array_merge($issues, $urlAnalysis['issues']);
    
    // Check schema markup
    $schemaMarkupValid = false;
    if ($html) {
        $schemaMarkupValid = checkSchemaMarkup($html);
        if (!$schemaMarkupValid) {
            $issues[] = [
                'description' => 'No schema markup detected on the page',
                'severity' => 'medium'
            ];
        }
    }
    
    // Try to use Google PageSpeed Insights for performance metrics (free with limits)
    $siteSpeedScore = 0;
    $mobileFriendlyScore = 0;
    
    try {
        // Since callPageSpeedApi is also defined in config.php, we'll just use
        // a placeholder approach here to avoid conflicts
        $pageSpeedData = [];  // Skip PageSpeed API call to avoid conflicts
        
        // Simplified performance metrics estimation
        // Simple heuristic for site speed based on load time
        $startTime = microtime(true);
        makeHttpRequest($url);
        $endTime = microtime(true);
        $loadTime = $endTime - $startTime;
        
        if ($loadTime < 1) {
            $siteSpeedScore = 90;
        } elseif ($loadTime < 2) {
            $siteSpeedScore = 75;
        } elseif ($loadTime < 3) {
            $siteSpeedScore = 60;
        } elseif ($loadTime < 5) {
            $siteSpeedScore = 40;
        } else {
            $siteSpeedScore = 20;
        }
        
        // Add issue if load time is slow
        if ($loadTime > 3) {
            $issues[] = [
                'description' => 'Page load time is slow (' . round($loadTime, 2) . ' seconds)',
                'severity' => 'high'
            ];
        }
    } catch (Exception $e) {
        error_log("Error in performance analysis: " . $e->getMessage());
    }
    
    // If mobile-friendly score is not available, check for viewport meta tag as simple indicator
    if ($mobileFriendlyScore == 0 && $html) {
        if (strpos($html, 'viewport') !== false) {
            $mobileFriendlyScore = 70; // Basic responsive design detected
        } else {
            $mobileFriendlyScore = 30; // No responsive design detected
            $issues[] = [
                'description' => 'No viewport meta tag found, site may not be mobile-friendly',
                'severity' => 'high'
            ];
        }
    }
    
    // Check for has_sitemap and has_robots_txt explicitly
    $parsedUrl = parse_url($url);
    $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
    
    $robotsUrl = $baseUrl . '/robots.txt';
    $robotsRequest = makeHttpRequest($robotsUrl);
    $has_robots_txt = ($robotsRequest !== false);
    
    $sitemapUrl = $baseUrl . '/sitemap.xml';
    $sitemapRequest = makeHttpRequest($sitemapUrl);
    $has_sitemap = ($sitemapRequest !== false);
    
    return [
        'crawlability_score' => $crawlabilityScore,
        'indexability_score' => $indexabilityScore,
        'site_speed_score' => $siteSpeedScore,
        'mobile_friendly_score' => $mobileFriendlyScore,
        'has_ssl' => $hasSSL,
        'schema_markup_valid' => $schemaMarkupValid,
        'has_robots_txt' => $has_robots_txt,
        'has_sitemap' => $has_sitemap,
        'issues' => $issues,
        'url' => $url
    ];
}

// Simulate delay for analysis time
sleep(1);

// Get technical SEO analysis
$technicalSEOData = analyzeTechnicalSEO($url, $domain);

// Return success response
echo json_encode([
    'success' => true,
    'data' => $technicalSEOData
]);
