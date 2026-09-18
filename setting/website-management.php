<?php
// website-management.php - Manage Websites

// Include database configuration
require_once '../config.php';

// Set page title
$pageTitle = "Website Management";

// Success and error messages
$successMessage = null;
$errorMessage = null;

// Handle website deletion
if (isset($_POST['delete_website']) && isset($_POST['website_id'])) {
    $websiteId = intval($_POST['website_id']);
    
    // Start transaction to ensure all data is deleted together
    $conn->begin_transaction();
    
    try {
        // Delete data from all related tables
        $relatedTables = [
            'ai_recommendations',
            'competitive_analysis',
            'content_analysis',
            'core_web_vitals',
            'image_analysis',
            'keyword_analysis',
            'link_analysis',
            'meta_tags_analysis',
            'offpage_seo',
            'onpage_seo',
            'page_structure',
            'technical_seo'
        ];
        
        foreach ($relatedTables as $table) {
            $stmt = $conn->prepare("DELETE FROM $table WHERE website_id = ?");
            $stmt->bind_param("i", $websiteId);
            $stmt->execute();
        }
        
        // Get website information for confirmation message
        $stmt = $conn->prepare("SELECT domain_name FROM websites WHERE id = ?");
        $stmt->bind_param("i", $websiteId);
        $stmt->execute();
        $result = $stmt->get_result();
        $websiteName = "";
        
        if ($row = $result->fetch_assoc()) {
            $websiteName = $row['domain_name'];
        }
        
        // Finally, delete the website record
        $stmt = $conn->prepare("DELETE FROM websites WHERE id = ?");
        $stmt->bind_param("i", $websiteId);
        $stmt->execute();
        
        // Commit the transaction
        $conn->commit();
        
        $successMessage = "Website '$websiteName' and all its data has been deleted successfully.";
    } catch (Exception $e) {
        // Rollback the transaction if any error occurs
        $conn->rollback();
        $errorMessage = "Error deleting website: " . $e->getMessage();
    }
}

// Pagination setup
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$resultsPerPage = 10;
$offset = ($currentPage - 1) * $resultsPerPage;

// Get total number of websites
$totalWebsites = 0;
$countResult = $conn->query("SELECT COUNT(*) as count FROM websites");
if ($countResult && $row = $countResult->fetch_assoc()) {
    $totalWebsites = $row['count'];
}
$totalPages = ceil($totalWebsites / $resultsPerPage);

// Get websites for current page
$websites = [];
$query = "SELECT w.id, w.domain_name, w.url, w.date_added, w.last_checked, 
          COUNT(DISTINCT t.id) as technical_count,
          COUNT(DISTINCT o.id) as onpage_count,
          COUNT(DISTINCT os.id) as offpage_count
          FROM websites w
          LEFT JOIN technical_seo t ON w.id = t.website_id
          LEFT JOIN onpage_seo o ON w.id = o.website_id
          LEFT JOIN offpage_seo os ON w.id = os.website_id
          GROUP BY w.id
          ORDER BY w.last_checked DESC
          LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $resultsPerPage, $offset);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $websites[] = $row;
}

// Function to safely display values
function safeEcho($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Function to format timestamp
function formatDate($timestamp) {
    return date('M d, Y H:i', strtotime($timestamp));
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
        .website-card {
            transition: transform 0.2s;
            margin-bottom: 1.5rem;
        }
        
        .website-card:hover {
            transform: translateY(-5px);
        }
        
        .pagination {
            justify-content: center;
        }
        
        .table-responsive {
            margin-bottom: 1.5rem;
        }
        
        .website-actions {
            white-space: nowrap;
        }
        
        .delete-btn {
            cursor: pointer;
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
            
            <!-- Websites List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Websites (<?= $totalWebsites ?>)</h5>
                    <a href="../index.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Add New Website
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Domain</th>
                                    <th>URL</th>
                                    <th>Analysis Data</th>
                                    <th>Added On</th>
                                    <th>Last Checked</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($websites) > 0): ?>
                                    <?php foreach ($websites as $website): ?>
                                        <tr>
                                            <td><?= safeEcho($website['domain_name']) ?></td>
                                            <td>
                                                <a href="<?= safeEcho($website['url']) ?>" target="_blank" class="text-truncate d-inline-block" style="max-width: 200px;">
                                                    <?= safeEcho($website['url']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?= $website['technical_count'] ?> Technical</span>
                                                <span class="badge bg-success"><?= $website['onpage_count'] ?> On-page</span>
                                                <span class="badge bg-info"><?= $website['offpage_count'] ?> Off-page</span>
                                            </td>
                                            <td><?= formatDate($website['date_added']) ?></td>
                                            <td><?= formatDate($website['last_checked']) ?></td>
                                            <td class="website-actions">
                                                <a href="../index.php?url=<?= urlencode($website['url']) ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-search"></i>
                                                </a>
                                                <button class="btn btn-sm btn-outline-danger delete-btn" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteModal" 
                                                        data-website-id="<?= $website['id'] ?>"
                                                        data-website-name="<?= safeEcho($website['domain_name']) ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-3">
                                            <i class="fas fa-info-circle text-info"></i> No websites found. 
                                            <a href="../index.php">Add your first website</a>.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Websites pagination">
                            <ul class="pagination">
                                <li class="page-item <?= $currentPage == 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=1" aria-label="First">
                                        <span aria-hidden="true">&laquo;&laquo;</span>
                                    </a>
                                </li>
                                <li class="page-item <?= $currentPage == 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= max(1, $currentPage - 1) ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                                
                                <?php
                                $startPage = max(1, $currentPage - 2);
                                $endPage = min($totalPages, $startPage + 4);
                                
                                if ($endPage - $startPage < 4) {
                                    $startPage = max(1, $endPage - 4);
                                }
                                
                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <li class="page-item <?= $currentPage == $totalPages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= min($totalPages, $currentPage + 1) ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                                <li class="page-item <?= $currentPage == $totalPages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $totalPages ?>" aria-label="Last">
                                        <span aria-hidden="true">&raquo;&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i> 
                        This action will permanently delete <strong id="websiteName"></strong> and all its associated data.
                    </p>
                    <p>This includes all technical SEO, on-page SEO, off-page SEO, and other analysis data for this website.</p>
                    <p class="mb-0">This action cannot be undone. Are you sure you want to proceed?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="post" action="">
                        <input type="hidden" name="website_id" id="websiteIdInput" value="">
                        <button type="submit" name="delete_website" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i> Delete Website
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5.2 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Set website data in delete modal
        document.addEventListener('DOMContentLoaded', function() {
            const deleteModal = document.getElementById('deleteModal');
            if (deleteModal) {
                deleteModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const websiteId = button.getAttribute('data-website-id');
                    const websiteName = button.getAttribute('data-website-name');
                    
                    const websiteNameElement = document.getElementById('websiteName');
                    const websiteIdInput = document.getElementById('websiteIdInput');
                    
                    websiteNameElement.textContent = websiteName;
                    websiteIdInput.value = websiteId;
                });
            }
        });
    </script>
</body>
</html>
