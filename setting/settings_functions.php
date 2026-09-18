<?php
/**
 * settings_functions.php - Functions for managing application settings
 */

/**
 * Get a setting value by category and key
 * 
 * @param mysqli $conn Database connection
 * @param string $category Setting category
 * @param string $key Setting key
 * @param mixed $default Default value if setting not found
 * @return mixed Setting value
 */
function getSetting($conn, $category, $key, $default = null) {
    $stmt = $conn->prepare("SELECT value, data_type FROM settings WHERE category = ? AND `key` = ? LIMIT 1");
    $stmt->bind_param("ss", $category, $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return $default;
    }
    
    $row = $result->fetch_assoc();
    $value = $row['value'];
    $dataType = $row['data_type'];
    
    // Convert value based on data type
    switch ($dataType) {
        case 'integer':
            return intval($value);
        case 'float':
            return floatval($value);
        case 'boolean':
            return $value == '1' ? true : false;
        case 'json':
            return json_decode($value, true);
        case 'string':
        default:
            return $value;
    }
}

/**
 * Get all settings for a category
 * 
 * @param mysqli $conn Database connection
 * @param string $category Setting category
 * @return array Settings for the category
 */
function getCategorySettings($conn, $category) {
    $settings = [];
    
    $stmt = $conn->prepare("SELECT `key`, value, data_type FROM settings WHERE category = ?");
    $stmt->bind_param("s", $category);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $key = $row['key'];
        $value = $row['value'];
        $dataType = $row['data_type'];
        
        // Convert value based on data type
        switch ($dataType) {
            case 'integer':
                $settings[$key] = intval($value);
                break;
            case 'float':
                $settings[$key] = floatval($value);
                break;
            case 'boolean':
                $settings[$key] = $value == '1' ? true : false;
                break;
            case 'json':
                $settings[$key] = json_decode($value, true);
                break;
            case 'string':
            default:
                $settings[$key] = $value;
                break;
        }
    }
    
    return $settings;
}

/**
 * Update a setting value
 * 
 * @param mysqli $conn Database connection
 * @param string $category Setting category
 * @param string $key Setting key
 * @param mixed $value New setting value
 * @param string|null $dataType Data type (if null, will be determined automatically)
 * @return bool True if successful, false otherwise
 */
function updateSetting($conn, $category, $key, $value, $dataType = null) {
    // Determine data type if not provided
    if ($dataType === null) {
        if (is_bool($value)) {
            $dataType = 'boolean';
            $value = $value ? '1' : '0';
        } elseif (is_int($value)) {
            $dataType = 'integer';
            $value = (string)$value;
        } elseif (is_float($value)) {
            $dataType = 'float';
            $value = (string)$value;
        } elseif (is_array($value) || is_object($value)) {
            $dataType = 'json';
            $value = json_encode($value);
        } else {
            $dataType = 'string';
        }
    } else {
        // Convert value based on specified data type
        switch ($dataType) {
            case 'boolean':
                $value = $value ? '1' : '0';
                break;
            case 'integer':
            case 'float':
                $value = (string)$value;
                break;
            case 'json':
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }
                break;
        }
    }
    
    // Check if setting exists
    $stmt = $conn->prepare("SELECT id FROM settings WHERE category = ? AND `key` = ?");
    $stmt->bind_param("ss", $category, $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing setting
        $stmt = $conn->prepare("UPDATE settings SET value = ? WHERE category = ? AND `key` = ?");
        $stmt->bind_param("sss", $value, $category, $key);
        return $stmt->execute();
    } else {
        // Insert new setting
        $stmt = $conn->prepare("INSERT INTO settings (category, `key`, value, data_type) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $category, $key, $value, $dataType);
        return $stmt->execute();
    }
}

/**
 * Update multiple settings at once
 * 
 * @param mysqli $conn Database connection
 * @param string $category Setting category
 * @param array $settings Array of key => value pairs
 * @return bool True if all updates were successful, false otherwise
 */
function updateCategorySettings($conn, $category, $settings) {
    $success = true;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        foreach ($settings as $key => $value) {
            if (!updateSetting($conn, $category, $key, $value)) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            $conn->commit();
        } else {
            $conn->rollback();
        }
    } catch (Exception $e) {
        $conn->rollback();
        $success = false;
    }
    
    return $success;
}

/**
 * Delete a setting
 * 
 * @param mysqli $conn Database connection
 * @param string $category Setting category
 * @param string $key Setting key
 * @return bool True if successful, false otherwise
 */
function deleteSetting($conn, $category, $key) {
    $stmt = $conn->prepare("DELETE FROM settings WHERE category = ? AND `key` = ?");
    $stmt->bind_param("ss", $category, $key);
    return $stmt->execute();
}

/**
 * Reset settings to default values
 * 
 * @param mysqli $conn Database connection
 * @param string $category Setting category (or null for all categories)
 * @return bool True if successful, false otherwise
 */
function resetSettings($conn, $category = null) {
    // Define default settings
    $defaultSettings = [
        'general' => [
            'site_name' => 'SEO Analysis Tools',
            'result_limit' => 10,
            'default_language' => 'en',
            'debug_mode' => false
        ],
        'api' => [
            'google_api_key' => '',
            'moz_api_key' => '',
			'moz_access_id' => '',
            'moz_secret_key' => '',
            'semrush_api_key' => '',
            'majestic_api_key' => ''
        ],
        'seo' => [
            'crawl_depth' => 3,
            'crawl_delay' => 1,
            'user_agent' => 'SEO Analysis Tool Bot',
            'respect_robots_txt' => true,
            'crawl_timeout' => 30
        ],
        'ai' => [
            'default_ai_model' => 0,
            'max_tokens' => 1000,
            'temperature' => 0.7,
            'request_timeout' => 30
        ],
        'cache' => [
            'enable_cache' => true,
            'cache_ttl' => 3600,
            'cache_driver' => 'file',
            'max_cache_size' => 100
        ],
        'email' => [
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_encryption' => 'tls',
            'from_email' => '',
            'from_name' => 'SEO Analysis Tools'
        ]
    ];
    
    // Reset settings for specific category or all categories
    if ($category !== null) {
        if (!isset($defaultSettings[$category])) {
            return false;
        }
        
        return updateCategorySettings($conn, $category, $defaultSettings[$category]);
    } else {
        $success = true;
        
        foreach ($defaultSettings as $cat => $settings) {
            if (!updateCategorySettings($conn, $cat, $settings)) {
                $success = false;
            }
        }
        
        return $success;
    }
}
