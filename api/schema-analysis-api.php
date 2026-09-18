<?php
/**
 * schema-analysis-api.php - API endpoint for schema markup analysis
 * File path: api/schema-analysis-api.php
 */

// Include database configuration
require_once '../config.php';

// Include the schema markup analyzer class
require_once '../includes/schema-generator.php';

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

try {
    // Create analyzer
    $analyzer = new SchemaMarkupAnalyzer($url);
    
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
        'message' => 'Schema markup analysis failed: ' . $e->getMessage()
    ]);
}
