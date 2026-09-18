<?php
// setup-ai-tables.php - Creates and populates AI model tables in the database

// Include database configuration
require_once '../config.php';

// Create ai_models table
$createAiModelsTable = "
CREATE TABLE IF NOT EXISTS `ai_models` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('online','local') NOT NULL DEFAULT 'online',
  `api_url` varchar(255) DEFAULT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `model_name` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Create ai_recommendations table
$createAiRecommendationsTable = "
CREATE TABLE IF NOT EXISTS `ai_recommendations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `website_id` int(11) NOT NULL,
  `ai_model_id` int(11) NOT NULL,
  `summary` text DEFAULT NULL,
  `recommendations_json` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `website_id` (`website_id`),
  KEY `ai_model_id` (`ai_model_id`),
  CONSTRAINT `ai_recommendations_ibfk_1` FOREIGN KEY (`website_id`) REFERENCES `websites` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ai_recommendations_ibfk_2` FOREIGN KEY (`ai_model_id`) REFERENCES `ai_models` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Sample data for ai_models
$sampleAiModels = "
INSERT INTO `ai_models` (`name`, `description`, `type`, `api_url`, `api_key`, `model_name`, `status`) VALUES
('OpenAI GPT-4', 'Advanced AI model for comprehensive SEO analysis and recommendations', 'online', 'https://api.openai.com/v1/chat/completions', '', 'gpt-4', 'active'),
('OpenAI GPT-3.5 Turbo', 'Balanced model for good recommendations with faster response time', 'online', 'https://api.openai.com/v1/chat/completions', '', 'gpt-3.5-turbo', 'active'),
('Google Gemini', 'Google\'s AI assistant for SEO analysis', 'online', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent', '', 'gemini-pro', 'active'),
('Local Llama 3', 'Self-hosted open-source model for privacy-focused recommendations', 'local', 'http://localhost:8080/v1/chat/completions', '', 'llama-3-8b', 'active'),
('Local Mistral', 'Fast local model for basic SEO recommendations', 'local', 'http://localhost:8080/v1/chat/completions', '', 'mistral-7b', 'active');
";

// Execute queries
try {
    // Create ai_models table
    if ($conn->query($createAiModelsTable) === TRUE) {
        echo "AI Models table created successfully<br>";
    } else {
        echo "Error creating AI Models table: " . $conn->error . "<br>";
    }
    
    // Create ai_recommendations table
    if ($conn->query($createAiRecommendationsTable) === TRUE) {
        echo "AI Recommendations table created successfully<br>";
    } else {
        echo "Error creating AI Recommendations table: " . $conn->error . "<br>";
    }
    
    // Check if ai_models table is empty before inserting sample data
    $result = $conn->query("SELECT COUNT(*) as count FROM ai_models");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        // Insert sample data
        if ($conn->multi_query($sampleAiModels) === TRUE) {
            echo "Sample AI models added successfully<br>";
        } else {
            echo "Error adding sample AI models: " . $conn->error . "<br>";
        }
    } else {
        echo "AI models table already has data, skipping sample data insertion<br>";
    }
    
    echo "Setup completed!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}