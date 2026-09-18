<?php
/**
 * page-structure-api.php - API endpoint for page structure analysis
 * File path: api/page-structure-api.php
 */

// Include database configuration
require_once '../config.php';

require_once __DIR__ . '/../includes/security.php';
api_session_boot();

// Include the page structure analyzer class
require_once '../includes/page-structure-analyzer.php';

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

// Get max pages parameter (default to 5)
$maxPages = isset($_POST['max_pages']) ? intval($_POST['max_pages']) : 5;
$maxPages = min(10, max(1, $maxPages)); // Limit to between 1 and 10

// Validate URL format
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid URL format'
    ]);
    exit;
}

// Increase memory limit and execution time for larger sites
ini_set('memory_limit', '256M');
set_time_limit(120);

try {
    // Create analyzer
    $analyzer = new PageStructureAnalyzer($url, $maxPages);
    
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
        'message' => 'Page structure analysis failed: ' . $e->getMessage()
    ]);
}
