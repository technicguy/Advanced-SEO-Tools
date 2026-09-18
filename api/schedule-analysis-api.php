<?php
/**
 * schedule-analysis-api.php - Endpoint for scheduling recurring audits
 */

require_once __DIR__ . '/../config.php';

// Set header to JSON
header('Content-Type: application/json');

// Check if scheduled_audits table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'scheduled_audits'");
if ($tableCheck->num_rows === 0) {
    // Create the table
    $createTableSQL = "CREATE TABLE IF NOT EXISTS scheduled_audits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        website_id INT NOT NULL,
        frequency ENUM('daily', 'weekly', 'monthly') NOT NULL,
        last_run TIMESTAMP NULL,
        next_run TIMESTAMP NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE
    )";
    $conn->query($createTableSQL);
}

// Handle request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $websiteId = (int)($_POST['website_id'] ?? 0);
    $frequency = $_POST['frequency'] ?? 'weekly';
    $isActive = (int)($_POST['is_active'] ?? 1);

    if ($websiteId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid website ID']);
        exit;
    }

    // Calculate next run date based on frequency
    $nextRun = date('Y-m-d H:i:s');
    switch ($frequency) {
        case 'daily': $nextRun = date('Y-m-d H:i:s', strtotime('+1 day')); break;
        case 'weekly': $nextRun = date('Y-m-d H:i:s', strtotime('+1 week')); break;
        case 'monthly': $nextRun = date('Y-m-d H:i:s', strtotime('+1 month')); break;
    }

    // Check if schedule already exists
    $stmt = $conn->prepare("SELECT id FROM scheduled_audits WHERE website_id = ?");
    $stmt->bind_param("i", $websiteId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing schedule
        $stmt = $conn->prepare("UPDATE scheduled_audits SET frequency = ?, is_active = ?, next_run = ? WHERE website_id = ?");
        $stmt->bind_param("sisi", $frequency, $isActive, $nextRun, $websiteId);
    } else {
        // Insert new schedule
        $stmt = $conn->prepare("INSERT INTO scheduled_audits (website_id, frequency, is_active, next_run) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isis", $websiteId, $frequency, $isActive, $nextRun);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Audit scheduled successfully', 'next_run' => $nextRun]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to schedule audit: ' . $conn->error]);
    }

    exit;
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Return all active schedules
    $sql = "SELECT sa.*, w.domain_name FROM scheduled_audits sa JOIN websites w ON sa.website_id = w.id";
    $result = $conn->query($sql);
    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
    echo json_encode(['success' => true, 'schedules' => $schedules]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unsupported request method']);
