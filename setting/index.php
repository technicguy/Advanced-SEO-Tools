<?php
// setting/index.php - Admin Dashboard

// Include database configuration
require_once '../config.php';

// Set page title
$pageTitle = "Admin Dashboard";

// Check if database tables exist
$tablesExist = true;
$requiredTables = [
    'websites', 'technical_seo', 'onpage_seo', 'offpage_seo', 
    'keyword_analysis', 'content_analysis', 'ai_models', 'ai_recommendations'
];

foreach ($requiredTables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows == 0) {
        $tablesExist = false;
        break;
    }
}

// Get system statistics
$stats = [
    'websites' => 0,
    'analyses' => 0,
    'ai_models' => 0,
    'ai_recommendations' => 0
];

if ($tablesExist) {
    // Websites count
    $result = $conn->query("SELECT COUNT(*) as count FROM websites");
    if ($result && $row = $result->fetch_assoc()) {
        $stats['websites'] = $row['count'];
    }
    
    // Analyses count (technical SEO as a representative)
    $result = $conn->query("SELECT COUNT(*) as count FROM technical_seo");
    if ($result && $row = $result->fetch_assoc()) {
        $stats['analyses'] = $row['count'];
    }
    
    // AI models count
    $result = $conn->query("SELECT COUNT(*) as count FROM ai_models WHERE status = 'active'");
    if ($result && $row = $result->fetch_assoc()) {
        $stats['ai_models'] = $row['count'];
    }
    
    // AI recommendations count
    $result = $conn->query("SELECT COUNT(*) as count FROM ai_recommendations");
    if ($result && $row = $result->fetch_assoc()) {
        $stats['ai_recommendations'] = $row['count'];
    }
}

// Recent analyses
$recentAnalyses = [];
if ($tablesExist) {
    $query = "SELECT w.domain_name, w.url, t.scan_date 
              FROM websites w 
              JOIN technical_seo t ON w.id = t.website_id 
              ORDER BY t.scan_date DESC LIMIT 5";
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recentAnalyses[] = $row;
        }
    }
}

// System information
$systemInfo = [
    'php_version' => phpversion(),
    'mysql_version' => $conn->server_info,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time') . ' seconds',
    'upload_max_filesize' => ini_get('upload_max_filesize')
];

// Function to safely display values
function safeEcho($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Check disk space
function getDiskSpace() {
    $totalSpace = disk_total_space('/');
    $freeSpace = disk_free_space('/');
    
    return [
        'total' => $totalSpace,
        'free' => $freeSpace,
        'used' => $totalSpace - $freeSpace,
        'percent_used' => ($totalSpace > 0) ? round(($totalSpace - $freeSpace) / $totalSpace * 100, 1) : 0
    ];
}

$diskSpace = getDiskSpace();

// Function to format bytes
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= (1 << (10 * $pow));
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}
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
        .dashboard-card {
            transition: transform 0.2s;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        
        .dashboard-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .system-info-card {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Go back to main page -->
            <div class="col-12 py-3 bg-light border-bottom">
                <div class="container">
                    <a href="../index.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Back to SEO Analysis
                    </a>
                </div>
            </div>
        </div>
        
        <div class="container py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><?= $pageTitle ?></h1>
                <div>
                    <span class="text-muted">Server Time: <?= date('Y-m-d H:i:s') ?></span>
                </div>
            </div>
            
            <?php if (!$tablesExist): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Database tables are missing. Please check your installation.
                </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card dashboard-card bg-primary text-white">
                        <div class="card-body text-center">
                            <div class="dashboard-icon">
                                <i class="fas fa-globe"></i>
                            </div>
                            <h3><?= $stats['websites'] ?></h3>
                            <p class="mb-0">Websites</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card dashboard-card bg-success text-white">
                        <div class="card-body text-center">
                            <div class="dashboard-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3><?= $stats['analyses'] ?></h3>
                            <p class="mb-0">Analyses</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card dashboard-card bg-info text-white">
                        <div class="card-body text-center">
                            <div class="dashboard-icon">
                                <i class="fas fa-robot"></i>
                            </div>
                            <h3><?= $stats['ai_models'] ?></h3>
                            <p class="mb-0">Active AI Models</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card dashboard-card bg-warning text-white">
                        <div class="card-body text-center">
                            <div class="dashboard-icon">
                                <i class="fas fa-lightbulb"></i>
                            </div>
                            <h3><?= $stats['ai_recommendations'] ?></h3>
                            <p class="mb-0">AI Recommendations</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Management Cards -->
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Management</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
								<div class="col-md-4 mb-3">
                                    <a href="settings.php" class="card h-100 text-decoration-none text-dark">
                                        <div class="card-body text-center">
                                            <i class="fas fa-cogs fa-2x mb-3 text-primary"></i>
                                            <h5>System Settings</h5>
                                            <p class="mb-0 text-muted small">Configure global system settings</p>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="website-management.php" class="card h-100 text-decoration-none text-dark">
                                        <div class="card-body text-center">
                                            <i class="fas fa-globe fa-2x mb-3 text-success"></i>
                                            <h5>Websites</h5>
                                            <p class="mb-0 text-muted small">Manage websites and their data</p>
                                        </div>
                                    </a>
                                </div>	
                                <div class="col-md-4 mb-3">
                                    <a href="ai-model-management.php" class="card h-100 text-decoration-none text-dark">
                                        <div class="card-body text-center">
                                            <i class="fas fa-robot fa-2x mb-3 text-info"></i>
                                            <h5>AI Models</h5>
                                            <p class="mb-0 text-muted small">Manage AI models for recommendations</p>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="db-maintenance.php" class="card h-100 text-decoration-none text-dark">
                                        <div class="card-body text-center">
                                            <i class="fas fa-database fa-2x mb-3 text-warning"></i>
                                            <h5>Database</h5>
                                            <p class="mb-0 text-muted small">Database maintenance tools</p>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="maintenance.php" class="card h-100 text-decoration-none text-dark">
                                        <div class="card-body text-center">
                                            <i class="fas fa-tools fa-2x mb-3 text-secondary"></i>
                                            <h5>Maintenance</h5>
                                            <p class="mb-0 text-muted small">System maintenance tools</p>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="backup.php" class="card h-100 text-decoration-none text-dark">
                                        <div class="card-body text-center">
                                            <i class="fas fa-download fa-2x mb-3 text-success"></i>
                                            <h5>Backup</h5>
                                            <p class="mb-0 text-muted small">Backup database and configurations</p>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="clear-cache.php" class="card h-100 text-decoration-none text-dark">
                                        <div class="card-body text-center">
                                            <i class="fas fa-broom fa-2x mb-3 text-danger"></i>
                                            <h5>Clear Cache</h5>
                                            <p class="mb-0 text-muted small">Clear application caches</p>
                                        </div>
                                    </a>
                                </div>
								
							
								
								
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Analyses -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Recent Analyses</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($recentAnalyses) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Domain</th>
                                                <th>URL</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentAnalyses as $analysis): ?>
                                                <tr>
                                                    <td><?= safeEcho($analysis['domain_name']) ?></td>
                                                    <td>
                                                        <a href="<?= safeEcho($analysis['url']) ?>" target="_blank">
                                                            <?= safeEcho($analysis['url']) ?>
                                                        </a>
                                                    </td>
                                                    <td><?= date('M d, Y H:i', strtotime($analysis['scan_date'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-info-circle"></i> No analyses found.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- System Information -->
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">System Information</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>PHP Version</span>
                                    <span class="text-muted"><?= safeEcho($systemInfo['php_version']) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>MySQL Version</span>
                                    <span class="text-muted"><?= safeEcho($systemInfo['mysql_version']) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Server Software</span>
                                    <span class="text-muted"><?= safeEcho($systemInfo['server_software']) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Memory Limit</span>
                                    <span class="text-muted"><?= safeEcho($systemInfo['memory_limit']) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Max Execution Time</span>
                                    <span class="text-muted"><?= safeEcho($systemInfo['max_execution_time']) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Upload Max Filesize</span>
                                    <span class="text-muted"><?= safeEcho($systemInfo['upload_max_filesize']) ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Disk Space -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Disk Space</h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <h6>
                                    <?= formatBytes($diskSpace['used']) ?> / <?= formatBytes($diskSpace['total']) ?>
                                    (<?= $diskSpace['percent_used'] ?>%)
                                </h6>
                            </div>
                            <div class="progress mb-3">
                                <div class="progress-bar <?= $diskSpace['percent_used'] > 80 ? 'bg-danger' : 'bg-success' ?>" 
                                     role="progressbar" 
                                     style="width: <?= $diskSpace['percent_used'] ?>%" 
                                     aria-valuenow="<?= $diskSpace['percent_used'] ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                </div>
                            </div>
                            <div class="row text-center">
                                <div class="col">
                                    <small class="text-muted">Free Space</small>
                                    <div><?= formatBytes($diskSpace['free']) ?></div>
                                </div>
                                <div class="col">
                                    <small class="text-muted">Used Space</small>
                                    <div><?= formatBytes($diskSpace['used']) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <a href="backup.php?quick=1" class="list-group-item list-group-item-action">
                                    <i class="fas fa-download me-2"></i> Quick Backup
                                </a>
                                <a href="clear-cache.php?quick=1" class="list-group-item list-group-item-action">
                                    <i class="fas fa-broom me-2"></i> Clear Cache
                                </a>
                                <a href="ai-model-tester.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-robot me-2"></i> Test AI Models
                                </a>
                                <a href="../index.php" class="list-group-item list-group-item-action">
                                    <i class="fas fa-chart-line me-2"></i> Run New Analysis
                                </a>
                            </div>
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
