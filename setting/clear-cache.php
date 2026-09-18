<?php
// setting/clear-cache.php - Cache Management Tool

// Include database configuration
require_once '../config.php';

// Set page title
$pageTitle = "Clear Cache";

// Success and error messages
$successMessage = null;
$errorMessage = null;

// Cache directories
$cacheDirs = [
    'api' => '../cache/api/',
    'results' => '../cache/results/',
    'temp' => '../cache/temp/',
    'images' => '../cache/images/'
];

// Ensure cache directories exist
foreach ($cacheDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Handle quick cache clear request from admin dashboard
if (isset($_GET['quick']) && $_GET['quick'] == 1) {
    try {
        clearAllCaches($cacheDirs);
        $successMessage = "All caches cleared successfully.";
    } catch (Exception $e) {
        $errorMessage = "Quick cache clear failed: " . $e->getMessage();
    }
}

// Handle cache clear actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'clear_all':
                clearAllCaches($cacheDirs);
                $successMessage = "All caches cleared successfully.";
                break;
                
            case 'clear_api':
                clearCache($cacheDirs['api']);
                $successMessage = "API cache cleared successfully.";
                break;
                
            case 'clear_results':
                clearCache($cacheDirs['results']);
                $successMessage = "Results cache cleared successfully.";
                break;
                
            case 'clear_temp':
                clearCache($cacheDirs['temp']);
                $successMessage = "Temporary files cleared successfully.";
                break;
                
            case 'clear_images':
                clearCache($cacheDirs['images']);
                $successMessage = "Image cache cleared successfully.";
                break;
                
            case 'clear_db_cache':
                clearDatabaseCache($conn);
                $successMessage = "Database cache cleared successfully.";
                break;
                
            default:
                throw new Exception("Unknown action");
        }
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

/**
 * Clear a specific cache directory
 * 
 * @param string $cacheDir Cache directory path
 */
function clearCache($cacheDir) {
    if (!is_dir($cacheDir)) {
        return; // Directory doesn't exist, nothing to clear
    }
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($files as $file) {
        if ($file->isDir()) {
            // Skip directory removal to maintain structure
            continue;
        } else {
            unlink($file->getRealPath());
        }
    }
}

/**
 * Clear all cache directories
 * 
 * @param array $cacheDirs Array of cache directory paths
 */
function clearAllCaches($cacheDirs) {
    foreach ($cacheDirs as $dir) {
        clearCache($dir);
    }
}

/**
 * Clear database cache table if it exists
 * 
 * @param mysqli $conn Database connection
 */
function clearDatabaseCache($conn) {
    // Check if cache table exists
    $result = $conn->query("SHOW TABLES LIKE 'cache'");
    if ($result->num_rows > 0) {
        $conn->query("TRUNCATE TABLE cache");
    }
}

/**
 * Get cache size and file count for a directory
 * 
 * @param string $cacheDir Cache directory path
 * @return array Size and file count
 */
function getCacheStats($cacheDir) {
    $totalSize = 0;
    $fileCount = 0;
    
    if (!is_dir($cacheDir)) {
        return ['size' => 0, 'count' => 0];
    }
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($files as $file) {
        if ($file->isFile()) {
            $totalSize += $file->getSize();
            $fileCount++;
        }
    }
    
    return [
        'size' => $totalSize,
        'count' => $fileCount
    ];
}

// Get cache statistics
$cacheStats = [];
foreach ($cacheDirs as $type => $dir) {
    $cacheStats[$type] = getCacheStats($dir);
}

// Get database cache size if it exists
$dbCacheSize = 0;
$dbCacheCount = 0;
$result = $conn->query("SHOW TABLES LIKE 'cache'");
if ($result->num_rows > 0) {
    $result = $conn->query("SELECT COUNT(*) as count, SUM(LENGTH(data)) as size FROM cache");
    if ($row = $result->fetch_assoc()) {
        $dbCacheCount = $row['count'] ?? 0;
        $dbCacheSize = $row['size'] ?? 0;
    }
}

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
        .cache-card {
            transition: transform 0.2s;
            margin-bottom: 1.5rem;
        }
        
        .cache-card:hover {
            transform: translateY(-5px);
        }
        
        .cache-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #4e73df;
        }
        
        .cache-stats {
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
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
                <div class="col-md-8">
                    <div class="row">
                        <!-- API Cache -->
                        <div class="col-md-6">
                            <div class="card cache-card h-100">
                                <div class="card-body text-center">
                                    <div class="cache-icon">
                                        <i class="fas fa-server"></i>
                                    </div>
                                    <h5 class="card-title">API Cache</h5>
                                    <p class="card-text">Cached API responses and data from external services.</p>
                                    <div class="cache-stats">
                                        <strong>Size:</strong> <?= formatBytes($cacheStats['api']['size']) ?> | 
                                        <strong>Files:</strong> <?= $cacheStats['api']['count'] ?>
                                    </div>
                                    <form method="post" action="">
                                        <button type="submit" name="action" value="clear_api" class="btn btn-primary">
                                            <i class="fas fa-broom me-2"></i> Clear API Cache
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Results Cache -->
                        <div class="col-md-6">
                            <div class="card cache-card h-100">
                                <div class="card-body text-center">
                                    <div class="cache-icon">
                                        <i class="fas fa-chart-bar"></i>
                                    </div>
                                    <h5 class="card-title">Results Cache</h5>
                                    <p class="card-text">Cached analysis results and reports.</p>
                                    <div class="cache-stats">
                                        <strong>Size:</strong> <?= formatBytes($cacheStats['results']['size']) ?> | 
                                        <strong>Files:</strong> <?= $cacheStats['results']['count'] ?>
                                    </div>
                                    <form method="post" action="">
                                        <button type="submit" name="action" value="clear_results" class="btn btn-primary">
                                            <i class="fas fa-broom me-2"></i> Clear Results Cache
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Temporary Files -->
   <!-- Temporary Files -->
                        <div class="col-md-6">
                            <div class="card cache-card h-100">
                                <div class="card-body text-center">
                                    <div class="cache-icon">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <h5 class="card-title">Temporary Files</h5>
                                    <p class="card-text">Temporary files created during analyses and uploads.</p>
                                    <div class="cache-stats">
                                        <strong>Size:</strong> <?= formatBytes($cacheStats['temp']['size']) ?> | 
                                        <strong>Files:</strong> <?= $cacheStats['temp']['count'] ?>
                                    </div>
                                    <form method="post" action="">
                                        <button type="submit" name="action" value="clear_temp" class="btn btn-primary">
                                            <i class="fas fa-broom me-2"></i> Clear Temporary Files
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Image Cache -->
                        <div class="col-md-6">
                            <div class="card cache-card h-100">
                                <div class="card-body text-center">
                                    <div class="cache-icon">
                                        <i class="fas fa-images"></i>
                                    </div>
                                    <h5 class="card-title">Image Cache</h5>
                                    <p class="card-text">Cached images, screenshots, and graphics.</p>
                                    <div class="cache-stats">
                                        <strong>Size:</strong> <?= formatBytes($cacheStats['images']['size']) ?> | 
                                        <strong>Files:</strong> <?= $cacheStats['images']['count'] ?>
                                    </div>
                                    <form method="post" action="">
                                        <button type="submit" name="action" value="clear_images" class="btn btn-primary">
                                            <i class="fas fa-broom me-2"></i> Clear Image Cache
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Database Cache -->
                        <div class="col-md-6">
                            <div class="card cache-card h-100">
                                <div class="card-body text-center">
                                    <div class="cache-icon">
                                        <i class="fas fa-database"></i>
                                    </div>
                                    <h5 class="card-title">Database Cache</h5>
                                    <p class="card-text">Cached data stored in the database.</p>
                                    <div class="cache-stats">
                                        <strong>Size:</strong> <?= formatBytes($dbCacheSize) ?> | 
                                        <strong>Entries:</strong> <?= $dbCacheCount ?>
                                    </div>
                                    <form method="post" action="">
                                        <button type="submit" name="action" value="clear_db_cache" class="btn btn-primary">
                                            <i class="fas fa-broom me-2"></i> Clear Database Cache
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Clear All Cache -->
                        <div class="col-md-6">
                            <div class="card cache-card h-100 bg-primary text-white">
                                <div class="card-body text-center">
                                    <div class="cache-icon text-white">
                                        <i class="fas fa-trash-alt"></i>
                                    </div>
                                    <h5 class="card-title">Clear All Caches</h5>
                                    <p class="card-text">Clear all caches in one operation.</p>
                                    <div class="cache-stats">
                                        <strong>Total Size:</strong> <?= formatBytes(
                                            $cacheStats['api']['size'] + 
                                            $cacheStats['results']['size'] + 
                                            $cacheStats['temp']['size'] + 
                                            $cacheStats['images']['size'] + 
                                            $dbCacheSize
                                        ) ?> | 
                                        <strong>Total Files:</strong> <?= 
                                            $cacheStats['api']['count'] + 
                                            $cacheStats['results']['count'] + 
                                            $cacheStats['temp']['count'] + 
                                            $cacheStats['images']['count'] + 
                                            $dbCacheCount
                                        ?>
                                    </div>
                                    <form method="post" action="">
                                        <button type="submit" name="action" value="clear_all" class="btn btn-light">
                                            <i class="fas fa-broom me-2"></i> Clear All Caches
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <!-- Cache Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Cache Information</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                Caching improves performance by storing frequently accessed data, but can sometimes lead to
                                stale or outdated information being displayed.
                            </p>
                            <p>
                                Clear caches if you're experiencing issues with outdated data or after making significant
                                changes to your system or settings.
                            </p>
                            
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle me-2"></i> Clearing caches may temporarily slow down the system
                                as new cache files are generated.
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cache Configuration -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Cache Configuration</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Cache Enabled</span>
                                    <span class="badge bg-success">Yes</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Cache Driver</span>
                                    <span class="text-muted"><?= safeEcho(isset($_COOKIE['setting_cache_driver']) ? $_COOKIE['setting_cache_driver'] : 'file') ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Cache TTL</span>
                                    <span class="text-muted"><?= safeEcho(isset($_COOKIE['setting_cache_ttl']) ? $_COOKIE['setting_cache_ttl'] : '3600') ?> seconds</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Max Cache Size</span>
                                    <span class="text-muted"><?= safeEcho(isset($_COOKIE['setting_max_cache_size']) ? $_COOKIE['setting_max_cache_size'] : '100') ?> MB</span>
                                </li>
                            </ul>
                            <div class="text-center mt-3">
                                <a href="settings.php?category=cache" class="btn btn-outline-primary">
                                    <i class="fas fa-cog me-2"></i> Edit Cache Settings
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cache Tips -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Cache Tips</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i> Clear API cache when changing API keys
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i> Clear results cache after major system updates
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i> Clear temporary files periodically to free up disk space
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i> Adjust cache TTL based on how frequently your data changes
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