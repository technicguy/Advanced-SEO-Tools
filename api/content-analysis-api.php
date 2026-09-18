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

// Function to fetch page content
function fetchPageContent($url) {
    return makeHttpRequest($url);
}

// Function to extract text content from HTML
function extractTextContent($html) {
    if (!$html) return '';
    
    // Create a DOM document
    $dom = new DOMDocument();
    @$dom->loadHTML($html); // Suppress errors for invalid HTML
    $xpath = new DOMXPath($dom);
    
    // Extract main content (paragraphs, lists, headers)
    $contentNodes = $xpath->query('//p | //li | //h1 | //h2 | //h3 | //h4 | //h5 | //h6');
    $content = '';
    
    foreach ($contentNodes as $node) {
        $content .= trim($node->nodeValue) . "\n";
    }
    
    return $content;
}

// Function to extract internal links
function extractInternalLinks($html, $domain) {
    if (!$html) return [];
    
    // Create a DOM document
    $dom = new DOMDocument();
    @$dom->loadHTML($html); // Suppress errors for invalid HTML
    $xpath = new DOMXPath($dom);
    
    // Find all internal links
    $internalLinks = [];
    $linkNodes = $xpath->query('//a[@href]');
    
    foreach ($linkNodes as $link) {
        $href = $link->getAttribute('href');
        
        // Check if it's an internal link
        if (strpos($href, 'http') === 0) {
            // Absolute URL - check if it's for the same domain
            $linkDomain = parse_url($href, PHP_URL_HOST);
            if ($linkDomain === $domain || $linkDomain === 'www.' . $domain) {
                $internalLinks[] = $href;
            }
        } elseif (strpos($href, '/') === 0) {
            // Relative URL - it's internal
            $internalLinks[] = $href;
        }
    }
    
    return $internalLinks;
}

// Function to calculate readability score (Flesch-Kincaid)
function calculateReadabilityScore($text) {
    if (empty($text)) return 0;
    
    // Count sentences
    $sentences = preg_split('/[.!?]+/', $text);
    $sentenceCount = count(array_filter($sentences));
    
    if ($sentenceCount == 0) return 0;
    
    // Count words
    $words = preg_split('/\s+/', $text);
    $wordCount = count(array_filter($words));
    
    if ($wordCount == 0) return 0;
    
    // Count syllables (simplified approach)
    $syllableCount = 0;
    foreach ($words as $word) {
        $word = strtolower($word);
        $word = preg_replace('/[^a-z]/', '', $word);
        
        if (empty($word)) continue;
        
        // Count vowel groups as syllables
        $syllableCount += max(1, preg_match_all('/[aeiouy]+/', $word, $matches));
    }
    
    // Calculate Flesch-Kincaid Reading Ease
    $score = 206.835 - (1.015 * ($wordCount / $sentenceCount)) - (84.6 * ($syllableCount / $wordCount));
    
    // Normalize to 0-100 scale
    $score = max(0, min(100, $score));
    
    return round($score);
}

// Function to analyze keyword density
function analyzeKeywordDensity($text, $keyword) {
    $text = strtolower($text);
    $keyword = strtolower($keyword);
    
    $wordCount = str_word_count($text);
    if ($wordCount == 0) return 0;
    
    $keywordCount = substr_count($text, $keyword);
    $density = ($keywordCount / $wordCount) * 100;
    
    return round($density, 2);
}

// Function to extract top keywords from content
function extractTopKeywords($text, $count = 5) {
    // Clean and normalize text
    $text = strtolower($text);
    $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text); // Remove non-alphanumeric characters
    $text = preg_replace('/\s+/', ' ', $text); // Normalize whitespace
    
    // Split into words
    $words = explode(' ', $text);
    
    // Remove common stop words
    $stopWords = [
        'a', 'an', 'the', 'and', 'or', 'but', 'if', 'then', 'else', 'when', 
        'at', 'from', 'by', 'on', 'off', 'for', 'in', 'out', 'over', 'to', 
        'into', 'with', 'about', 'as', 'into', 'like', 'through', 'after', 
        'before', 'between', 'since', 'without', 'under', 'above', 'below', 
        'this', 'that', 'these', 'those', 'there', 'here', 'where', 'how', 
        'why', 'who', 'whom', 'which', 'what', 'whatever', 'whoever', 'whenever',
        'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had',
        'do', 'does', 'did', 'can', 'could', 'will', 'would', 'shall', 'should',
        'may', 'might', 'must', 'my', 'your', 'our', 'their', 'his', 'her', 'its',
        'i', 'you', 'he', 'she', 'it', 'we', 'they', 'me', 'him', 'us', 'them'
    ];
    
    $words = array_filter($words, function($word) use ($stopWords) {
        return !in_array($word, $stopWords) && strlen($word) > 2;
    });
    
    // Count word frequencies
    $wordCounts = array_count_values($words);
    
    // Sort by frequency
    arsort($wordCounts);
    
    // Return top keywords
    return array_slice(array_keys($wordCounts), 0, $count);
}

// Function to check for thin content
function isThinContent($wordCount) {
    // Generally, content with fewer than 300 words is considered "thin"
    return $wordCount < 300;
}

// Function to estimate duplicate content risk
function estimateDuplicateContentRisk($content) {
    // In a real implementation, you would check against a database or use an API
    // For this example, we'll use a simple heuristic
    
    // Very short content has higher risk of being duplicate
    $wordCount = str_word_count($content);
    if ($wordCount < 100) {
        return 'high';
    } elseif ($wordCount < 300) {
        return 'medium';
    } else {
        return 'low';
    }
}

// Function to check image optimization
function checkImageOptimization($html) {
    if (!$html) return ['count' => 0, 'with_alt' => 0, 'optimized_pct' => 0];
    
    // Create a DOM document
    $dom = new DOMDocument();
    @$dom->loadHTML($html); // Suppress errors for invalid HTML
    $xpath = new DOMXPath($dom);
    
    // Find all images
    $images = $xpath->query('//img');
    $totalImages = $images->length;
    $imagesWithAlt = 0;
    
    foreach ($images as $img) {
        if ($img->hasAttribute('alt') && !empty($img->getAttribute('alt'))) {
            $imagesWithAlt++;
        }
    }
    
    $optimizedPct = $totalImages > 0 ? round(($imagesWithAlt / $totalImages) * 100) : 0;
    
    return [
        'count' => $totalImages,
        'with_alt' => $imagesWithAlt,
        'optimized_pct' => $optimizedPct
    ];
}

// Function to analyze content without paid APIs
function analyzeContent($url) {
    // Extract domain name from URL
    $domain = parse_url($url, PHP_URL_HOST);
    if (substr($domain, 0, 4) === 'www.') {
        $domain = substr($domain, 4);
    }
    
    // Try to fetch HTML content with multiple attempts
    $html = null;
    $maxAttempts = 3;
    
    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        $html = fetchPageContent($url);
        if ($html) break;
        
        // Wait between attempts
        if ($attempt < $maxAttempts) {
            sleep(2);
        }
    }
    
    // If still failed after multiple attempts, try an alternative approach
    if (!$html) {
        // Try fetching with different user agent
        $alternativeOptions = [
            CURLOPT_USERAGENT => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1'
        ];
        $html = makeHttpRequest($url, $alternativeOptions);
    }
	
    // If still no content, provide minimal analysis with clear error
    if (!$html) {
        error_log("Failed to fetch content for URL: $url after multiple attempts");
        return [
            'avg_word_count' => 0,
            'avg_readability' => 0,
            'total_pages' => 1,
            'thin_content_pages' => 1,
            'error' => 'Could not fetch page content. The site may be blocking automated requests.',
            'suggestions' => [
                [
                    'description' => 'Content could not be analyzed automatically. Consider the following general recommendations:',
                    'priority' => 'high'
                ],
                [
                    'description' => 'Ensure content is at least 700-1000 words for key pages',
                    'priority' => 'medium'
                ],
                [
                    'description' => 'Use headers (H1, H2, H3) to structure content',
                    'priority' => 'medium'
                ],
                [
                    'description' => 'Include relevant keywords naturally throughout the content',
                    'priority' => 'high'
                ],
                [
                    'description' => 'Add alt text to all images',
                    'priority' => 'medium'
                ],
                [
                    'description' => 'Include internal links to other relevant pages',
                    'priority' => 'medium'
                ]
            ]
        ];
    }	
	
    
    // Extract content from the main page
    $content = extractTextContent($html);
    $wordCount = str_word_count($content);
    $readabilityScore = calculateReadabilityScore($content);
    $hasThinContent = isThinContent($wordCount);
    $duplicateContentRisk = estimateDuplicateContentRisk($content);
    $topKeywords = extractTopKeywords($content);
    $imageOptimization = checkImageOptimization($html);	
    
    // Extract internal links to potentially analyze more pages
    $internalLinks = extractInternalLinks($html, $domain);
    $totalPages = count($internalLinks) + 1; // Add 1 for current page
    $thinContentPages = $hasThinContent ? 1 : 0;
    
    // Initialize the content data for the main page
    $contentData = [
        'main_page' => [
            'url' => $url,
            'word_count' => $wordCount,
            'readability_score' => $readabilityScore,
            'has_thin_content' => $hasThinContent,
            'duplicate_content_risk' => $duplicateContentRisk,
            'top_keywords' => $topKeywords,
            'image_optimization' => $imageOptimization
        ],
        'analyzed_pages' => 1
    ];
    
    // Limit the number of additional pages to analyze
    $maxPagesToAnalyze = min(5, count($internalLinks));
    $analyzedInternalPages = 0;
    
    // Analyze a sample of internal pages
    for ($i = 0; $i < $maxPagesToAnalyze; $i++) {
        // Get a random internal link
        $randomLink = $internalLinks[array_rand($internalLinks)];
        
        // Convert relative URL to absolute if needed
        if (strpos($randomLink, 'http') !== 0) {
            $base = parse_url($url);
            $randomLink = $base['scheme'] . '://' . $base['host'] . ($randomLink[0] === '/' ? $randomLink : '/' . $randomLink);
        }
        
        // Fetch and analyze the internal page
        $internalHtml = fetchPageContent($randomLink);
        
        if ($internalHtml) {
            $internalContent = extractTextContent($internalHtml);
            $internalWordCount = str_word_count($internalContent);
            $internalReadabilityScore = calculateReadabilityScore($internalContent);
            $hasInternalThinContent = isThinContent($internalWordCount);
            
            // Update running totals
            $wordCount += $internalWordCount;
            $readabilityScore += $internalReadabilityScore;
            if ($hasInternalThinContent) {
                $thinContentPages++;
            }
            
            $analyzedInternalPages++;
        }
        
        // Avoid overloading the server
        usleep(500000); // 0.5 second delay between requests
    }
    
    // Calculate averages
    $totalAnalyzedPages = $analyzedInternalPages + 1; // Main page + internal pages
    $avgWordCount = $totalAnalyzedPages > 0 ? round($wordCount / $totalAnalyzedPages) : 0;
    $avgReadability = $totalAnalyzedPages > 0 ? round($readabilityScore / $totalAnalyzedPages) : 0;
    
    // Generate content suggestions based on analysis
    $suggestions = [];
    
    if ($avgWordCount < 700) {
        $suggestions[] = [
            'description' => 'Increase content length for key pages (aim for 1000+ words)',
            'priority' => $avgWordCount < 500 ? 'high' : 'medium'
        ];
    }
    
    if ($avgReadability < 60) {
        $suggestions[] = [
            'description' => 'Improve content readability by using shorter sentences and simpler language',
            'priority' => $avgReadability < 40 ? 'high' : 'medium'
        ];
    }
    
    if ($thinContentPages > 0) {
        $suggestions[] = [
            'description' => 'Address thin content issues on ' . $thinContentPages . ' pages',
            'priority' => $thinContentPages > 3 ? 'high' : 'medium'
        ];
    }
    
    if ($duplicateContentRisk === 'high' || $duplicateContentRisk === 'medium') {
        $suggestions[] = [
            'description' => 'Risk of duplicate content detected. Create more unique content',
            'priority' => $duplicateContentRisk === 'high' ? 'high' : 'medium'
        ];
    }
    
    if ($imageOptimization['count'] > 0 && $imageOptimization['optimized_pct'] < 70) {
        $suggestions[] = [
            'description' => 'Add alt text to all images for better accessibility and SEO',
            'priority' => $imageOptimization['optimized_pct'] < 50 ? 'high' : 'medium'
        ];
    }
    
    // Add general content improvement suggestions
    $suggestions[] = [
        'description' => 'Include relevant internal links throughout content',
        'priority' => 'medium'
    ];
    
    $suggestions[] = [
        'description' => 'Add FAQ sections to address common user questions',
        'priority' => 'low'
    ];
    
    if ($avgWordCount > 700) {
        $suggestions[] = [
            'description' => 'Use more formatting elements (headers, lists, etc.) to improve readability',
            'priority' => 'medium'
        ];
    }
    
    return [
        'avg_word_count' => $avgWordCount,
        'avg_readability' => $avgReadability,
        'total_pages' => $totalPages,
        'thin_content_pages' => $thinContentPages,
        'main_page_data' => $contentData['main_page'],
        'analyzed_pages_count' => $totalAnalyzedPages,
        'suggestions' => $suggestions
    ];
}

// Simulate delay for analysis time
sleep(1);

// Get content analysis
$contentData = analyzeContent($url);

// Return success response
echo json_encode([
    'success' => true,
    'data' => $contentData
]);
