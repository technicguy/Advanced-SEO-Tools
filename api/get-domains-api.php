<?php
// Include database configuration
require_once '../config.php';

// Set header to return JSON
header('Content-Type: application/json');

// Get all domains from the database
try {
    $sql = "SELECT id, domain_name, url, last_checked FROM websites ORDER BY last_checked DESC";
    $result = $conn->query($sql);
    
    $domains = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $domains[] = $row;
        }
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'domains' => $domains
    ]);
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Failed to get domains: ' . $e->getMessage()
    ]);
}
