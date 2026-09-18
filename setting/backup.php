<?php
// setting/backup.php - Database Backup and Restore Tool

// Include database configuration
require_once '../config.php';

// Set page title
$pageTitle = "Database Backup";

// Success and error messages
$successMessage = null;
$errorMessage = null;

// Backup directory
$backupDir = '../backups/';

// Ensure backup directory exists
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Handle quick backup request from admin dashboard
if (isset($_GET['quick']) && $_GET['quick'] == 1) {
    try {
        $backupFile = createBackup($conn, $backupDir);
        $successMessage = "Quick backup created successfully: " . basename($backupFile);
    } catch (Exception $e) {
        $errorMessage = "Quick backup failed: " . $e->getMessage();
    }
}

// Handle backup actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'create_backup':
                $backupFile = createBackup($conn, $backupDir);
                $successMessage = "Backup created successfully: " . basename($backupFile);
                break;
                
            case 'restore_backup':
                if (!isset($_POST['backup_file']) || empty($_POST['backup_file'])) {
                    throw new Exception("No backup file selected");
                }
                
                $backupFile = $backupDir . basename($_POST['backup_file']);
                if (!file_exists($backupFile)) {
                    throw new Exception("Backup file not found");
                }
                
                restoreBackup($conn, $backupFile);
                $successMessage = "Backup restored successfully";
                break;
                
            case 'delete_backup':
                if (!isset($_POST['backup_file']) || empty($_POST['backup_file'])) {
                    throw new Exception("No backup file selected");
                }
                
                $backupFile = $backupDir . basename($_POST['backup_file']);
                if (!file_exists($backupFile)) {
                    throw new Exception("Backup file not found");
                }
                
                unlink($backupFile);
                $successMessage = "Backup file deleted successfully";
                break;
                
            default:
                throw new Exception("Unknown action");
        }
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

/**
 * Create a database backup
 * 
 * @param mysqli $conn Database connection
 * @param string $backupDir Backup directory
 * @return string Path to the backup file
 */
function createBackup($conn, $backupDir) {
    // Get all tables
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    
    if (empty($tables)) {
        throw new Exception("No tables found in database");
    }
    
    // Start output buffering
    ob_start();
    
    // Output header
    echo "-- SEO Analysis Tool Database Backup\n";
    echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    echo "-- Server: " . $conn->host_info . "\n";
    echo "-- PHP Version: " . phpversion() . "\n\n";
    
    // For each table
    foreach ($tables as $table) {
        // Get create table syntax
        $result = $conn->query("SHOW CREATE TABLE `$table`");
        $row = $result->fetch_row();
        $createTable = $row[1];
        
        echo "-- Table structure for table `$table`\n";
        echo "DROP TABLE IF EXISTS `$table`;\n";
        echo "$createTable;\n\n";
        
        // Get table data
        $result = $conn->query("SELECT * FROM `$table`");
        $numFields = $result->field_count;
        $numRows = $result->num_rows;
        
        if ($numRows > 0) {
            echo "-- Dumping data for table `$table`\n";
            
            // Get field names
            $fieldNames = [];
            $fieldsResult = $conn->query("DESCRIBE `$table`");
            while ($fieldRow = $fieldsResult->fetch_assoc()) {
                $fieldNames[] = $fieldRow['Field'];
            }
            
            // Start INSERT statement
            echo "INSERT INTO `$table` (`" . implode("`, `", $fieldNames) . "`) VALUES\n";
            
            // For each row
            $rowCount = 0;
            while ($row = $result->fetch_row()) {
                $rowCount++;
                
                echo "(";
                for ($i = 0; $i < $numFields; $i++) {
                    if (is_null($row[$i])) {
                        echo "NULL";
                    } elseif (is_numeric($row[$i])) {
                        echo $row[$i];
                    } else {
                        echo "'" . $conn->real_escape_string($row[$i]) . "'";
                    }
                    
                    if ($i < $numFields - 1) {
                        echo ", ";
                    }
                }
                
                if ($rowCount < $numRows) {
                    echo "),\n";
                } else {
                    echo ");\n\n";
                }
            }
        }
    }
    
    // Get buffered content
    $backupContent = ob_get_clean();
    
    // Create backup file
    $backupFile = $backupDir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    file_put_contents($backupFile, $backupContent);
    
    return $backupFile;
}

/**
 * Restore a database backup
 * 
 * @param mysqli $conn Database connection
 * @param string $backupFile Path to backup file
 */
function restoreBackup($conn, $backupFile) {
    // Read backup file
    $backupContent = file_get_contents($backupFile);
    
    // Split into queries
    $queries = explode(";\n", $backupContent);
    
    // Execute each query
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $conn->query($query);
            if ($conn->error) {
                throw new Exception("Error executing query: " . $conn->error);
            }
        }
    }
}

/**
 * Get list of backup files
 * 
 * @param string $backupDir Backup directory
 * @return array List of backup files with details
 */
function getBackupFiles($backupDir) {
    $backupFiles = [];
    $files = glob($backupDir . 'backup_*.sql');
    
    foreach ($files as $file) {
        $fileSize = filesize($file);
        $fileDate = filemtime($file);
        
        $backupFiles[] = [
            'name' => basename($file),
            'size' => formatBytes($fileSize),
            'date' => date('Y-m-d H:i:s', $fileDate)
        ];
    }
    
    // Sort by date (newest first)
    usort($backupFiles, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    return $backupFiles;
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

// Get backup files
$backupFiles = getBackupFiles($backupDir);
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
        .backup-card {
            transition: transform 0.2s;
        }
        
        .backup-card:hover {
            transform: translateY(-5px);
        }
        
        .backup-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #4e73df;
        }
        
        .backup-table td, .backup-table th {
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
                <div class="col-md-8">
                    <!-- Backup List -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Backup Files</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($backupFiles)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-info-circle text-muted fa-2x mb-3"></i>
                                    <p class="text-muted">No backup files found. Create a backup using the form on the right.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover backup-table">
                                        <thead>
                                            <tr>
                                                <th>Filename</th>
                                                <th>Date</th>
                                                <th>Size</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($backupFiles as $file): ?>
                                                <tr>
                                                    <td><?= safeEcho($file['name']) ?></td>
                                                    <td><?= safeEcho($file['date']) ?></td>
                                                    <td><?= safeEcho($file['size']) ?></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <form method="post" action="" class="d-inline-block me-2">
                                                                <input type="hidden" name="backup_file" value="<?= safeEcho($file['name']) ?>">
                                                                <button type="submit" name="action" value="restore_backup" class="btn btn-sm btn-warning" 
                                                                        onclick="return confirm('Are you sure you want to restore this backup? All current data will be overwritten.')">
                                                                    <i class="fas fa-undo"></i> Restore
                                                                </button>
                                                            </form>
                                                            <a href="<?= '../backups/' . safeEcho($file['name']) ?>" download class="btn btn-sm btn-info me-2">
                                                                <i class="fas fa-download"></i> Download
                                                            </a>
                                                            <form method="post" action="" class="d-inline-block">
                                                                <input type="hidden" name="backup_file" value="<?= safeEcho($file['name']) ?>">
                                                                <button type="submit" name="action" value="delete_backup" class="btn btn-sm btn-danger" 
                                                                        onclick="return confirm('Are you sure you want to delete this backup?')">
                                                                    <i class="fas fa-trash"></i> Delete
                                                                </button>
                                                            </form>
                                                        </div>
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
                
                <div class="col-md-4">
                    <!-- Create Backup -->
                    <div class="card backup-card mb-4">
                        <div class="card-body text-center">
                            <div class="backup-icon">
                                <i class="fas fa-database"></i>
                            </div>
                            <h5 class="card-title">Create New Backup</h5>
                            <p class="card-text">Create a backup of your database to protect against data loss.</p>
                            <form method="post" action="">
                                <button type="submit" name="action" value="create_backup" class="btn btn-primary">
                                    <i class="fas fa-download me-2"></i> Create Backup
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Backup Info -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Backup Information</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Total Backups</span>
                                    <span class="text-muted"><?= count($backupFiles) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Backup Directory</span>
                                    <span class="text-muted"><?= safeEcho($backupDir) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Latest Backup</span>
                                    <span class="text-muted">
                                        <?= !empty($backupFiles) ? safeEcho($backupFiles[0]['date']) : 'None' ?>
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Backup Tips -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Backup Tips</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i> Create regular backups to prevent data loss
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i> Download backups to an external location
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i> Create backups before major updates
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-check-circle text-success me-2"></i> Delete old backups to save disk space
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
