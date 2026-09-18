<?php
// config.php - Database and API configuration file (Sanitized for Open Source / GitHub)

// Database credentials
define('DB_SERVER', '127.0.0.1');
define('DB_PORT',   3306);
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'ai_seo_tools');

// Google Business API Key (Replace with your actual API key)
define('GOOGLE_BUSINESS_API_KEY', 'YOUR_GOOGLE_BUSINESS_API_KEY');
 
// API MODE TOGGLE - Set to true to use online APIs, false to use local JSON files
define('USE_ONLINE_API', true);
define('DEV_MODE', false); // Set to true for development/local testing

// Path definitions
define('BASE_PATH', __DIR__);
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('API_PATH', BASE_PATH . '/api');
define('TEMPLATES_PATH', BASE_PATH . '/templates');
define('LOGS_PATH', BASE_PATH . '/logs');
define('WORKERS_PATH', BASE_PATH . '/workers');
define('CACHE_PATH', BASE_PATH . '/cache');

// Use absolute paths for local JSON files
define('LOCAL_JSON_PATH', __DIR__ . '/dev_data/'); // Absolute path to data directory
define('PAGESPEED_JSON_FILE', LOCAL_JSON_PATH . 'pagespeed.json');

// Create dev_data directory if it doesn't exist
if (!is_dir(LOCAL_JSON_PATH)) {
    mkdir(LOCAL_JSON_PATH, 0755, true);
    error_log("Created directory: " . LOCAL_JSON_PATH);
}

// Check if pagespeed.json exists and log the full path
if (!file_exists(PAGESPEED_JSON_FILE)) {
    error_log("PageSpeed JSON file not found at: " . PAGESPEED_JSON_FILE);
    error_log("Current working directory: " . getcwd());
}

// Attempt to connect to MySQL database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME, (defined('DB_PORT') ? DB_PORT : 3306));

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to UTF-8
$conn->set_charset("utf8mb4");

// Google PageSpeed Insights API
define('PAGESPEED_API_KEY', 'YOUR_PAGESPEED_API_KEY'); 
define('PAGESPEED_API_ENDPOINT', 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed');

// Schema.org Validator API
define('SCHEMA_VALIDATOR_ENDPOINT', 'https://validator.schema.org/validate');
define('SCHEMA_JSON_FILE', LOCAL_JSON_PATH . 'schema.json');

// Proxy Configuration
define('USE_PROXY', false);
define('PROXY_HOST', '');
define('PROXY_PORT', '');
define('PROXY_USERNAME', '');
define('PROXY_PASSWORD', '');
?>
