<?php
/**
 * link-analysis-api.php - API endpoint for link analysis
 * File path: api/link-analysis-api.php
 */

// Include database configuration
require_once '../config.php';

require_once __DIR__ . '/../includes/security.php';
api_session_boot();

// Include the link analyzer class
require_once '../includes/link-analyzer.php';

// Include the broken link checker if it exists
if (file_exists('../includes/broken-link-checker.php')) {
    require_once '../includes/broken-link-checker.php';
}

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

// Get max pages parameter (default to 10)
$maxPages = isset($_POST['max_pages']) ? intval($_POST['max_pages']) : 10;
$maxPages = min(20, max(1, $maxPages)); // Limit to between 1 and 20

// Validate URL format
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid URL format'
    ]);
    exit;
}

// Simulate delay for analysis time (can be removed in production)
sleep(1);

try {
    // Create link analyzer
    $analyzer = new LinkAnalyzer($url, $maxPages);
    
    // Run the analysis
    $analysisData = $analyzer->analyze();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $analysisData
    ]);
} catch (Exception $e) {
    // Handle errors
    echo json_encode([
        'success' => false,
        'message' => 'Link analysis failed: ' . $e->getMessage()
    ]);
}