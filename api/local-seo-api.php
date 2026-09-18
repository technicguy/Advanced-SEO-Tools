<?php
// api/local-seo-api.php - Endpoint for local SEO analysis

// Include database configuration and analyzer class
require_once '../config.php';
require_once __DIR__ . '/../includes/security.php';
api_session_boot();

require_once '../includes/local-seo-analyzer.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if API key is configured
if (!defined('GOOGLE_BUSINESS_API_KEY') || empty(GOOGLE_BUSINESS_API_KEY)) {
    error_log("Google Business API Key not configured properly in config.php");
    // We'll continue with limited functionality
}

require_once __DIR__ . '/../includes/security.php';
api_session_boot();

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

// Validate URL — format + private-IP guard
$chk = api_assert_safe_url($url);
if (!$chk['ok']) {
    echo json_encode(['success' => false, 'message' => $chk['reason']]);
    exit;
}

try {
    // Run the local SEO analysis
    $analysis = LocalSEOAnalyzer::analyze($url);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $analysis
    ]);
} catch (Exception $e) {
    // Log the error
    error_log("Local SEO Analysis Error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Analysis failed: ' . $e->getMessage(),
        'error_details' => $e->getMessage() // More details for debugging
    ]);
}