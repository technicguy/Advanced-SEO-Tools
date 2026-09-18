<?php
// api/content-gap-api.php - Endpoint for content gap analysis

// Include database configuration and analyzer class
require_once '../config.php';
require_once '../includes/content-gap-analyzer.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if URL is provided
if (!isset($_POST['url']) || empty($_POST['url'])) {
    echo json_encode([
        'success' => false,
        'message' => 'URL is required'
    ]);
    exit;
}

// Get URL from POST data
$url = $_POST['url'];

// Validate URL
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid URL format'
    ]);
    exit;
}

// Get competitors list if provided
$competitors = [];
if (isset($_POST['competitors']) && !empty($_POST['competitors'])) {
    $competitors = json_decode($_POST['competitors'], true);
    
    // Validate each competitor URL
    foreach ($competitors as $competitor) {
        if (!filter_var($competitor, FILTER_VALIDATE_URL)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid competitor URL: ' . $competitor
            ]);
            exit;
        }
    }
}

try {
    // Run the content gap analysis
    $analysis = ContentGapAnalyzer::analyze($url, $competitors);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $analysis
    ]);
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Analysis failed: ' . $e->getMessage()
    ]);
}