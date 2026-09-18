<?php
/**
 * export-report-api.php - Endpoint for exporting analysis results
 */

require_once __DIR__ . '/../config.php';

// Set header to JSON by default
header('Content-Type: application/json');

// Check if data is submitted
if (!isset($_GET['website_id'])) {
    echo json_encode(['success' => false, 'message' => 'Website ID is required']);
    exit;
}

$websiteId = (int)$_GET['website_id'];
$format = $_GET['format'] ?? 'json';

// Fetch website data
$stmt = $conn->prepare("SELECT * FROM websites WHERE id = ?");
$stmt->bind_param("i", $websiteId);
$stmt->execute();
$websiteResult = $stmt->get_result();

if ($websiteResult->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Website not found']);
    exit;
}

$website = $websiteResult->fetch_assoc();

// Fetch analysis results (simplified for this example)
$resultsData = [
    'website' => $website,
    'analysis_date' => date('Y-m-d H:i:s'),
    'technical_seo' => fetchLatestResults($conn, 'technical_seo', $websiteId),
    'onpage_seo' => fetchLatestResults($conn, 'onpage_seo', $websiteId),
    'offpage_seo' => fetchLatestResults($conn, 'offpage_seo', $websiteId),
    'keyword_analysis' => fetchLatestResults($conn, 'keyword_analysis', $websiteId),
    'content_analysis' => fetchLatestResults($conn, 'content_analysis', $websiteId),
    'link_analysis' => fetchLatestResults($conn, 'link_analysis', $websiteId),
    'image_analysis' => fetchLatestResults($conn, 'image_analysis', $websiteId),
    'page_structure' => fetchLatestResults($conn, 'page_structure', $websiteId),
    'core_web_vitals' => fetchLatestResults($conn, 'core_web_vitals', $websiteId),
    'competitor_analysis' => fetchLatestResults($conn, 'competitive_analysis', $websiteId),
    'local_seo' => fetchLatestResults($conn, 'local_seo', $websiteId),
    'content_gap' => fetchLatestResults($conn, 'content_gap', $websiteId),
    'ai_recommendations' => fetchLatestResults($conn, 'ai_recommendations', $websiteId)
];

/**
 * Helper to fetch latest results for a table
 */
function fetchLatestResults($db, $table, $websiteId) {
    $dateCol = ($table === 'ai_recommendations') ? 'created_at' : 'scan_date';
    $sql = "SELECT * FROM $table WHERE website_id = ? ORDER BY $dateCol DESC LIMIT 1";
    
    if ($stmt = $db->prepare($sql)) {
        $stmt->bind_param("i", $websiteId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc() ?: null;
    }
    
    return null;
}

// Handle different export formats
switch ($format) {
    case 'csv':
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="seo_report_' . $website['domain_name'] . '.csv"');
        $output = fopen('php://output', 'w');
        
        // Basic CSV headers and data
        fputcsv($output, ['Website', 'URL', 'Analysis Date']);
        fputcsv($output, [$website['domain_name'], $website['url'], $resultsData['analysis_date']]);
        fputcsv($output, []);
        fputcsv($output, ['Component', 'Score/Status']);
        
        if ($resultsData['technical_seo']) {
            fputcsv($output, ['Crawlability', $resultsData['technical_seo']['crawlability_score'] ?? 'N/A']);
            fputcsv($output, ['Indexability', $resultsData['technical_seo']['indexability_score'] ?? 'N/A']);
        }
        
        // Add more components...
        fclose($output);
        exit;

    case 'pdf':
        // PDF generation would require a library like Dompdf or TCPDF
        // For now, we will output as HTML for the user to print/save as PDF
        header('Content-Type: text/html');
        echo "<h1>SEO Report for " . htmlspecialchars($website['domain_name']) . "</h1>";
        echo "<p>Analysis Date: " . $resultsData['analysis_date'] . "</p>";
        echo "<hr>";
        echo "<h3>Technical SEO Analysis</h3>";
        if ($resultsData['technical_seo']) {
            echo "<p>Crawlability Score: " . ($resultsData['technical_seo']['crawlability_score'] ?? 'N/A') . "</p>";
            echo "<p>Indexability Score: " . ($resultsData['technical_seo']['indexability_score'] ?? 'N/A') . "</p>";
        } else {
            echo "<p>No technical SEO analysis results found.</p>";
        }
        // ... (Add more components to the HTML report)
        exit;

    case 'json':
    default:
        header('Content-Type: application/json');
        echo json_encode($resultsData, JSON_PRETTY_PRINT);
        exit;
}
