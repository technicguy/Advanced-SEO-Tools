<?php
// api/get-ai-models-api.php - Endpoint to retrieve available AI models

// Include database configuration
require_once '../config.php';

// Set header to return JSON
header('Content-Type: application/json');

// Get active AI models from the database
try {
    // Check if ai_models table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'ai_models'");
    if ($tableCheck->num_rows == 0) {
        // Table doesn't exist, return empty array
        echo json_encode([
            'success' => true,
            'models' => []
        ]);
        exit;
    }
    
    $sql = "SELECT id, name, description, type, model_name, is_default FROM ai_models WHERE status = 'active' ORDER BY type, name";

    $result = $conn->query($sql);
    
    $models = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $models[] = $row;
        }
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'models' => $models
    ]);
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Failed to get AI models: ' . $e->getMessage()
    ]);
}
