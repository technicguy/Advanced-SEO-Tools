<?php
/**
 * performance-api.php - API endpoint for Core Web Vitals analysis
 * File path: api/performance-api.php
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

/**
 * Analyze Core Web Vitals
 * @param string $url URL to analyze
 * @return array Analysis results
 */
function analyzePerformance($url) {
    // In a production environment, you would integrate with tools like:
    // - Google PageSpeed Insights API
    // - Chrome UX Report API
    // - Lighthouse API
    
    // For development, we'll generate simulated Core Web Vitals data
    
    // Use consistent random values based on URL for testing
    $seed = crc32($url) % 100;
    mt_srand($seed);
    
    // Overall score (0-100)
    $overallScore = mt_rand(50, 95);
    
    // LCP (Largest Contentful Paint) - Good < 2.5s, Needs Improvement < 4s, Poor > 4s
    $lcpValue = mt_rand(1500, 5000); // 1.5s to 5s
    $lcpScore = $lcpValue < 2500 ? 90 : ($lcpValue < 4000 ? 60 : 30);
    $lcpStatus = $lcpValue < 2500 ? 'good' : ($lcpValue < 4000 ? 'needs-improvement' : 'poor');
    
    // FID (First Input Delay) - Good < 100ms, Needs Improvement < 300ms, Poor > 300ms
    $fidValue = mt_rand(50, 350); // 50ms to 350ms
    $fidScore = $fidValue < 100 ? 90 : ($fidValue < 300 ? 60 : 30);
    $fidStatus = $fidValue < 100 ? 'good' : ($fidValue < 300 ? 'needs-improvement' : 'poor');
    
    // CLS (Cumulative Layout Shift) - Good < 0.1, Needs Improvement < 0.25, Poor > 0.25
    $clsValue = mt_rand(5, 35) / 100; // 0.05 to 0.35
    $clsScore = $clsValue < 0.1 ? 90 : ($clsValue < 0.25 ? 60 : 30);
    $clsStatus = $clsValue < 0.1 ? 'good' : ($clsValue < 0.25 ? 'needs-improvement' : 'poor');
    
    // TTFB (Time to First Byte) - Good < 800ms, Needs Improvement < 1800ms, Poor > 1800ms
    $ttfbValue = mt_rand(400, 2200); // 400ms to 2200ms
    $ttfbScore = $ttfbValue < 800 ? 90 : ($ttfbValue < 1800 ? 60 : 30);
    $ttfbStatus = $ttfbValue < 800 ? 'good' : ($ttfbValue < 1800 ? 'needs-improvement' : 'poor');
    
    // Generate recommendations based on scores
    $recommendations = [];
    
    if ($lcpScore < 70) {
        $recommendations[] = [
            'title' => 'Improve Largest Contentful Paint',
            'description' => 'Optimize loading of key visual elements to improve perceived page load speed.',
            'importance' => $lcpScore < 40 ? 'high' : 'medium',
            'implementation' => 'Optimize image loading, eliminate render-blocking resources, and implement critical CSS.'
        ];
    }
    
    if ($fidScore < 70) {
        $recommendations[] = [
            'title' => 'Enhance First Input Delay',
            'description' => 'Improve responsiveness to user interactions.',
            'importance' => $fidScore < 40 ? 'high' : 'medium',
            'implementation' => 'Reduce JavaScript execution time, break up long tasks, and optimize event handlers.'
        ];
    }
    
    if ($clsScore < 70) {
        $recommendations[] = [
            'title' => 'Minimize Layout Shifts',
            'description' => 'Reduce unexpected layout shifts during page load.',
            'importance' => $clsScore < 40 ? 'high' : 'medium',
            'implementation' => 'Always specify dimensions for images and videos, avoid inserting content above existing content, and use transform animations.'
        ];
    }
    
    if ($ttfbScore < 70) {
        $recommendations[] = [
            'title' => 'Improve Server Response Time',
            'description' => 'Reduce the time it takes for the server to respond to requests.',
            'importance' => $ttfbScore < 40 ? 'high' : 'medium',
            'implementation' => 'Optimize server performance, implement caching, and consider using a CDN.'
        ];
    }
    
    // Put everything together in the expected format
    return [
        'overall_score' => $overallScore,
        'metrics' => [
            'lcp' => [
                'name' => 'Largest Contentful Paint',
                'value' => $lcpValue,
                'score' => $lcpScore,
                'status' => $lcpStatus,
                'unit' => 'ms'
            ],
            'fid' => [
                'name' => 'First Input Delay',
                'value' => $fidValue,
                'score' => $fidScore,
                'status' => $fidStatus,
                'unit' => 'ms'
            ],
            'cls' => [
                'name' => 'Cumulative Layout Shift',
                'value' => $clsValue,
                'score' => $clsScore,
                'status' => $clsStatus,
                'unit' => ''
            ],
            'ttfb' => [
                'name' => 'Time to First Byte',
                'value' => $ttfbValue,
                'score' => $ttfbScore,
                'status' => $ttfbStatus,
                'unit' => 'ms'
            ]
        ],
        'recommendations' => $recommendations
    ];
}

// Analyze performance
$performanceData = analyzePerformance($url);

// Return success response
echo json_encode([
    'success' => true,
    'data' => $performanceData
]);
