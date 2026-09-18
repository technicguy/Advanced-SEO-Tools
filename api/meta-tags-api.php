<?php
// Include database configuration
require_once '../config.php';
require_once '../includes/meta-analyzer.php';

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

try {
    // Create meta analyzer instance
    $analyzer = new MetaAnalyzer($url);
    
    // Run analysis
    $analysisResults = $analyzer->analyze();
    
    if (!$analysisResults['success']) {
        echo json_encode([
            'success' => false,
            'message' => $analysisResults['message']
        ]);
        exit;
    }
    
    // Get the website ID if available (for saving to database)
    $websiteId = null;
    if (isset($_POST['website_id']) && !empty($_POST['website_id'])) {
        $websiteId = intval($_POST['website_id']);
    } else {
        // Try to find website ID from URL
        $stmt = $conn->prepare("SELECT id FROM websites WHERE url = ?");
        $stmt->bind_param("s", $url);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $websiteId = $row['id'];
        }
    }
    
    // If we have a website ID, save results to database
    if ($websiteId) {
        // Prepare data for storage
        $pageUrl = $url;
        $title = isset($analysisResults['meta_tags']['title']) ? $analysisResults['meta_tags']['title'] : '';
        $titleLength = isset($analysisResults['meta_tags']['title_length']) ? $analysisResults['meta_tags']['title_length'] : 0;
        $metaDescription = isset($analysisResults['meta_tags']['description']) ? $analysisResults['meta_tags']['description'] : '';
        $metaDescriptionLength = isset($analysisResults['meta_tags']['description_length']) ? $analysisResults['meta_tags']['description_length'] : 0;
        $metaKeywords = isset($analysisResults['meta_tags']['keywords']) ? $analysisResults['meta_tags']['keywords'] : '';

        // Fix: Convert boolean or string values to integers (0 or 1)
        $hasViewport = isset($analysisResults['meta_tags']['viewport']) ? 1 : 0;
        $hasRobots = isset($analysisResults['meta_tags']['robots']) ? 1 : 0;
        $hasCanonical = isset($analysisResults['meta_tags']['canonical']) ? 1 : 0;
        
        // Convert boolean or string values to integers
        $hasOgTags = isset($analysisResults['meta_tags']['has_open_graph']) && 
                    $analysisResults['meta_tags']['has_open_graph'] ? 1 : 0;
        
        $hasTwitterCards = isset($analysisResults['meta_tags']['has_twitter_cards']) && 
                          $analysisResults['meta_tags']['has_twitter_cards'] ? 1 : 0;
        
        // Check if meta_tags_analysis table exists and create it if it doesn't
        $tableCheck = $conn->query("SHOW TABLES LIKE 'meta_tags_analysis'");
        if ($tableCheck->num_rows == 0) {
            $createTableSQL = "CREATE TABLE IF NOT EXISTS `meta_tags_analysis` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `website_id` int(11) NOT NULL,
                `page_url` varchar(255) NOT NULL,
                `title` varchar(255),
                `title_length` int(11),
                `meta_description` text,
                `meta_description_length` int(11),
                `meta_keywords` text,
                `has_viewport` tinyint(1),
                `has_robots` tinyint(1),
                `has_canonical` tinyint(1),
                `has_og_tags` tinyint(1),
                `has_twitter_cards` tinyint(1),
                `scan_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `website_id` (`website_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            
            $conn->query($createTableSQL);
        }
        
        // Log the values for debugging
        error_log("Meta Tags Analysis Values to Insert:");
        error_log("has_viewport: " . var_export($hasViewport, true));
        error_log("has_robots: " . var_export($hasRobots, true));
        error_log("has_canonical: " . var_export($hasCanonical, true));
        error_log("has_og_tags: " . var_export($hasOgTags, true));
        error_log("has_twitter_cards: " . var_export($hasTwitterCards, true));
        
        // Save to database - Fixed: Using correct parameter types (i for integer)
        $stmt = $conn->prepare("INSERT INTO meta_tags_analysis (website_id, page_url, title, title_length, 
                               meta_description, meta_description_length, meta_keywords, has_viewport, 
                               has_robots, has_canonical, has_og_tags, has_twitter_cards) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param("issisisiiiii", $websiteId, $pageUrl, $title, $titleLength, 
                          $metaDescription, $metaDescriptionLength, $metaKeywords, $hasViewport, 
                          $hasRobots, $hasCanonical, $hasOgTags, $hasTwitterCards);
        
        $stmt->execute();
        
        // Check for errors
        if ($stmt->error) {
            // Log the error but continue
            error_log("Error saving meta tags analysis: " . $stmt->error);
        } else {
            error_log("Meta tags analysis saved successfully for URL: " . $url);
        }
    }
    
    // Return analysis results
    echo json_encode([
        'success' => true,
        'data' => $analysisResults
    ]);
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Analysis failed: ' . $e->getMessage()
    ]);
}
