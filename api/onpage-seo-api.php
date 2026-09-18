<?php
// Include database configuration
require_once '../config.php';

require_once __DIR__ . '/../includes/security.php';
api_session_boot();

// Set header to return JSON
header('Content-Type: application/json');

// Check if URL is submitted
if (!isset($_POST['url']) || empty($_POST['url'])) {
    echo json_encode([
        'success' => false,
        'message' => 'URL is required'
    ]);
    exit;
}

// Get the URL from POST data
$url = trim($_POST['url']);

// Validate URL format
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid URL format'
    ]);
    exit;
}

// Extract domain name from URL
$domain = parse_url($url, PHP_URL_HOST);
if (substr($domain, 0, 4) === 'www.') {
    $domain = substr($domain, 4);
}

// Function to fetch and parse HTML content
function fetchHTMLContent($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; SEOToolsBot/1.0)');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300 && !empty($html)) {
        return $html;
    }
    
    return null;
}

// Function to get meta content from HTML
function getMetaContent($html, $metaName) {
    $pattern = '/<meta\s+(?:name|property)=["\']\s*' . preg_quote($metaName, '/') . '\s*["\']\s+content=["\'](.*?)["\']\s*\/?>/i';
    if (preg_match($pattern, $html, $matches)) {
        return trim($matches[1]);
    }
    
    // Try open graph tags
    $pattern = '/<meta\s+property=["\']\s*og:' . preg_quote($metaName, '/') . '\s*["\']\s+content=["\'](.*?)["\']\s*\/?>/i';
    if (preg_match($pattern, $html, $matches)) {
        return trim($matches[1]);
    }
    
    return '';
}

// Function to analyze on-page SEO using real data and APIs
function analyzeOnPageSEO($url, $domain) {
    $html = fetchHTMLContent($url);
    
    // Initialize scores and recommendations
    $titleTagsScore = 0;
    $metaDescScore = 0;
    $headerTagsScore = 0;
    $contentScore = 0;
    $imageOptScore = 0;
    $internalLinkingScore = 0;
    $urlStructureScore = 0;
    $recommendations = [];
    
    if (!$html) {
        // Failed to fetch the page
        return [
            'title_tags_score' => 0,
            'meta_desc_score' => 0,
            'header_tags_score' => 0,
            'content_score' => 0,
            'image_optimization_score' => 0,
            'internal_linking_score' => 0,
            'url_structure_score' => 0,
            'recommendations' => [
                [
                    'description' => 'Failed to fetch page content. Please check if the URL is accessible.',
                    'priority' => 'high'
                ]
            ]
        ];
    }
    
    // Create a DOM document
    $dom = new DOMDocument();
    @$dom->loadHTML($html); // Suppress errors for invalid HTML
    $xpath = new DOMXPath($dom);
    
    // 1. Analyze Title Tag
    $titleTags = $xpath->query('//title');
    $titleText = '';
    
    if ($titleTags->length > 0) {
        $titleText = trim($titleTags->item(0)->nodeValue);
        $titleLength = mb_strlen($titleText);
        
        // Score based on length (ideal: 50-60 characters)
        if ($titleLength >= 50 && $titleLength <= 60) {
            $titleTagsScore = 90;
        } elseif ($titleLength >= 40 && $titleLength <= 70) {
            $titleTagsScore = 75;
        } elseif ($titleLength > 0) {
            $titleTagsScore = 50;
        } else {
            $titleTagsScore = 0;
        }
        
        // Check if title contains the domain name or relevant keywords
        if (stripos($titleText, $domain) !== false || stripos($domain, explode(' ', $titleText)[0]) !== false) {
            $titleTagsScore += 10;
        }
        
        // Recommendations for title
        if ($titleLength == 0) {
            $recommendations[] = [
                'description' => 'Add a title tag to your page',
                'priority' => 'high'
            ];
        } elseif ($titleLength < 30) {
            $recommendations[] = [
                'description' => 'Your title tag is too short. Aim for 50-60 characters',
                'priority' => 'medium'
            ];
        } elseif ($titleLength > 70) {
            $recommendations[] = [
                'description' => 'Your title tag is too long. Keep it under 60 characters to avoid truncation in search results',
                'priority' => 'medium'
            ];
        }
    } else {
        $recommendations[] = [
            'description' => 'Add a title tag to your page',
            'priority' => 'high'
        ];
    }
    
    // 2. Analyze Meta Description
    $metaDesc = getMetaContent($html, 'description');
    $metaDescLength = mb_strlen($metaDesc);
    
    // Score based on length (ideal: 120-158 characters)
    if ($metaDescLength >= 120 && $metaDescLength <= 158) {
        $metaDescScore = 90;
    } elseif ($metaDescLength >= 80 && $metaDescLength <= 200) {
        $metaDescScore = 70;
    } elseif ($metaDescLength > 0) {
        $metaDescScore = 40;
    } else {
        $metaDescScore = 0;
    }
    
    // Recommendations for meta description
    if ($metaDescLength == 0) {
        $recommendations[] = [
            'description' => 'Add a meta description to your page',
            'priority' => 'high'
        ];
    } elseif ($metaDescLength < 80) {
        $recommendations[] = [
            'description' => 'Your meta description is too short. Aim for 120-158 characters',
            'priority' => 'medium'
        ];
    } elseif ($metaDescLength > 200) {
        $recommendations[] = [
            'description' => 'Your meta description is too long. Keep it under 158 characters to avoid truncation in search results',
            'priority' => 'medium'
        ];
    }
    
    // 3. Analyze Header Tags
    $h1Tags = $xpath->query('//h1');
    $h2Tags = $xpath->query('//h2');
    $h3Tags = $xpath->query('//h3');
    
    $h1Count = $h1Tags->length;
    $h2Count = $h2Tags->length;
    $h3Count = $h3Tags->length;
    
    // Score based on header structure
    if ($h1Count == 1 && $h2Count > 0) {
        $headerTagsScore = 90; // Ideal: 1 H1 and multiple H2s
    } elseif ($h1Count == 1) {
        $headerTagsScore = 75; // Good: At least has H1
    } elseif ($h1Count > 1) {
        $headerTagsScore = 60; // Multiple H1s is not ideal
    } elseif ($h2Count > 0) {
        $headerTagsScore = 50; // Has H2s but no H1
    } else {
        $headerTagsScore = 30; // Poor header structure
    }
    
    // Recommendations for headers
    if ($h1Count == 0) {
        $recommendations[] = [
            'description' => 'Add an H1 tag to your page for better SEO structure',
            'priority' => 'high'
        ];
    } elseif ($h1Count > 1) {
        $recommendations[] = [
            'description' => 'Use only one H1 tag per page for optimal SEO',
            'priority' => 'medium'
        ];
    }
    
    if ($h2Count == 0) {
        $recommendations[] = [
            'description' => 'Add H2 tags to structure your content better',
            'priority' => 'medium'
        ];
    }
    
    // 4. Analyze Content
    $paragraphs = $xpath->query('//p');
    $wordCount = 0;
    
    foreach ($paragraphs as $p) {
        $wordCount += str_word_count(strip_tags($p->nodeValue));
    }
    
    // Score based on content length
    if ($wordCount > 1000) {
        $contentScore = 90; // Excellent content length
    } elseif ($wordCount > 700) {
        $contentScore = 80; // Good content length
    } elseif ($wordCount > 400) {
        $contentScore = 60; // Acceptable content length
    } elseif ($wordCount > 200) {
        $contentScore = 40; // Poor content length
    } else {
        $contentScore = 20; // Very poor content length
    }
    
    // Recommendations for content
    if ($wordCount < 300) {
        $recommendations[] = [
            'description' => 'Add more content to your page. Aim for at least 700 words for better SEO performance',
            'priority' => 'high'
        ];
    } elseif ($wordCount < 700) {
        $recommendations[] = [
            'description' => 'Consider expanding your content to reach at least 700 words for better SEO performance',
            'priority' => 'medium'
        ];
    }
    
    // 5. Analyze Images
    $images = $xpath->query('//img');
    $imagesWithAlt = 0;
    
    foreach ($images as $img) {
        if ($img->hasAttribute('alt') && !empty($img->getAttribute('alt'))) {
            $imagesWithAlt++;
        }
    }
    
    // Score based on image alt tags
    if ($images->length > 0) {
        $imageOptScore = ($imagesWithAlt / $images->length) * 100;
    } else {
        $imageOptScore = 50; // No images, neutral score
    }
    
    // Recommendations for images
    if ($images->length > 0 && $imagesWithAlt < $images->length) {
        $recommendations[] = [
            'description' => 'Add alt text to all images for better accessibility and SEO',
            'priority' => $imageOptScore < 50 ? 'high' : 'medium'
        ];
    }
    
    // 6. Analyze Internal Linking
    $internalLinks = $xpath->query('//a[starts-with(@href, "/") or starts-with(@href, "' . $url . '") or starts-with(@href, "http://' . $domain . '") or starts-with(@href, "https://' . $domain . '")]');
    
    // Score based on internal linking
    if ($internalLinks->length > 10) {
        $internalLinkingScore = 90; // Excellent internal linking
    } elseif ($internalLinks->length > 5) {
        $internalLinkingScore = 75; // Good internal linking
    } elseif ($internalLinks->length > 0) {
        $internalLinkingScore = 50; // Some internal linking
    } else {
        $internalLinkingScore = 30; // Poor internal linking
    }
    
    // Recommendations for internal linking
    if ($internalLinks->length < 3) {
        $recommendations[] = [
            'description' => 'Add more internal links to improve site structure and SEO',
            'priority' => 'medium'
        ];
    }
    
    // 7. Analyze URL Structure
    $urlParts = parse_url($url);
    $path = isset($urlParts['path']) ? $urlParts['path'] : '';
    
    // Score based on URL structure
    if (empty($path) || $path == '/') {
        $urlStructureScore = 90; // Homepage is good
    } elseif (substr_count($path, '/') <= 2 && !preg_match('/[^a-z0-9\-\/]/', $path)) {
        $urlStructureScore = 85; // Clean, short URL
    } elseif (!preg_match('/[^a-z0-9\-\_\/\.]/', $path)) {
        $urlStructureScore = 70; // Clean URL but could be shorter
    } else {
        $urlStructureScore = 50; // URL could be improved
    }
    
    // Check for query parameters
    if (isset($urlParts['query'])) {
        $urlStructureScore -= 20;
        $recommendations[] = [
            'description' => 'Remove query parameters from URLs for better SEO',
            'priority' => 'low'
        ];
    }
    
    // Try to use SEMrush API for additional insights
    try {
        $semrushData = callSemrushApi('url_organic', [
            'url' => $url,
            'database' => 'us'
        ]);
        
        if ($semrushData && !empty($semrushData)) {
            // Adjust scores based on SEMrush data if available
            // This is a simplified example; in reality, you would use more sophisticated logic
            
            $semrushKeywordCount = count($semrushData);
            
            if ($semrushKeywordCount > 10) {
                $contentScore = max($contentScore, 85);
            } elseif ($semrushKeywordCount > 5) {
                $contentScore = max($contentScore, 75);
            } elseif ($semrushKeywordCount > 0) {
                $contentScore = max($contentScore, 65);
            }
        }
    } catch (Exception $e) {
        error_log("SEMrush API error: " . $e->getMessage());
        // Continue without SEMrush data
    }
    
    return [
        'title_tags_score' => $titleTagsScore,
        'meta_desc_score' => $metaDescScore,
        'header_tags_score' => $headerTagsScore,
        'content_score' => $contentScore,
        'image_optimization_score' => $imageOptScore,
        'internal_linking_score' => $internalLinkingScore,
        'url_structure_score' => $urlStructureScore,
        'recommendations' => $recommendations
    ];
}

// Simulate API call time
sleep(1);

// Get on-page SEO analysis
$onPageSEOData = analyzeOnPageSEO($url, $domain);

// Return success response
echo json_encode([
    'success' => true,
    'data' => $onPageSEOData
]);
