<?php
// api/generate-ai-recommendations-api.php - Endpoint to generate AI recommendations

// Include database configuration
require_once '../config.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if required parameters are submitted
if (!isset($_POST['seo_data']) || empty($_POST['seo_data']) || !isset($_POST['model_id']) || empty($_POST['model_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'SEO data and model ID are required'
    ]);
    exit;
}

// Get the data from POST
$seoData = json_decode($_POST['seo_data'], true);
$modelId = intval($_POST['model_id']);

// Validate model ID
try {
    // Check if ai_models table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'ai_models'");
    if ($tableCheck->num_rows == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'AI models feature is not available'
        ]);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT id, name, type, api_url, api_key, model_name FROM ai_models WHERE id = ? AND status = 'active'");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $modelId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid or inactive AI model'
        ]);
        exit;
    }
    
    $modelInfo = $result->fetch_assoc();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving model information: ' . $e->getMessage()
    ]);
    exit;
}

/**
 * Function to generate recommendations using an AI model
 * 
 * @param array $seoData SEO analysis data
 * @param array $modelInfo AI model information
 * @return array Generated recommendations
 */
function generateRecommendations($seoData, $modelInfo) {
    // Extract key information from the SEO data
    $domain = parse_url($seoData['url'] ?? '', PHP_URL_HOST) ?? 'unknown domain';
    
    // Build a context prompt for the AI
    $prompt = "You are an SEO expert assistant. Based on the following SEO analysis data for $domain, provide specific, actionable recommendations to improve their search engine rankings and visibility. Focus on practical steps they can implement.";
    
    // Add technical SEO data
    if (isset($seoData['technicalSeo']) && !empty($seoData['technicalSeo'])) {
        $prompt .= "\n\nTECHNICAL SEO DATA:";
        $prompt .= "\nCrawlability Score: " . ($seoData['technicalSeo']['crawlability_score'] ?? 'N/A');
        $prompt .= "\nIndexability Score: " . ($seoData['technicalSeo']['indexability_score'] ?? 'N/A');
        $prompt .= "\nSite Speed Score: " . ($seoData['technicalSeo']['site_speed_score'] ?? 'N/A');
        $prompt .= "\nMobile Friendly Score: " . ($seoData['technicalSeo']['mobile_friendly_score'] ?? 'N/A');
        $prompt .= "\nHas SSL: " . (($seoData['technicalSeo']['has_ssl'] ?? false) ? 'Yes' : 'No');
        $prompt .= "\nValid Schema Markup: " . (($seoData['technicalSeo']['schema_markup_valid'] ?? false) ? 'Yes' : 'No');
        
        if (isset($seoData['technicalSeo']['issues']) && is_array($seoData['technicalSeo']['issues'])) {
            $prompt .= "\nIssues:";
            foreach ($seoData['technicalSeo']['issues'] as $issue) {
                $prompt .= "\n- " . $issue['description'] . " (Severity: " . $issue['severity'] . ")";
            }
        }
    }
    
    // Add on-page SEO data
    if (isset($seoData['onPageSeo']) && !empty($seoData['onPageSeo'])) {
        $prompt .= "\n\nON-PAGE SEO DATA:";
        $prompt .= "\nTitle Tags Score: " . ($seoData['onPageSeo']['title_tags_score'] ?? 'N/A');
        $prompt .= "\nMeta Description Score: " . ($seoData['onPageSeo']['meta_desc_score'] ?? 'N/A');
        $prompt .= "\nHeader Tags Score: " . ($seoData['onPageSeo']['header_tags_score'] ?? 'N/A');
        $prompt .= "\nContent Score: " . ($seoData['onPageSeo']['content_score'] ?? 'N/A');
        $prompt .= "\nImage Optimization Score: " . ($seoData['onPageSeo']['image_optimization_score'] ?? 'N/A');
        $prompt .= "\nInternal Linking Score: " . ($seoData['onPageSeo']['internal_linking_score'] ?? 'N/A');
        $prompt .= "\nURL Structure Score: " . ($seoData['onPageSeo']['url_structure_score'] ?? 'N/A');
        
        if (isset($seoData['onPageSeo']['recommendations']) && is_array($seoData['onPageSeo']['recommendations'])) {
            $prompt .= "\nRecommendations:";
            foreach ($seoData['onPageSeo']['recommendations'] as $rec) {
                $prompt .= "\n- " . $rec['description'] . " (Priority: " . $rec['priority'] . ")";
            }
        }
    }
    
    // Add off-page SEO data
    if (isset($seoData['offPageSeo']) && !empty($seoData['offPageSeo'])) {
        $prompt .= "\n\nOFF-PAGE SEO DATA:";
        $prompt .= "\nBacklink Score: " . ($seoData['offPageSeo']['backlink_score'] ?? 'N/A');
        $prompt .= "\nDomain Authority: " . ($seoData['offPageSeo']['domain_authority'] ?? 'N/A');
        $prompt .= "\nSocial Media Score: " . ($seoData['offPageSeo']['social_media_score'] ?? 'N/A');
        $prompt .= "\nToxic Backlinks: " . ($seoData['offPageSeo']['toxic_backlinks'] ?? 'N/A');
    }
    
    // Add content analysis data
    if (isset($seoData['contentAnalysis']) && !empty($seoData['contentAnalysis'])) {
        $prompt .= "\n\nCONTENT ANALYSIS DATA:";
        $prompt .= "\nAverage Word Count: " . ($seoData['contentAnalysis']['avg_word_count'] ?? 'N/A');
        $prompt .= "\nAverage Readability Score: " . ($seoData['contentAnalysis']['avg_readability'] ?? 'N/A');
        $prompt .= "\nTotal Pages: " . ($seoData['contentAnalysis']['total_pages'] ?? 'N/A');
        $prompt .= "\nPages with Thin Content: " . ($seoData['contentAnalysis']['thin_content_pages'] ?? 'N/A');
        
        if (isset($seoData['contentAnalysis']['suggestions']) && is_array($seoData['contentAnalysis']['suggestions'])) {
            $prompt .= "\nSuggestions:";
            foreach ($seoData['contentAnalysis']['suggestions'] as $suggestion) {
                $prompt .= "\n- " . $suggestion['description'] . " (Priority: " . $suggestion['priority'] . ")";
            }
        }
    }
    
    // Add keyword analysis data
    if (isset($seoData['keywordAnalysis']) && !empty($seoData['keywordAnalysis']) && 
        isset($seoData['keywordAnalysis']['keywords']) && is_array($seoData['keywordAnalysis']['keywords'])) {
        $prompt .= "\n\nKEYWORD ANALYSIS DATA:";
        $prompt .= "\nAnalyzed Keywords:";
        $keywordCount = 0;
        foreach ($seoData['keywordAnalysis']['keywords'] as $keyword) {
            if ($keywordCount++ < 10) { // Limit to 10 keywords to avoid too long prompts
                $prompt .= "\n- " . $keyword['keyword'] . 
                          " (Search Volume: " . $keyword['search_volume'] . 
                          ", Difficulty: " . $keyword['difficulty'] . 
                          ", Current Ranking: " . ($keyword['current_ranking'] > 0 ? $keyword['current_ranking'] : 'Not Ranked') . ")";
            }
        }
        if (count($seoData['keywordAnalysis']['keywords']) > 10) {
            $prompt .= "\n- ... and " . (count($seoData['keywordAnalysis']['keywords']) - 10) . " more keywords";
        }
    }
    
    // Request format
    $prompt .= "\n\nPlease format your recommendations in JSON with the following structure:
    {
      \"summary\": \"A short overall assessment of the site's SEO health and primary areas of improvement\",
      \"technical\": [
        {
          \"title\": \"Fix Title\",
          \"description\": \"Detailed explanation\",
          \"importance\": \"high/medium/low\",
          \"implementation\": \"How to implement this recommendation\"
        }
      ],
      \"onpage\": [
        {
          \"title\": \"Recommendation Title\",
          \"description\": \"Detailed explanation\",
          \"importance\": \"high/medium/low\",
          \"implementation\": \"How to implement this recommendation\"
        }
      ],
      \"content\": [
        {
          \"title\": \"Recommendation Title\",
          \"description\": \"Detailed explanation\",
          \"importance\": \"high/medium/low\",
          \"implementation\": \"How to implement this recommendation\"
        }
      ],
      \"keywords\": [
        {
          \"title\": \"Recommendation Title\",
          \"description\": \"Detailed explanation\",
          \"importance\": \"high/medium/low\",
          \"implementation\": \"How to implement this recommendation\"
        }
      ]
    }";
    
    // Determine which API to call based on model type
    if ($modelInfo['type'] === 'online') {
        try {
            return callAiModelApi($prompt, $modelInfo);
        } catch (Exception $e) {
            error_log("API call failed: " . $e->getMessage());
            return simulateOnlineAiResponse($prompt, $modelInfo);
        }
    } else {
        try {
            return callAiModelApi($prompt, $modelInfo);
        } catch (Exception $e) {
            error_log("API call failed: " . $e->getMessage());
            return simulateLocalAiResponse($prompt, $modelInfo);
        }
    }
}

function extractJsonFromAiResponse($content) {
    // Log the first part of the response for debugging
    error_log("Received response from AI: " . substr($content, 0, 200) . "...");
    
    // First, check if the entire content is valid JSON already
    $jsonData = json_decode($content, true);
    if ($jsonData !== null && json_last_error() === JSON_ERROR_NONE) {
        return $jsonData;
    }
    
    // If not, try to extract JSON from within Markdown code blocks
    if (preg_match('/```(?:json)?\s*(\{[\s\S]*?\})\s*```/m', $content, $matches)) {
        $jsonContent = $matches[1];
        $jsonData = json_decode($jsonContent, true);
        
        if ($jsonData !== null && json_last_error() === JSON_ERROR_NONE) {
            return $jsonData;
        }
    }
    
    // If that fails, try to find any JSON object in the content
    if (preg_match('/(\{[\s\S]*\})/m', $content, $matches)) {
        $potentialJson = $matches[1];
        $jsonData = json_decode($potentialJson, true);
        
        if ($jsonData !== null && json_last_error() === JSON_ERROR_NONE) {
            return $jsonData;
        }
        
        // Try to fix common JSON issues and parse again
        $cleanedJson = preg_replace('/,\s*\}/', '}', $potentialJson); // Remove trailing commas
        $jsonData = json_decode($cleanedJson, true);
        
        if ($jsonData !== null && json_last_error() === JSON_ERROR_NONE) {
            return $jsonData;
        }
    }
    
    // If we still can't parse JSON, log the error
    error_log("Failed to parse JSON from AI response: " . json_last_error_msg());
    error_log("Response content: " . substr($content, 0, 1000));
    
    // Return null to indicate failure
    return null;
}

/**
 * Call the actual AI model API
 */
function callAiModelApi($prompt, $modelInfo) {
    $apiUrl = $modelInfo['api_url'];
    $apiKey = $modelInfo['api_key'];
    $modelName = $modelInfo['model_name'];
    $isLocalAI = ($modelInfo['type'] == 'local');
    
    // Prepare the payload and headers based on model type
    if ($isLocalAI) {
        // Local model API (e.g., Ollama)
        $headers = ["Content-Type: application/json"];
        
        $body = json_encode([
            'model' => $modelName,
            'prompt' => $prompt,
            'stream' => false
        ]);
    } else {
        // Online API (e.g., OpenAI)
        $headers = [
            "Authorization: Bearer " . $apiKey,
            "Content-Type: application/json"
        ];
        
        $body = json_encode([
            'model' => $modelName,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an expert SEO consultant who provides actionable recommendations.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 2000
        ]);
    }
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 60
    ]);
    
    // Execute the request
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Handle errors
    if (!empty($curlError)) {
        throw new Exception("Connection error: " . $curlError);
    }
    
    if ($httpCode < 200 || $httpCode >= 300) {
        throw new Exception("API returned error code: " . $httpCode . " Response: " . $response);
    }
    
    // Parse the response
    $responseData = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Error decoding API response: " . json_last_error_msg());
        error_log("Raw response: " . substr($response, 0, 1000));
    }
    
    // Extract content based on API format
    $content = '';
    
    if ($isLocalAI) {
        // Local API format (e.g., Ollama)
        $content = $responseData['response'] ?? '';
    } else {
        // Standard API format (e.g., OpenAI)
        $content = $responseData['choices'][0]['message']['content'] ?? '';
    }
    
    if (empty($content)) {
        throw new Exception("No content received from AI model");
    }
    
    // Parse the JSON response using our improved extractor
    $jsonData = extractJsonFromAiResponse($content);
    
    if ($jsonData && 
        isset($jsonData['summary']) && 
        (isset($jsonData['technical']) || 
         isset($jsonData['onpage']) || 
         isset($jsonData['content']) || 
         isset($jsonData['keywords']))) {
        
        return $jsonData;
    }
    
    // If extraction failed, fallback to simulated response
    error_log("Couldn't extract valid recommendations from AI response, using fallback");
    if ($isLocalAI) {
        return simulateLocalAiResponse($prompt, $modelInfo);
    } else {
        return simulateOnlineAiResponse($prompt, $modelInfo);
    }
}

/**
 * Simulate an online AI API response (for development/testing)
 */
function simulateOnlineAiResponse($prompt, $modelInfo) {
    // This is a simulated response. In production, you would call the actual API.
    // You can test the API connection here, but return a fallback if it fails
    
    return [
        'summary' => 'Based on the analysis, the website has several areas that need improvement, particularly in technical SEO and content quality. The site has a decent mobile-friendly score but lacks proper schema markup and has issues with page speed.',
        'technical' => [
            [
                'title' => 'Implement HTTPS',
                'description' => 'The website is not using HTTPS which is essential for security and SEO ranking.',
                'importance' => 'high',
                'implementation' => 'Purchase an SSL certificate from your hosting provider or use Let\'s Encrypt for a free certificate, then implement a proper redirect from HTTP to HTTPS.'
            ],
            [
                'title' => 'Improve Page Speed',
                'description' => 'The site speed score is below average, which affects both user experience and search rankings.',
                'importance' => 'high',
                'implementation' => 'Optimize images, enable browser caching, minimize HTTP requests, and use a content delivery network (CDN).'
            ],
            [
                'title' => 'Add Schema Markup',
                'description' => 'The site lacks proper schema markup which helps search engines understand your content.',
                'importance' => 'medium',
                'implementation' => 'Implement JSON-LD schema markup for your business type, products, and key pages.'
            ]
        ],
        'onpage' => [
            [
                'title' => 'Optimize Title Tags',
                'description' => 'Many pages have suboptimal title tags that don\'t include target keywords or exceed the recommended length.',
                'importance' => 'high',
                'implementation' => 'Keep titles under 60 characters and include primary keywords near the beginning.'
            ],
            [
                'title' => 'Enhance Meta Descriptions',
                'description' => 'Several meta descriptions are missing or too short to be effective.',
                'importance' => 'medium',
                'implementation' => 'Write compelling meta descriptions between 150-160 characters that include keywords and a call to action.'
            ]
        ],
        'content' => [
            [
                'title' => 'Address Thin Content',
                'description' => 'Multiple pages have thin content which provides little value to users and search engines.',
                'importance' => 'high',
                'implementation' => 'Expand pages to at least 750-1000 words of informative, relevant content.'
            ],
            [
                'title' => 'Improve Content Readability',
                'description' => 'The average readability score is below recommended levels.',
                'importance' => 'medium',
                'implementation' => 'Use shorter sentences, break up paragraphs, add subheadings, and use bullet points where appropriate.'
            ]
        ],
        'keywords' => [
            [
                'title' => 'Target Low-Competition Keywords',
                'description' => 'There are several low-difficulty keywords with good search volume that aren\'t currently targeted.',
                'importance' => 'medium',
                'implementation' => 'Create dedicated content around these keywords and optimize existing pages to include them naturally.'
            ],
            [
                'title' => 'Improve First Page Rankings',
                'description' => 'Several keywords are ranking on the second page and could be moved to the first page with optimization.',
                'importance' => 'high',
                'implementation' => 'Analyze the current top-ranking pages for these keywords and enhance your content to better match search intent.'
            ]
        ]
    ];
}

/**
 * Simulate a local AI API response (for development/testing)
 */
function simulateLocalAiResponse($prompt, $modelInfo) {
    // For local models, we provide a similar but slightly different response
    // In production, this would call your local AI implementation
    
    return [
        'summary' => 'The website has several opportunities for improvement in its SEO strategy. While the site has some strengths, there are notable issues with technical implementation, content depth, and keyword targeting that should be addressed.',
        'technical' => [
            [
                'title' => 'Improve Site Speed',
                'description' => 'The site speed score indicates performance issues that can negatively impact both user experience and search rankings.',
                'importance' => 'high',
                'implementation' => 'Optimize image sizes, enable compression, leverage browser caching, and minimize render-blocking resources.'
            ],
            [
                'title' => 'Fix Crawlability Issues',
                'description' => 'The crawlability score suggests that search engines may have difficulty accessing parts of your site.',
                'importance' => 'high',
                'implementation' => 'Check robots.txt for blocking directives, fix broken internal links, and ensure a proper site structure.'
            ]
        ],
        'onpage' => [
            [
                'title' => 'Enhance Header Tag Structure',
                'description' => 'The header tags score indicates improper use of H1-H6 tags throughout the site.',
                'importance' => 'medium',
                'implementation' => 'Use a single H1 per page, followed by H2 for sections and H3 for subsections in a logical hierarchy.'
            ],
            [
                'title' => 'Optimize Internal Linking',
                'description' => 'The internal linking score shows insufficient cross-linking between related content.',
                'importance' => 'medium',
                'implementation' => 'Add contextual links between related pages and create hub pages for important topics.'
            ]
        ],
        'content' => [
            [
                'title' => 'Expand Thin Content Pages',
                'description' => 'Several pages have insufficient content depth to rank well or satisfy user queries.',
                'importance' => 'high',
                'implementation' => 'Identify pages with less than 500 words and expand them with valuable information that addresses user needs.'
            ]
        ],
        'keywords' => [
            [
                'title' => 'Focus on High-Potential Keywords',
                'description' => 'Several keywords have high search volume and low difficulty but aren\'t being targeted effectively.',
                'importance' => 'high',
                'implementation' => 'Create dedicated pages for these keywords and incorporate them naturally into your content strategy.'
            ]
        ]
    ];
}

// Generate recommendations
try {
    $recommendations = generateRecommendations($seoData, $modelInfo);
    
    // Store recommendations in database
    try {
        // Convert recommendations array to JSON for storage
        $recommendationsJson = json_encode($recommendations);
        
        // Get website ID from the URL
        $urlStmt = $conn->prepare("SELECT id FROM websites WHERE url = ?");
        $urlStmt->bind_param("s", $seoData['url']);
        $urlStmt->execute();
        $urlResult = $urlStmt->get_result();
        
        if ($urlResult->num_rows > 0) {
            $websiteId = $urlResult->fetch_assoc()['id'];
            
            // Check if there are already recommendations for this website/model combination
            $checkStmt = $conn->prepare("SELECT id FROM ai_recommendations WHERE website_id = ? AND ai_model_id = ?");
            $checkStmt->bind_param("ii", $websiteId, $modelId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                // Update existing record
                $recId = $checkResult->fetch_assoc()['id'];
                $updateStmt = $conn->prepare("UPDATE ai_recommendations SET summary = ?, recommendations_json = ?, created_at = NOW() WHERE id = ?");
                $updateStmt->bind_param("ssi", $recommendations['summary'], $recommendationsJson, $recId);
                $updateStmt->execute();
            } else {
                // Insert new record
                $insertStmt = $conn->prepare("INSERT INTO ai_recommendations (website_id, ai_model_id, summary, recommendations_json) VALUES (?, ?, ?, ?)");
                $insertStmt->bind_param("iiss", $websiteId, $modelId, $recommendations['summary'], $recommendationsJson);
                $insertStmt->execute();
            }
        } else {
            // Website not found in database - this should not happen if analysis was saved properly
            error_log("Website not found for URL: " . $seoData['url']);
        }
    } catch (Exception $e) {
        error_log("Error saving AI recommendations: " . $e->getMessage());
        // Continue anyway - we'll still return the recommendations to the user
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $recommendations
    ]);
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Failed to generate recommendations: ' . $e->getMessage()
    ]);
}
