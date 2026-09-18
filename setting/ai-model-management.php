<?php
// ai-model-management.php - Interface for managing AI models

// Include database configuration
require_once '../config.php';

// Set page title
$pageTitle = "AI Model Management";

// Handle form submissions
$message = '';
$messageType = '';

// Handle add/edit model form submission
if (isset($_POST['action']) && ($_POST['action'] === 'add' || $_POST['action'] === 'edit')) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $type = $_POST['type'];
    $apiUrl = trim($_POST['api_url']);
    $apiKey = trim($_POST['api_key']);
    $modelName = trim($_POST['model_name']);
    $status = $_POST['status'];
    
    // Validate inputs
    if (empty($name)) {
        $message = "Model name is required";
        $messageType = "danger";
    } elseif (empty($apiUrl)) {
        $message = "API URL is required";
        $messageType = "danger";
    } elseif (empty($modelName)) {
        $message = "Model name is required";
        $messageType = "danger";
    } else {
        try {
            if ($_POST['action'] === 'add') {
                // Insert new model
                $stmt = $conn->prepare("INSERT INTO ai_models (name, description, type, api_url, api_key, model_name, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssss", $name, $description, $type, $apiUrl, $apiKey, $modelName, $status);
                
                if ($stmt->execute()) {
                    $message = "AI model added successfully";
                    $messageType = "success";
                } else {
                    $message = "Failed to add AI model: " . $stmt->error;
                    $messageType = "danger";
                }
            } else {
                // Update existing model
                $id = intval($_POST['id']);
                $stmt = $conn->prepare("UPDATE ai_models SET name = ?, description = ?, type = ?, api_url = ?, api_key = ?, model_name = ?, status = ? WHERE id = ?");
                $stmt->bind_param("sssssssi", $name, $description, $type, $apiUrl, $apiKey, $modelName, $status, $id);
                
                if ($stmt->execute()) {
                    $message = "AI model updated successfully";
                    $messageType = "success";
                } else {
                    $message = "Failed to update AI model: " . $stmt->error;
                    $messageType = "danger";
                }
            }
        } catch (Exception $e) {
            $message = "Error: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

// Handle model activation/deactivation
if (isset($_GET['toggle']) && !empty($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    
    try {
        // Get current status
        $stmt = $conn->prepare("SELECT status FROM ai_models WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $newStatus = ($row['status'] === 'active') ? 'inactive' : 'active';
            
            // Update status
            $stmt = $conn->prepare("UPDATE ai_models SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $newStatus, $id);
            
            if ($stmt->execute()) {
                $message = "AI model status updated successfully";
                $messageType = "success";
            } else {
                $message = "Failed to update AI model status: " . $stmt->error;
                $messageType = "danger";
            }
        } else {
            $message = "AI model not found";
            $messageType = "danger";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Handle model deletion
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    try {
        // Delete model
        $stmt = $conn->prepare("DELETE FROM ai_models WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $message = "AI model deleted successfully";
            $messageType = "success";
        } else {
            $message = "Failed to delete AI model: " . $stmt->error;
            $messageType = "danger";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Get model data for editing
$editModel = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $id = intval($_GET['edit']);
    
    try {
        $stmt = $conn->prepare("SELECT * FROM ai_models WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $editModel = $result->fetch_assoc();
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Get all AI models
$aiModels = [];
try {
    $sql = "SELECT * FROM ai_models ORDER BY type, name";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $aiModels[] = $row;
        }
    }
} catch (Exception $e) {
    $message = "Error retrieving AI models: " . $e->getMessage();
    $messageType = "danger";
}

// Function to safely display a value
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
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        .model-card {
            margin-bottom: 20px;
            transition: transform 0.2s;
        }
        
        .model-card:hover {
            transform: translateY(-5px);
        }
        
        .model-status {
            position: absolute;
            right: 15px;
            top: 15px;
        }
        
        .model-actions {
            position: absolute;
            right: 15px;
            bottom: 15px;
        }
        
        .api-key-field {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            z-index: 10;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Go back to main page -->
            <div class="col-12 py-3 bg-light border-bottom">
                <div class="container">
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Back to SEO Analysis
                    </a>
                </div>
            </div>
        </div>
        
        <div class="container py-4">
            <h1><?= $pageTitle ?></h1>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show mt-3" role="alert">
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Add/Edit Model Form -->
            <div class="card mt-4 mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?= $editModel ? 'Edit' : 'Add' ?> AI Model</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <input type="hidden" name="action" value="<?= $editModel ? 'edit' : 'add' ?>">
                        <?php if ($editModel): ?>
                            <input type="hidden" name="id" value="<?= $editModel['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Model Name*</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?= $editModel ? safeEcho($editModel['name']) : '' ?>" required>
                                <div class="form-text">A descriptive name for the AI model</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="type" class="form-label">Type*</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="online" <?= ($editModel && $editModel['type'] === 'online') ? 'selected' : '' ?>>Online API</option>
                                    <option value="local" <?= ($editModel && $editModel['type'] === 'local') ? 'selected' : '' ?>>Local Model</option>
                                </select>
                                <div class="form-text">Whether this is an online API or local model</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2"><?= $editModel ? safeEcho($editModel['description']) : '' ?></textarea>
                            <div class="form-text">A brief description of the model's capabilities</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="api_url" class="form-label">API URL*</label>
                                <input type="url" class="form-control" id="api_url" name="api_url" value="<?= $editModel ? safeEcho($editModel['api_url']) : '' ?>" required>
                                <div class="form-text">The endpoint URL for API calls</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="model_name" class="form-label">Model Name/ID*</label>
                                <input type="text" class="form-control" id="model_name" name="model_name" value="<?= $editModel ? safeEcho($editModel['model_name']) : '' ?>" required>
                                <div class="form-text">The specific model name or ID used in API calls</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="api_key" class="form-label">API Key</label>
                            <div class="api-key-field">
                                <input type="password" class="form-control" id="api_key" name="api_key" value="<?= $editModel ? safeEcho($editModel['api_key']) : '' ?>">
                                <span class="toggle-password" onclick="togglePassword('api_key')">
                                    <i class="far fa-eye"></i>
                                </span>
                            </div>
                            <div class="form-text">Your API key for authentication (leave empty to keep current key)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status*</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" <?= ($editModel && $editModel['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= ($editModel && $editModel['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                            </select>
                            <div class="form-text">Only active models will be available for use</div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?= $editModel ? 'Update' : 'Add' ?> Model
                            </button>
                            
                            <?php if ($editModel): ?>
                                <a href="ai-model-management.php" class="btn btn-outline-secondary ms-2">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Model List -->
            <h2 class="mt-5 mb-4">Available AI Models</h2>
            
            <?php if (empty($aiModels)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No AI models found. Add your first model using the form above.
                </div>
            <?php else: ?>
                <div class="row">
                    <!-- Online Models -->
                    <div class="col-md-6">
                        <h3>Online Models</h3>
                        <?php 
                        $hasOnlineModels = false;
                        foreach ($aiModels as $model): 
                            if ($model['type'] === 'online'):
                                $hasOnlineModels = true;
                        ?>
                            <div class="card model-card">
                                <div class="card-body">
                                    <div class="model-status">
                                        <span class="badge bg-<?= $model['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($model['status']) ?>
                                        </span>
                                    </div>
                                    
                                    <h5 class="card-title"><?= safeEcho($model['name']) ?></h5>
                                    <h6 class="card-subtitle mb-2 text-muted"><?= safeEcho($model['model_name']) ?></h6>
                                    
                                    <?php if (!empty($model['description'])): ?>
                                        <p class="card-text"><?= safeEcho($model['description']) ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="small text-muted mb-3">
                                        <div>API URL: <?= safeEcho($model['api_url']) ?></div>
                                        <div>
                                            API Key: 
                                            <?php if (!empty($model['api_key'])): ?>
                                                <span class="text-success">Set</span>
                                            <?php else: ?>
                                                <span class="text-danger">Not set</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="model-actions">
                                        <a href="ai-model-management.php?edit=<?= $model['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="ai-model-management.php?toggle=<?= $model['id'] ?>" class="btn btn-sm btn-outline-<?= $model['status'] === 'active' ? 'warning' : 'success' ?>">
                                            <i class="fas fa-<?= $model['status'] === 'active' ? 'pause' : 'play' ?>"></i> 
                                            <?= $model['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                        </a>
                                        <a href="ai-model-management.php?delete=<?= $model['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this model?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        
                        if (!$hasOnlineModels):
                        ?>
                            <div class="alert alert-light border">
                                No online AI models found
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Local Models -->
                    <div class="col-md-6">
                        <h3>Local Models</h3>
                        <?php 
                        $hasLocalModels = false;
                        foreach ($aiModels as $model): 
                            if ($model['type'] === 'local'):
                                $hasLocalModels = true;
                        ?>
                            <div class="card model-card">
                                <div class="card-body">
                                    <div class="model-status">
                                        <span class="badge bg-<?= $model['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($model['status']) ?>
                                        </span>
                                    </div>
                                    
                                    <h5 class="card-title"><?= safeEcho($model['name']) ?></h5>
                                    <h6 class="card-subtitle mb-2 text-muted"><?= safeEcho($model['model_name']) ?></h6>
                                    
                                    <?php if (!empty($model['description'])): ?>
                                        <p class="card-text"><?= safeEcho($model['description']) ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="small text-muted mb-3">
                                        <div>API URL: <?= safeEcho($model['api_url']) ?></div>
                                    </div>
                                    
                                    <div class="model-actions">
                                        <a href="ai-model-management.php?edit=<?= $model['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="ai-model-management.php?toggle=<?= $model['id'] ?>" class="btn btn-sm btn-outline-<?= $model['status'] === 'active' ? 'warning' : 'success' ?>">
                                            <i class="fas fa-<?= $model['status'] === 'active' ? 'pause' : 'play' ?>"></i> 
                                            <?= $model['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                        </a>
                                        <a href="ai-model-management.php?delete=<?= $model['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this model?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        
                        if (!$hasLocalModels):
                        ?>
                            <div class="alert alert-light border">
                                No local AI models found
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Bootstrap 5.2 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const toggle = field.nextElementSibling.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                toggle.classList.remove('fa-eye');
                toggle.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                toggle.classList.remove('fa-eye-slash');
                toggle.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
