<?php
// setting/settings.php - System Settings

// Include database configuration
require_once '../config.php';
require_once 'settings_functions.php';

// Set page title
$pageTitle = "System Settings";

// Define available settings categories
$categories = [
    'general' => 'General Settings',
    'api' => 'API Settings',
    'seo' => 'SEO Analysis Settings',
    'ai' => 'AI Settings',
    'cache' => 'Cache Settings',
    'email' => 'Email Settings'
];

// Get active category from URL or default to general
$activeCategory = isset($_GET['category']) && array_key_exists($_GET['category'], $categories) 
                 ? $_GET['category'] 
                 : 'general';

// Success and error messages
$successMessage = null;
$errorMessage = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    try {
        // Different settings for each category
        $settings = [];
        
        switch ($activeCategory) {
            case 'general':
                $settings = [
                    'site_name' => trim($_POST['site_name'] ?? ''),
                    'result_limit' => max(5, min(100, intval($_POST['result_limit'] ?? 10))),
                    'default_language' => trim($_POST['default_language'] ?? 'en'),
                    'debug_mode' => isset($_POST['debug_mode']) ? true : false
                ];
                break;
                
            case 'api':
                $settings = [
                    'google_api_key' => trim($_POST['google_api_key'] ?? ''),
                    'moz_api_key' => trim($_POST['moz_api_key'] ?? ''),
					'moz_access_id' => trim($_POST['moz_access_id'] ?? ''),
                    'moz_secret_key' => trim($_POST['moz_secret_key'] ?? ''),
                    'semrush_api_key' => trim($_POST['semrush_api_key'] ?? ''),
                    'majestic_api_key' => trim($_POST['majestic_api_key'] ?? '')
                ];
                break;
                
            case 'seo':
                $settings = [
                    'crawl_depth' => max(1, min(10, intval($_POST['crawl_depth'] ?? 3))),
                    'crawl_delay' => max(0, min(10, floatval($_POST['crawl_delay'] ?? 1))),
                    'user_agent' => trim($_POST['user_agent'] ?? ''),
                    'respect_robots_txt' => isset($_POST['respect_robots_txt']) ? true : false,
                    'crawl_timeout' => max(10, min(120, intval($_POST['crawl_timeout'] ?? 30)))
                ];
                break;
                
            case 'ai':
                $settings = [
                    'default_ai_model' => intval($_POST['default_ai_model'] ?? 0),
                    'max_tokens' => max(100, min(4000, intval($_POST['max_tokens'] ?? 1000))),
                    'temperature' => max(0.1, min(1.0, floatval($_POST['temperature'] ?? 0.7))),
                    'request_timeout' => max(10, min(120, intval($_POST['request_timeout'] ?? 30)))
                ];
                break;
                
            case 'cache':
                $settings = [
                    'enable_cache' => isset($_POST['enable_cache']) ? true : false,
                    'cache_ttl' => max(60, min(86400, intval($_POST['cache_ttl'] ?? 3600))),
                    'cache_driver' => trim($_POST['cache_driver'] ?? 'file'),
                    'max_cache_size' => max(10, min(1000, intval($_POST['max_cache_size'] ?? 100)))
                ];
                break;
                
            case 'email':
                $settings = [
                    'smtp_host' => trim($_POST['smtp_host'] ?? ''),
                    'smtp_port' => intval($_POST['smtp_port'] ?? 587),
                    'smtp_username' => trim($_POST['smtp_username'] ?? ''),
                    'smtp_encryption' => trim($_POST['smtp_encryption'] ?? 'tls'),
                    'from_email' => trim($_POST['from_email'] ?? ''),
                    'from_name' => trim($_POST['from_name'] ?? '')
                ];
                
                // Only update password if provided
                $smtpPassword = trim($_POST['smtp_password'] ?? '');
                if (!empty($smtpPassword)) {
                    // In a real application, you would encrypt this password
                    $settings['smtp_password'] = $smtpPassword;
                }
                break;
        }
        
        // Update settings in database
        if (updateCategorySettings($conn, $activeCategory, $settings)) {
            $successMessage = "Settings have been saved successfully.";
        } else {
            throw new Exception("Failed to save settings. Please try again.");
        }
    } catch (Exception $e) {
        $errorMessage = "Error saving settings: " . $e->getMessage();
    }
}

// Get current settings for the active category from database
$currentSettings = getCategorySettings($conn, $activeCategory);

// Function to safely display values
function safeEcho($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// Get AI models for dropdown selection
$aiModels = [];
try {
    $query = "SELECT id, name, type FROM ai_models WHERE status = 'active' ORDER BY type, name";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $aiModels[] = $row;
        }
    }
} catch (Exception $e) {
    // Silently handle error - we'll just show an empty dropdown
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
        .settings-card {
            margin-bottom: 2rem;
        }
        
        .settings-nav .nav-link {
            padding: 0.5rem 1rem;
            border-radius: 0;
            color: #5a5c69;
        }
        
        .settings-nav .nav-link.active {
            color: #4e73df;
            background-color: #f8f9fc;
            border-left: 3px solid #4e73df;
        }
        
        .settings-nav .nav-link:hover:not(.active) {
            background-color: #f8f9fc;
        }
        
        .settings-form label {
            font-weight: 600;
        }
        
        .settings-form .form-text {
            font-size: 0.8rem;
        }
        
        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .form-section:last-child {
            border-bottom: none;
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
            
            <div class="row mt-4">
                <!-- Settings Navigation -->
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Settings Categories</h5>
                        </div>
                        <div class="card-body p-0">
                            <ul class="nav flex-column settings-nav">
                                <?php foreach ($categories as $key => $label): ?>
                                    <li class="nav-item">
                                        <a class="nav-link <?= $activeCategory === $key ? 'active' : '' ?>" 
                                           href="?category=<?= $key ?>">
                                            <i class="fas fa-<?= getCategoryIcon($key) ?> me-2"></i> <?= $label ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Reset Settings -->
                    <div class="card mt-3">
                        <div class="card-body">
                            <form method="post" action="?category=<?= $activeCategory ?>" onsubmit="return confirm('Are you sure you want to reset the <?= $categories[$activeCategory] ?> to defaults?');">
                                <input type="hidden" name="reset_settings" value="1">
                                <button type="submit" class="btn btn-outline-danger w-100">
                                    <i class="fas fa-undo me-2"></i> Reset to Defaults
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Settings Form -->
                <div class="col-md-9">
                    <div class="card settings-card">
                        <div class="card-header">
                            <h5 class="mb-0"><?= $categories[$activeCategory] ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="?category=<?= $activeCategory ?>" class="settings-form">
                                <?php if ($activeCategory === 'general'): ?>
                                    <!-- General Settings -->
                                    <div class="form-section">
                                        <div class="mb-3">
                                            <label for="site_name" class="form-label">Site Name</label>
                                            <input type="text" class="form-control" id="site_name" name="site_name" 
                                                value="<?= safeEcho($currentSettings['site_name'] ?? 'SEO Analysis Tools') ?>">
                                            <div class="form-text">The name of your site displayed in various locations.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="result_limit" class="form-label">Results Per Page</label>
                                            <input type="number" class="form-control" id="result_limit" name="result_limit" 
                                                min="5" max="100" step="5"
                                                value="<?= safeEcho($currentSettings['result_limit'] ?? 10) ?>">
                                            <div class="form-text">Number of results to display per page (5-100).</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="default_language" class="form-label">Default Language</label>
                                            <select class="form-select" id="default_language" name="default_language">
                                                <option value="en" <?= ($currentSettings['default_language'] ?? 'en') === 'en' ? 'selected' : '' ?>>English</option>
                                                <option value="es" <?= ($currentSettings['default_language'] ?? '') === 'es' ? 'selected' : '' ?>>Spanish</option>
                                                <option value="fr" <?= ($currentSettings['default_language'] ?? '') === 'fr' ? 'selected' : '' ?>>French</option>
                                                <option value="de" <?= ($currentSettings['default_language'] ?? '') === 'de' ? 'selected' : '' ?>>German</option>
                                                <option value="it" <?= ($currentSettings['default_language'] ?? '') === 'it' ? 'selected' : '' ?>>Italian</option>
                                            </select>
                                            <div class="form-text">Default language for the application interface.</div>
                                        </div>
                                        
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="debug_mode" name="debug_mode" 
                                                <?= ($currentSettings['debug_mode'] ?? false) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="debug_mode">Debug Mode</label>
                                            <div class="form-text">Enable detailed error reporting (not recommended for production).</div>
                                        </div>
                                    </div>
                                    
                                <?php elseif ($activeCategory === 'api'): ?>
                                    <!-- API Settings -->
                                    <div class="form-section">
                                        <h6 class="text-primary mb-3">Google API</h6>
                                        <div class="mb-3">
                                            <label for="google_api_key" class="form-label">Google API Key</label>
                                            <input type="text" class="form-control" id="google_api_key" name="google_api_key" 
                                                value="<?= safeEcho($currentSettings['google_api_key'] ?? '') ?>">
                                            <div class="form-text">Used for Google Search Console, PageSpeed Insights, etc.</div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-section">
                                        <h6 class="text-primary mb-3">Moz API</h6>
                                        <div class="mb-3">
                                            <label for="moz_api_key" class="form-label">Moz API Key</label>
                                            <input type="text" class="form-control" id="moz_api_key" name="moz_api_key" 
                                                value="<?= safeEcho($currentSettings['moz_api_key'] ?? '') ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label for="moz_access_id" class="form-label">Access ID</label>
  <input type="text" class="form-control" id="moz_access_id" name="moz_access_id" 
                                                value="<?= safeEcho($currentSettings['moz_access_id'] ?? '') ?>">
                                            <div class="form-text">Used for domain authority, backlink data, etc.</div>
                                        </div>										
                                        <div class="mb-3">
                                            <label for="moz_secret_key" class="form-label">Moz Secret Key</label>
  <input type="text" class="form-control" id="moz_secret_key" name="moz_secret_key" 
                                                value="<?= safeEcho($currentSettings['moz_secret_key'] ?? '') ?>">
                                            <div class="form-text">Used for domain authority, backlink data, etc.</div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-section">
                                        <h6 class="text-primary mb-3">Other SEO APIs</h6>
                                        <div class="mb-3">
                                            <label for="semrush_api_key" class="form-label">SEMrush API Key</label>
                                            <input type="text" class="form-control" id="semrush_api_key" name="semrush_api_key" 
                                                value="<?= safeEcho($currentSettings['semrush_api_key'] ?? '') ?>">
                                            <div class="form-text">Used for keyword research, competitive analysis, etc.</div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="majestic_api_key" class="form-label">Majestic API Key</label>
                                            <input type="text" class="form-control" id="majestic_api_key" name="majestic_api_key" 
                                                value="<?= safeEcho($currentSettings['majestic_api_key'] ?? '') ?>">
                                            <div class="form-text">Used for backlink analysis, trust flow, etc.</div>
                                        </div>
                                    </div>
                                    
                                <?php elseif ($activeCategory === 'seo'): ?>
                                    <!-- SEO Analysis Settings -->
                                    <div class="form-section">
                                        <h6 class="text-primary mb-3">Crawling Configuration</h6>
                                        <div class="mb-3">
                                            <label for="crawl_depth" class="form-label">Crawl Depth</label>
                                            <input type="number" class="form-control" id="crawl_depth" name="crawl_depth" 
                                                min="1" max="10" step="1"
                                                value="<?= safeEcho($currentSettings['crawl_depth'] ?? 3) ?>">
                                            <div class="form-text">Maximum link depth to crawl (1-10).</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="crawl_delay" class="form-label">Crawl Delay (seconds)</label>
                                            <input type="number" class="form-control" id="crawl_delay" name="crawl_delay" 
                                                min="0" max="10" step="0.5"
                                                value="<?= safeEcho($currentSettings['crawl_delay'] ?? 1) ?>">
                                            <div class="form-text">Delay between requests to avoid server overload.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="crawl_timeout" class="form-label">Crawl Timeout (seconds)</label>
                                            <input type="number" class="form-control" id="crawl_timeout" name="crawl_timeout" 
                                                min="10" max="120" step="1"
                                                value="<?= safeEcho($currentSettings['crawl_timeout'] ?? 30) ?>">
                                            <div class="form-text">Maximum time to wait for a response.</div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-section">
                                        <h6 class="text-primary mb-3">Crawler Behavior</h6>
                                        <div class="mb-3">
                                            <label for="user_agent" class="form-label">User Agent</label>
                                            <input type="text" class="form-control" id="user_agent" name="user_agent" 
                                                value="<?= safeEcho($currentSettings['user_agent'] ?? 'SEO Analysis Tool Bot') ?>">
                                            <div class="form-text">User agent string sent with requests.</div>
                                        </div>
                                        
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="respect_robots_txt" name="respect_robots_txt" 
                                                <?= ($currentSettings['respect_robots_txt'] ?? true) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="respect_robots_txt">Respect robots.txt</label>
                                            <div class="form-text">Obey crawl directives in robots.txt files.</div>
                                        </div>
                                    </div>
                                    
                                <?php elseif ($activeCategory === 'ai'): ?>
                                    <!-- AI Settings -->
                                    <div class="form-section">
                                        <h6 class="text-primary mb-3">AI Model Configuration</h6>
                                        <div class="mb-3">
                                            <label for="default_ai_model" class="form-label">Default AI Model</label>
                                            <select class="form-select" id="default_ai_model" name="default_ai_model">
                                                <option value="0">None - No default model</option>
                                                <?php if (count($aiModels) > 0): ?>
                                                    <optgroup label="Online Models">
                                                        <?php foreach ($aiModels as $model): ?>
                                                            <?php if ($model['type'] === 'online'): ?>
                                                                <option value="<?= $model['id'] ?>" <?= ($currentSettings['default_ai_model'] ?? 0) == $model['id'] ? 'selected' : '' ?>>
                                                                    <?= safeEcho($model['name']) ?>
                                                                </option>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </optgroup>
                                                    <optgroup label="Local Models">
                                                        <?php foreach ($aiModels as $model): ?>
                                                            <?php if ($model['type'] === 'local'): ?>
                                                                <option value="<?= $model['id'] ?>" <?= ($currentSettings['default_ai_model'] ?? 0) == $model['id'] ? 'selected' : '' ?>>
                                                                    <?= safeEcho($model['name']) ?>
                                                                </option>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </optgroup>
                                                <?php else: ?>
                                                    <option value="" disabled>No AI models available</option>
                                                <?php endif; ?>
                                            </select>
                                            <div class="form-text">AI model to use by default for recommendations.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="max_tokens" class="form-label">Max Tokens</label>
                                            <input type="number" class="form-control" id="max_tokens" name="max_tokens" 
                                                min="100" max="4000" step="100"
                                                value="<?= safeEcho($currentSettings['max_tokens'] ?? 1000) ?>">
                                            <div class="form-text">Maximum tokens to generate in AI responses.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="temperature" class="form-label">Temperature</label>
                                            <input type="number" class="form-control" id="temperature" name="temperature" 
                                                min="0.1" max="1.0" step="0.1"
                                                value="<?= safeEcho($currentSettings['temperature'] ?? 0.7) ?>">
                                            <div class="form-text">Controls randomness (0.1-1.0). Lower values are more focused.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="request_timeout" class="form-label">Request Timeout (seconds)</label>
                                            <input type="number" class="form-control" id="request_timeout" name="request_timeout" 
                                                min="10" max="120" step="1"
                                                value="<?= safeEcho($currentSettings['request_timeout'] ?? 30) ?>">
                                            <div class="form-text">Maximum time to wait for AI API responses.</div>
                                        </div>
                                    </div>
                                    
                                <?php elseif ($activeCategory === 'cache'): ?>
                                    <!-- Cache Settings -->
                                    <div class="form-section">
                                        <h6 class="text-primary mb-3">Cache Configuration</h6>
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="enable_cache" name="enable_cache" 
                                                <?= ($currentSettings['enable_cache'] ?? true) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="enable_cache">Enable Caching</label>
                                            <div class="form-text">Store and reuse API responses to improve performance.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="cache_ttl" class="form-label">Cache TTL (seconds)</label>
                                            <input type="number" class="form-control" id="cache_ttl" name="cache_ttl" 
                                                min="60" max="86400" step="60"
                                                value="<?= safeEcho($currentSettings['cache_ttl'] ?? 3600) ?>">
                                            <div class="form-text">Time-to-live for cached data (60s - 24h).</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="cache_driver" class="form-label">Cache Driver</label>
                                            <select class="form-select" id="cache_driver" name="cache_driver">
                                                <option value="file" <?= ($currentSettings['cache_driver'] ?? 'file') === 'file' ? 'selected' : '' ?>>File System</option>
                                                <option value="database" <?= ($currentSettings['cache_driver'] ?? '') === 'database' ? 'selected' : '' ?>>Database</option>
                                                <option value="memory" <?= ($currentSettings['cache_driver'] ?? '') === 'memory' ? 'selected' : '' ?>>Memory (Redis/Memcached)</option>
                                            </select>
                                            <div class="form-text">Storage method for cached data.</div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="max_cache_size" class="form-label">Max Cache Size (MB)</label>
                                            <input type="number" class="form-control" id="max_cache_size" name="max_cache_size" 
                                                min="10" max="1000" step="10"
                                                value="<?= safeEcho($currentSettings['max_cache_size'] ?? 100) ?>">
                                            <div class="form-text">Maximum disk space for cache (10MB - 1GB).</div>
                                        </div>
                                    </div>
                                    
                                <?php elseif ($activeCategory === 'email'): ?>
                                    <!-- Email Settings -->
                                    <div class="form-section">
                                        <h6 class="text-primary mb-3">SMTP Configuration</h6>
                                        <div class="mb-3">
                                            <label for="smtp_host" class="form-label">SMTP Host</label>
                                            <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                                value="<?= safeEcho($currentSettings['smtp_host'] ?? '') ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="smtp_port" class="form-label">SMTP Port</label>
                                            <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                                value="<?= safeEcho($currentSettings['smtp_port'] ?? 587) ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="smtp_encryption" class="form-label">Encryption</label>
                                            <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                                                <option value="tls" <?= ($currentSettings['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                                <option value="ssl" <?= ($currentSettings['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                                <option value="none" <?= ($currentSettings['smtp_encryption'] ?? '') === 'none' ? 'selected' : '' ?>>None</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-section">
                                        <h6 class="text-primary mb-3">Authentication</h6>
                                        <div class="mb-3">
                                            <label for="smtp_username" class="form-label">SMTP Username</label>
                                            <input type="text" class="form-control" id="smtp_username" name="smtp_username" 
                                                value="<?= safeEcho($currentSettings['smtp_username'] ?? '') ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="smtp_password" class="form-label">SMTP Password</label>
                                            <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                                                placeholder="<?= !empty($currentSettings['smtp_password']) ? '********' : '' ?>">
                                            <div class="form-text">Leave blank to keep existing password.</div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-section">
                                        <h6 class="text-primary mb-3">Sender Information</h6>
                                        <div class="mb-3">
                                            <label for="from_email" class="form-label">From Email</label>
                                            <input type="email" class="form-control" id="from_email" name="from_email" 
                                                value="<?= safeEcho($currentSettings['from_email'] ?? '') ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="from_name" class="form-label">From Name</label>
                                            <input type="text" class="form-control" id="from_name" name="from_name" 
                                                value="<?= safeEcho($currentSettings['from_name'] ?? 'SEO Analysis Tools') ?>">
                                            <div class="form-text">Name displayed in the email's "From" field.</div>
                                        </div>
                                    </div>
                                    
                                <?php endif; ?>
                                
                                <div class="d-flex justify-content-end mt-4">
                                    <button type="submit" name="save_settings" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i> Save Settings
                                    </button>
                                </div>
                            </form>
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

<?php
// Helper function to get icon for category
function getCategoryIcon($category) {
    switch ($category) {
        case 'general':
            return 'cog';
        case 'api':
            return 'key';
        case 'seo':
            return 'search';
        case 'ai':
            return 'robot';
        case 'cache':
            return 'database';
        case 'email':
            return 'envelope';
        default:
            return 'cog';
    }
}
?>