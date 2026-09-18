<?php
// ai-model-tester.php - Utility for testing AI model connections and responses

// Include database configuration
require_once '../config.php';

// Set page title
$pageTitle = "AI Model Tester";

// Variables for form handling
$selectedModel = null;
$testPrompt = "Generate 3 SEO tips for improving a small business website.";
$testResult = null;
$testError = null;
$responseTime = null;

// Get all active AI models
$aiModels = [];
try {
    $sql = "SELECT id, name, type, api_url, model_name FROM ai_models WHERE status = 'active' ORDER BY type, name";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $aiModels[] = $row;
        }
    }
} catch (Exception $e) {
    $testError = "Error retrieving AI models: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_model'])) {
    $modelId = intval($_POST['model_id']);
    $testPrompt = trim($_POST['prompt']);
    
    // Validate inputs
    if ($modelId <= 0) {
        $testError = "Please select a valid AI model";
    } elseif (empty($testPrompt)) {
        $testError = "Please enter a test prompt";
    } else {
        try {
            // Get model details
            $stmt = $conn->prepare("SELECT * FROM ai_models WHERE id = ? AND status = 'active'");
            $stmt->bind_param("i", $modelId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $selectedModel = $result->fetch_assoc();
                
                // Test the model
                $startTime = microtime(true);
                $testResult = testAiModel($selectedModel, $testPrompt);
                $endTime = microtime(true);
                $responseTime = round(($endTime - $startTime) * 1000); // Convert to milliseconds
            } else {
                $testError = "Selected AI model not found or inactive";
            }
        } catch (Exception $e) {
            $testError = "Error: " . $e->getMessage();
        }
    }
}

/**
 * Function to test an AI model
 * @param array $model Model details
 * @param string $prompt Test prompt
 * @return string|null Response or null on error
 */
function testAiModel($model, $prompt) {
    $apiUrl = $model['api_url'];
    $apiKey = $model['api_key'];
    $modelName = $model['model_name'];
    $type = $model['type'];
    
    // Prepare the request payload based on the model type and API format
    $payload = [];
    
    // Handle different API formats
    if (strpos($apiUrl, 'openai.com') !== false) {
        // OpenAI API format
        $payload = [
            'model' => $modelName,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an expert SEO consultant who provides actionable recommendations.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 500
        ];
    } elseif (strpos($apiUrl, 'googleapis.com') !== false) {
        // Google Gemini API format
        $payload = [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 500
            ]
        ];
    } else {
        // Generic JSON API format as fallback (works for most local models too)
        $payload = [
            'prompt' => $prompt,
            'model' => $modelName,
            'max_tokens' => 500
        ];
    }
    
    // Make the API request
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);	
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    
    $headers = ['Content-Type: application/json'];
    
    // Add API key if available
    if (!empty($apiKey)) {
        $headers[] = 'Authorization: Bearer ' . $apiKey;
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30-second timeout
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    
    curl_close($ch);
    
    if (!empty($curlError)) {
        throw new Exception("Connection error: " . $curlError);
    }
    
    if ($httpCode < 200 || $httpCode >= 300) {
        throw new Exception("API returned error code: " . $httpCode . " Response: " . $response);
    }
    
    // Parse the response
    $responseData = json_decode($response, true);
    
    // Extract content based on API format
    $content = '';
    
    if (strpos($apiUrl, 'openai.com') !== false && isset($responseData['choices'][0]['message']['content'])) {
        // OpenAI response format
        $content = $responseData['choices'][0]['message']['content'];
    } elseif (strpos($apiUrl, 'googleapis.com') !== false && isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
        // Google Gemini response format
        $content = $responseData['candidates'][0]['content']['parts'][0]['text'];
    } elseif (isset($responseData['result']) || isset($responseData['response']) || isset($responseData['output'])) {
        // Common alternative formats
        $content = $responseData['result'] ?? $responseData['response'] ?? $responseData['output'] ?? '';
    } elseif (isset($responseData['choices'][0]['text'])) {
        // Generic LLM format
        $content = $responseData['choices'][0]['text'];
    } else {
        // If we can't parse the specific format, return the whole response
        $content = "Raw response: " . $response;
    }
    
    return $content;
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
    <link rel="stylesheet" href="../css/style.css">
    
    <style>
        .test-form-card {
            margin-bottom: 30px;
        }
        
        .result-container {
            min-height: 200px;
            max-height: 500px;
            overflow-y: auto;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            background-color: #f8f9fa;
            white-space: pre-wrap;
        }
        
        .response-time {
            position: absolute;
            top: 15px;
            right: 15px;
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
                    <a href="ai-model-management.php" class="btn btn-outline-secondary ms-2">
                        <i class="fas fa-cog"></i> Manage AI Models
                    </a>
                </div>
            </div>
        </div>
        
        <div class="container py-4">
            <h1><?= $pageTitle ?></h1>
            <p class="lead">Test your AI model connections and responses</p>
            
            <?php if (empty($aiModels)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> No active AI models found. 
                    <a href="ai-model-management.php" class="alert-link">Add or activate an AI model</a> to use this tester.
                </div>
            <?php else: ?>
                <!-- Test Form -->
                <div class="card test-form-card">
                    <div class="card-header">
                        <h5 class="mb-0">Test AI Model</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="model_id" class="form-label">Select AI Model</label>
                                <select class="form-select" id="model_id" name="model_id" required>
                                    <option value="">-- Select a model --</option>
                                    
                                    <?php if (count(array_filter($aiModels, function($m) { return $m['type'] === 'online'; })) > 0): ?>
                                        <optgroup label="Online Models">
                                            <?php foreach ($aiModels as $model): ?>
                                                <?php if ($model['type'] === 'online'): ?>
                                                    <option value="<?= $model['id'] ?>" <?= (isset($_POST['model_id']) && intval($_POST['model_id']) === $model['id']) ? 'selected' : '' ?>>
                                                        <?= safeEcho($model['name']) ?> (<?= safeEcho($model['model_name']) ?>)
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endif; ?>
                                    
                                    <?php if (count(array_filter($aiModels, function($m) { return $m['type'] === 'local'; })) > 0): ?>
                                        <optgroup label="Local Models">
                                            <?php foreach ($aiModels as $model): ?>
                                                <?php if ($model['type'] === 'local'): ?>
                                                    <option value="<?= $model['id'] ?>" <?= (isset($_POST['model_id']) && intval($_POST['model_id']) === $model['id']) ? 'selected' : '' ?>>
                                                        <?= safeEcho($model['name']) ?> (<?= safeEcho($model['model_name']) ?>)
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="prompt" class="form-label">Test Prompt</label>
                                <textarea class="form-control" id="prompt" name="prompt" rows="4" required><?= safeEcho($testPrompt) ?></textarea>
                                <div class="form-text">Enter a prompt to send to the AI model for testing</div>
                            </div>
                            
                            <button type="submit" name="test_model" class="btn btn-primary">
                                <i class="fas fa-play-circle"></i> Test Model
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Test Results -->
                <?php if ($testError): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> Error: <?= safeEcho($testError) ?>
                    </div>
                <?php elseif ($testResult): ?>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Test Results</h5>
                            <?php if ($responseTime): ?>
                                <span class="badge bg-info">Response time: <?= $responseTime ?>ms</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h6>Model: <?= safeEcho($selectedModel['name']) ?> (<?= safeEcho($selectedModel['model_name']) ?>)</h6>
                            
                            <h6 class="mt-3">Prompt:</h6>
                            <div class="p-3 mb-3 bg-light border rounded">
                                <?= nl2br(safeEcho($testPrompt)) ?>
                            </div>
                            
                            <h6>Response:</h6>
                            <div class="result-container">
                                <?= nl2br(safeEcho($testResult)) ?>
                            </div>
                            
                            <div class="mt-3 text-muted small">
                                <p>
                                    <strong>API URL:</strong> <?= safeEcho($selectedModel['api_url']) ?><br>
                                    <strong>Model Type:</strong> <?= ucfirst(safeEcho($selectedModel['type'])) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Quick Tips -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Tips for Testing AI Models</h5>
                    </div>
                    <div class="card-body">
                        <h6>Common Test Prompts</h6>
                        <div class="list-group mb-3">
                            <button type="button" class="list-group-item list-group-item-action" onclick="setPrompt('Generate 3 SEO tips for improving a small business website.')">
                                SEO Tips for Small Business
                            </button>
                            <button type="button" class="list-group-item list-group-item-action" onclick="setPrompt('Analyze the following SEO issues and provide recommendations:\n- Slow page load time\n- Missing meta descriptions\n- Duplicate content')">
                                SEO Issue Analysis
                            </button>
                            <button type="button" class="list-group-item list-group-item-action" onclick="setPrompt('Create a checklist for on-page SEO optimization in JSON format.')">
                                On-Page SEO Checklist (JSON)
                            </button>
                            <button type="button" class="list-group-item list-group-item-action" onclick="setPrompt('What are the most important ranking factors for e-commerce websites in 2024?')">
                                E-commerce Ranking Factors
                            </button>
                        </div>
                        
                        <h6>Troubleshooting Tips</h6>
                        <ul>
                            <li>Ensure your API key is valid and has not expired</li>
                            <li>Check that the API URL format is correct for your chosen model</li>
                            <li>If using a local model, verify that the server is running and accessible</li>
                            <li>For rate-limited APIs, wait a few minutes between tests</li>
                            <li>Test with shorter prompts if you encounter timeout issues</li>
                        </ul>
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
        // Set prompt text from examples
        function setPrompt(promptText) {
            document.getElementById('prompt').value = promptText;
        }
    </script>
</body>
</html>
