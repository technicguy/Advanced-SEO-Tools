<?php
/**
 * image-analysis-api.php - API endpoint for image analysis
 * File path: api/image-analysis-api.php
 */

// Include database configuration
require_once '../config.php';

require_once __DIR__ . '/../includes/security.php';
api_session_boot();

// Include the broken link checker if it exists
if (file_exists('../includes/broken-link-checker.php')) {
    require_once '../includes/broken-link-checker.php';
}

// Include the image analyzer class
require_once '../includes/image-analyzer.php';

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

// Simulate delay for analysis time (can be removed in production)
sleep(1);

try {
    // Create image analyzer
    $analyzer = new ImageAnalyzer($url, $maxPages);
    
    // Run the analysis
    $analysisData = $analyzer->analyze();
    
    // Extract data for database storage
    $domain = parse_url($url, PHP_URL_HOST);
    if (substr($domain, 0, 4) === 'www.') {
        $domain = substr($domain, 4);
    }
    
    // Extract website ID or create new website entry
    $websiteId = null;
    $stmt = $conn->prepare("SELECT id FROM websites WHERE url = ?");
    $stmt->bind_param("s", $url);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $websiteId = $result->fetch_assoc()['id'];
    } else {
        $stmt = $conn->prepare("INSERT INTO websites (domain_name, url) VALUES (?, ?)");
        $stmt->bind_param("ss", $domain, $url);
        $stmt->execute();
        $websiteId = $conn->insert_id;
    }
    
    // Store analysis data in database
    foreach ($analysisData['image_details'] as $pageData) {
        $pageUrl = $pageData['url'];
        $totalImages = count($pageData['images']);
        
        // Count various image types
        $imagesWithAlt = 0;
        $imagesWithoutAlt = 0;
        $oversizedImages = 0;
        $lazyLoadedImages = 0;
        
        foreach ($pageData['images'] as $image) {
            if ($image['has_alt']) {
                $imagesWithAlt++;
            } else {
                $imagesWithoutAlt++;
            }
            
            if ($image['is_oversized']) {
                $oversizedImages++;
            }
            
            if ($image['uses_lazy_loading']) {
                $lazyLoadedImages++;
            }
        }
        
        // Store the individual page data
        $imageDetailsJson = json_encode($pageData['images']);
        
        $stmt = $conn->prepare("INSERT INTO image_analysis 
                                (website_id, page_url, total_images, images_with_alt, 
                                images_without_alt, oversized_images, lazy_loaded_images, 
                                image_details_json) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param(
            "isiiiiis", 
            $websiteId, 
            $pageUrl, 
            $totalImages, 
            $imagesWithAlt, 
            $imagesWithoutAlt, 
            $oversizedImages, 
            $lazyLoadedImages, 
            $imageDetailsJson
        );
        
        $stmt->execute();
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $analysisData
    ]);
} catch (Exception $e) {
    // Handle errors
    echo json_encode([
        'success' => false,
        'message' => 'Image analysis failed: ' . $e->getMessage()
    ]);
}