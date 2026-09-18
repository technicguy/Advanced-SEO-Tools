<?php
// setting/maintenance.php - System Maintenance Tools

// Include database configuration
require_once '../config.php';

// Set page title
$pageTitle = "System Maintenance";

// Success and error messages
$successMessage = null;
$errorMessage = null;

// Handle maintenance actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'clear_logs':
                // Clear log files
                $logDir = '../logs/';
                if (is_dir($logDir)) {
                    $logFiles = glob($logDir . '*.log');
                    foreach ($logFiles as $file) {
                        if (is_file($file)) {
                            // Truncate file instead of deleting to maintain permissions
                            file_put_contents($file, '');
                        }
                    }
                    $successMessage = "Log files have been cleared successfully.";
                } else {
                    $errorMessage = "Log directory not found.";
                }
                break;
                
            case 'optimize_db':
                // Optimize database tables
                $tables = [];
                $result = $conn->query("SHOW TABLES");
                while ($row = $result->fetch_row()) {
                    $tables[] = $row[0];
                }
                
                if (count($tables) > 0) {
                    foreach ($tables as $table) {
                        $conn->query("OPTIMIZE TABLE `$table`");
                    }
                    $successMessage = "Database tables have been optimized successfully.";
                } else {
                    $errorMessage = "No tables found in the database.";
                }
                break;
                
            case 'repair_db':
                // Repair database tables
                $tables = [];
                $result = $conn->query("SHOW TABLES");
                while ($row = $result->fetch_row()) {
                    $tables[] = $row[0];
                }
                
                if (count($tables) > 0) {
                    foreach ($tables as $table) {
                        $conn->query("REPAIR TABLE `$table`");
                    }
                    $successMessage = "Database tables have been repaired successfully.";
                } else {
                    $errorMessage = "No tables found in the database.";
                }
                break;
                
            case 'clear_temp_files':
                // Clear temporary files
                $tempDir = '../cache/temp/';
                if (is_dir($tempDir)) {
                    $tempFiles = glob($tempDir . '*');
                    foreach ($tempFiles as $file) {
                        if (is_file($file)) {
                            unlink($file);
                        }
                    }
                    $successMessage = "Temporary files have been cleared successfully.";
                } else {
                    $errorMessage = "Temporary directory not found.";
                }
                break;
                
            case 'clear_sessions':
                // Clear expired PHP sessions
                $sessionDir = session_save_path();
                if (empty($sessionDir)) {
                    $sessionDir = '/tmp'; // Default location
                }
                
                if (is_dir($sessionDir)) {
                    $currentTime = time();
                    $sessionFiles = glob($sessionDir . '/sess_*');
                    $count = 0;
                    
                    foreach ($sessionFiles as $file) {
                        if (is_file($file) && filemtime($file) + 86400 < $currentTime) {
                            unlink($file);
                            $count++;
                        }
                    }
                    
                    $successMessage = "$count expired session files have been cleared successfully.";
                } else {
                    $errorMessage = "Session directory not found.";
                }
                break;
                
            case 'prune_old_data':
                // Prune old analysis data (older than 90 days)
                $pruneDays = isset($_POST['prune_days']) ? intval($_POST['prune_days']) : 90;
                $pruneDays = max(30, min(365, $pruneDays)); // Ensure between 30 and 365 days
                
                $pruneDate = date('Y-m-d H:i:s', strtotime("-$pruneDays days"));
                
                // Example: prune technical_seo table
                $stmt = $conn->prepare("DELETE FROM technical_seo WHERE scan_date < ?");
                $stmt->bind_param("s", $pruneDate);
                $stmt->execute();
                $techDeleted = $stmt->affected_rows;
                
                // Prune other tables similarly
                $stmt = $conn->prepare("DELETE FROM onpage_seo WHERE scan_date < ?");
                $stmt->bind_param("s", $pruneDate);
                $stmt->execute();
                $onpageDeleted = $stmt->affected_rows;
                
                $stmt = $conn->prepare("DELETE FROM offpage_seo WHERE scan_date < ?");
                $stmt->bind_param("s", $pruneDate);
                $stmt->execute();
                $offpageDeleted = $stmt->affected_rows;
                
                $stmt = $conn->prepare("DELETE FROM keyword_analysis WHERE scan_date < ?");
                $stmt->bind_param("s", $pruneDate);
                $stmt->execute();
                $keywordDeleted = $stmt->affected_rows;
                
                $successMessage = "Pruned old data: $techDeleted technical SEO, $onpageDeleted on-page SEO, $offpageDeleted off-page SEO, and $keywordDeleted keyword analysis records deleted.";
                break;
                
            case 'check_api_connections':
                // Check API connections
                $apiStatuses = [];
                
                // Google API check
                $googleApiKey = isset($_COOKIE['setting_google_api_key']) ? $_COOKIE['setting_google_api_key'] : '';
                if (!empty($googleApiKey)) {
                    $googleUrl = "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=https://www.google.com&key=$googleApiKey";
                    $apiStatuses['Google PageSpeed'] = checkApiConnection($googleUrl);
                } else {
                    $apiStatuses['Google PageSpeed'] = 'No API key configured';
                }
                
                // Moz API check
                $mozApiKey = isset($_COOKIE['setting_moz_api_key']) ? $_COOKIE['setting_moz_api_key'] : '';
                $mozSecretKey = isset($_COOKIE['setting_moz_secret_key']) ? $_COOKIE['setting_moz_secret_key'] : '';
                if (!empty($mozApiKey) && !empty($mozSecretKey)) {
                    $expires = time() + 300;
                    $stringToSign = $mozApiKey . "\n" . $expires;
                    $signature = hash_hmac('sha1', $stringToSign, $mozSecretKey);
                    $mozUrl = "https://lsapi.seomoz.com/v2/url_metrics?Cols=103079215104&AccessID=$mozApiKey&Expires=$expires&Signature=$signature";
                    $apiStatuses['Moz API'] = checkApiConnection($mozUrl, true);
                } else {
                    $apiStatuses['Moz API'] = 'No API keys configured';
                }
                
                // SEMrush API check
                $semrushApiKey = isset($_COOKIE['setting_semrush_api_key']) ? $_COOKIE['setting_semrush_api_key'] : '';
                if (!empty($semrushApiKey)) {
                    $semrushUrl = "https://api.semrush.com/?type=domain_rank&key=$semrushApiKey&export_columns=Dn,Rk,Or,Xn,Ot,Oc,Ad,At,Ac&domain=google.com&database=us";
                    $apiStatuses['SEMrush API'] = checkApiConnection($semrushUrl);
                } else {
                    $apiStatuses['SEMrush API'] = 'No API key configured';
                }
                
                // Store API status results in session for display
                session_start();
                $_SESSION['api_status_results'] = $apiStatuses;
                $successMessage = "API connections have been checked.";
                break;
                
            default:
                $errorMessage = "Unknown action: " . htmlspecialchars($action);
                break;
        }
    } catch (Exception $e) {
        $errorMessage = "Error: " . $e->getMessage();
    }
}

/**
 * Check if an API connection works
 * 
 * @param string $url The API URL to check
 * @param bool $postRequest Whether to use POST instead of GET
 * @return string Status message
 */
function checkApiConnection($url, $postRequest = false) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    if ($postRequest) {
        curl_setopt($ch, CURLOPT_POST, true);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return "Error: $error";
    } elseif ($httpCode >= 200 && $httpCode < 300) {
        return "Connected successfully (HTTP $httpCode)";
    } else {
        return "Failed to connect (HTTP $httpCode)";
    }
}

// Get server stats
function getServerStats() {
    return [
        'php_version' => phpversion(),
        'memory_usage' => memory_get_usage(true),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time') . ' seconds',
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'disk_free_space' => disk_free_space('.'),
        'disk_total_space' => disk_total_space('.')
    ];
}

$serverStats = getServerStats();

// Function to format bytes
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Function to safely display values
function safeEcho($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Get API status results from session if available
if(session_status() != PHP_SESSION_ACTIVE) {
    session_start();
}
$apiStatusResults = isset($_SESSION['api_status_results']) ? $_SESSION['api_status_results'] : [];
// Clear the session data after retrieving
unset($_SESSION['api_status_results']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - SEO Analysis Tools</title>
    
    <!-- Bootstrap 5.2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    
    <style>
        .maintenance-card {
            margin-bottom: 2rem;
            transition: transform 0.2s;
        }
        
        .maintenance-card:hover {
            transform: translateY(-5px);
        }
        
        .maintenance-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #4e73df;
        }
        
        .stats-card {
            margin-bottom: 1rem;
        }
        
        .api-status-table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Go back to admin dashboard -->
            <div class="col-12 py-3 bg-light border-bottom">
                <div class="container">
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Back to Admin Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <div class="container py-4">
            <h1><?= $pageTitle ?></h1>
            
            <?php if ($successMessage): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?= safeEcho($successMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($errorMessage): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> <?= safeEcho($errorMessage) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Maintenance Tools -->
                <div class="col-md-8">
                    <div class="row">
                        <!-- Database Maintenance -->
                        <div class="col-md-6 mb-4">
                            <div class="card maintenance-card h-100">
                                <div class="card-body text-center">
                                    <div class="maintenance-icon">
                                        <i class="fas fa-database"></i>
                                    </div>
                                    <h5 class="card-title">Database Maintenance</h5>
                                    <p class="card-text">Optimize and repair database tables for better performance.</p>
                                    <form method="post" action="">
                                        <button type="submit" name="action" value="optimize_db" class="btn btn-primary mb-2">
                                            <i class="fas fa-hammer me-2"></i> Optimize Database
                                        </button>
                                        <button type="submit" name="action" value="repair_db" class="btn btn-warning">
                                            <i class="fas fa-tools me-2"></i> Repair Database
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Disk Cleanup -->
                        <div class="col-md-6 mb-4">
                            <div class="card maintenance-card h-100">
                                <div class="card-body text-center">
                                    <div class="maintenance-icon">
                                        <i class="fas fa-broom"></i>
                                    </div>
                                    <h5 class="card-title">Disk Cleanup</h5>
                                    <p class="card-text">Clear temporary files, logs, and expired sessions.</p>
                                    <form method="post" action="">
                                        <button type="submit" name="action" value="clear_temp_files" class="btn btn-primary mb-2">
                                            <i class="fas fa-trash me-2"></i> Clear Temp Files
                                        </button>
                                        <button type="submit" name="action" value="clear_logs" class="btn btn-info mb-2">
                                            <i class="fas fa-file-alt me-2"></i> Clear Log Files
                                        </button>
                                        <button type="submit" name="action" value="clear_sessions" class="btn btn-secondary">
                                            <i class="fas fa-user-clock me-2"></i> Clear Expired Sessions
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Data Management -->
                        <div class="col-md-6 mb-4">
                            <div class="card maintenance-card h-100">
                                <div class="card-body text-center">
                                    <div class="maintenance-icon">
                                        <i class="fas fa-calendar-alt"></i>
                                    </div>
                                    <h5 class="card-title">Data Management</h5>
                                    <p class="card-text">Prune old analysis data to free up database space.</p>
                                    <form method="post" action="">
                                        <div class="input-group mb-3">
                                            <span class="input-group-text">Older than</span>
                                            <input type="number" class="form-control" name="prune_days" value="90" min="30" max="365">
                                            <span class="input-group-text">days</span>
                                        </div>
                                        <button type="submit" name="action" value="prune_old_data" class="btn btn-danger" 
                                                onclick="return confirm('Are you sure you want to delete old data? This action cannot be undone.')">
                                            <i class="fas fa-trash-alt me-2"></i> Prune Old Data
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- API Connections -->
                        <div class="col-md-6 mb-4">
                            <div class="card maintenance-card h-100">
                                <div class="card-body text-center">
                                    <div class="maintenance-icon">
                                        <i class="fas fa-plug"></i>
                                    </div>
                                    <h5 class="card-title">API Connections</h5>
                                    <p class="card-text">Test API connections to ensure they're working properly.</p>
                                    <form method="post" action="">
                                        <button type="submit" name="action" value="check_api_connections" class="btn btn-primary">
                                            <i class="fas fa-sync me-2"></i> Check API Connections
                                        </button>
                                    </form>
                                    
                                    <?php if (!empty($apiStatusResults)): ?>
                                        <div class="mt-3">
                                            <table class="table table-sm api-status-table">
                                                <thead>
                                                    <tr>
                                                        <th>API</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($apiStatusResults as $api => $status): ?>
                                                        <tr>
                                                            <td><?= safeEcho($api) ?></td>
                                                            <td>
                                                                <?php if (strpos($status, 'success') !== false): ?>
                                                                    <span class="text-success">
                                                                        <i class="fas fa-check-circle me-1"></i> <?= safeEcho($status) ?>
                                                                    </span>
                                                                <?php elseif (strpos($status, 'No API') !== false): ?>
                                                                    <span class="text-muted">
                                                                        <i class="fas fa-info-circle me-1"></i> <?= safeEcho($status) ?>
                                                                    </span>
                                                                <?php else: ?>
                                                                    <span class="text-danger">
                                                                        <i class="fas fa-exclamation-circle me-1"></i> <?= safeEcho($status) ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Server Information -->
                <div class="col-md-4">
                    <div class="card stats-card">
                        <div class="card-header">
                            <h5 class="mb-0">Server Information</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>PHP Version</span>
                                    <span class="text-muted"><?= safeEcho($serverStats['php_version']) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Memory Usage</span>
                                    <span class="text-muted"><?= formatBytes($serverStats['memory_usage']) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Memory Limit</span>
                                    <span class="text-muted"><?= safeEcho($serverStats['memory_limit']) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Max Execution Time</span>
                                    <span class="text-muted"><?= safeEcho($serverStats['max_execution_time']) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Upload Max Filesize</span>
                                    <span class="text-muted"><?= safeEcho($serverStats['upload_max_filesize']) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Server Software</span>
                                    <span class="text-muted"><?= safeEcho($serverStats['server_software']) ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Disk Space -->
                    <div class="card stats-card">
                        <div class="card-header">
                            <h5 class="mb-0">Disk Space</h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <?php
                                $usedSpace = $serverStats['disk_total_space'] - $serverStats['disk_free_space'];
                                $percentUsed = ($serverStats['disk_total_space'] > 0) ? round(($usedSpace / $serverStats['disk_total_space']) * 100, 1) : 0;
                                ?>
                                
                                <h6>
                                    <?= formatBytes($usedSpace) ?> / <?= formatBytes($serverStats['disk_total_space']) ?>
                                    (<?= $percentUsed ?>%)
                                </h6>
                            </div>
                            
                            <div class="progress mb-3">
                                <div class="progress-bar <?= $percentUsed > 80 ? 'bg-danger' : 'bg-success' ?>" 
                                     role="progressbar" 
                                     style="width: <?= $percentUsed ?>%" 
                                     aria-valuenow="<?= $percentUsed ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                </div>
                            </div>
                            
                            <div class="row text-center">
                                <div class="col">
                                    <small class="text-muted">Free Space</small>
                                    <div><?= formatBytes($serverStats['disk_free_space']) ?></div>
                                </div>
                                <div class="col">
                                    <small class="text-muted">Used Space</small>
                                    <div><?= formatBytes($usedSpace) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Maintenance Tips -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Maintenance Tips</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i> Regularly optimize database tables for better performance
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i> Clear temporary files and logs periodically
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i> Prune old analysis data to free up database space
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i> Check API connections after updating API keys
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i> Create backups before major maintenance operations
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5.2 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>