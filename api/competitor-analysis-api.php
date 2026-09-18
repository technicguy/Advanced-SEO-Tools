<?php
/**
 * competitor-analysis-api.php - API endpoint for competitive analysis
 * File path: api/competitor-analysis-api.php
 */

// Include database configuration
require_once '../config.php';

require_once __DIR__ . '/../includes/security.php';
api_session_boot();

// Include the competitive analyzer class
require_once '../includes/competitive-analyzer.php';

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

// Get competitors if provided
$competitors = [];
if (isset($_POST['competitors'])) {
    if (is_string($_POST['competitors'])) {
        // If competitors is a JSON string, decode it
        $competitors = json_decode($_POST['competitors'], true);
    } else if (is_array($_POST['competitors'])) {
        // If competitors is already an array
        $competitors = $_POST['competitors'];
    }
}

// Maximum number of competitors to analyze
$maxCompetitors = isset($_POST['max_competitors']) ? intval($_POST['max_competitors']) : 3;
$maxCompetitors = min(5, max(1, $maxCompetitors)); // Limit to between 1 and 5

// Truncate competitors array if too long
if (count($competitors) > $maxCompetitors) {
    $competitors = array_slice($competitors, 0, $maxCompetitors);
}

// Increase memory limit and execution time for larger analyses
ini_set('memory_limit', '256M');
set_time_limit(120);

try {
    // Create analyzer
    $analyzer = new CompetitiveAnalyzer($url, $competitors);
    
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
        'message' => 'Competitive analysis failed: ' . $e->getMessage()
    ]);
}
