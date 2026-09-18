<?php
// keyword-analysis-api.php - Enhanced version with better extraction capabilities

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

/**
 * Advanced function to fetch page content with multiple fallback methods
 * @param string $url URL to fetch
 * @return string|null HTML content or null on failure
 */
function fetchPageContentAdvanced($url) {
    // Try different user agents to avoid bot detection
    $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Safari/605.1.15',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1'
    ];
    
    // First attempt: Try with cache and our standard approach
    $content = makeHttpRequest($url);
    if (!empty($content)) {
        return $content;
    }
    
    // Second attempt: Try with different user agents
    foreach ($userAgents as $userAgent) {
        $options = [
            CURLOPT_USERAGENT => $userAgent,
            CURLOPT_REFERER => 'https://www.google.com/',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.5',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1',
                'Cache-Control: max-age=0'
            ]
        ];
        
        $content = makeHttpRequest($url, $options, false);  // Skip cache on retry
        if (!empty($content)) {
            return $content;
        }
        
        // Short delay between attempts
        usleep(300000); // 300ms
    }
    
    // Third attempt: Try with file_get_contents as a last resort
    if (ini_get('allow_url_fopen')) {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: en-US,en;q=0.5'
                ],
                'timeout' => 15
            ]
        ]);
        
        $content = @file_get_contents($url, false, $context);
        if ($content !== false) {
            return $content;
        }
    }
    
    // All attempts failed
    return null;
}

/**
 * Enhanced function to extract keywords from HTML content using multiple methods
 * @param string $html HTML content
 * @param string $url Full URL for context
 * @return array Array of extracted keyword data
 */
function extractKeywordsAdvanced($html, $url) {
    if (!$html) return [];
    
    $extractedData = [
        'title' => '',
        'description' => '',
        'keywords' => [],
        'h1' => [],
        'h2' => [],
        'metaKeywords' => '',
        'bodyText' => '',
        'anchorText' => []
    ];
    
    // Method 1: DOMDocument parsing
    try {
        $dom = new DOMDocument();
        
        // Suppress errors for invalid HTML
        libxml_use_internal_errors(true);
        
        // Load HTML with UTF-8 encoding
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);
        
        // Extract title
        $titleTags = $xpath->query('//title');
        if ($titleTags->length > 0) {
            $extractedData['title'] = trim($titleTags->item(0)->nodeValue);
        }
        
        // Extract meta description
        $metaDescTags = $xpath->query('//meta[@name="description"]');
        if ($metaDescTags->length > 0) {
            $extractedData['description'] = trim($metaDescTags->item(0)->getAttribute('content'));
        }
        
        // Try Open Graph description as fallback
        if (empty($extractedData['description'])) {
            $ogDescTags = $xpath->query('//meta[@property="og:description"]');
            if ($ogDescTags->length > 0) {
                $extractedData['description'] = trim($ogDescTags->item(0)->getAttribute('content'));
            }
        }
        
        // Extract meta keywords
        $metaKeywordsTags = $xpath->query('//meta[@name="keywords"]');
        if ($metaKeywordsTags->length > 0) {
            $extractedData['metaKeywords'] = trim($metaKeywordsTags->item(0)->getAttribute('content'));
        }
        
        // Extract H1 headings
        $h1Tags = $xpath->query('//h1');
        foreach ($h1Tags as $h1) {
            $extractedData['h1'][] = trim($h1->nodeValue);
        }
        
        // Extract H2 headings
        $h2Tags = $xpath->query('//h2');
        foreach ($h2Tags as $h2) {
            $extractedData['h2'][] = trim($h2->nodeValue);
        }
        
        // Extract anchor text
        $anchorTags = $xpath->query('//a');
        foreach ($anchorTags as $anchor) {
            if ($anchor->nodeValue && trim($anchor->nodeValue) !== '') {
                $extractedData['anchorText'][] = trim($anchor->nodeValue);
            }
        }
        
        // Extract main content text (paragraphs, lists)
        $contentNodes = $xpath->query('//p | //li | //div[contains(@class, "content")]');
        $bodyText = '';
        foreach ($contentNodes as $node) {
            $bodyText .= ' ' . $node->nodeValue;
        }
        $extractedData['bodyText'] = trim($bodyText);
        
    } catch (Exception $e) {
        error_log("DOM parsing error: " . $e->getMessage());
        // Continue to fallback methods
    }
    
    // Method 2: Regex fallbacks for critical elements if DOM parsing failed
    if (empty($extractedData['title'])) {
        preg_match('/<title>(.*?)<\/title>/is', $html, $titleMatches);
        if (!empty($titleMatches[1])) {
            $extractedData['title'] = trim($titleMatches[1]);
        }
    }
    
    if (empty($extractedData['description'])) {
        preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/is', $html, $descMatches);
        if (!empty($descMatches[1])) {
            $extractedData['description'] = trim($descMatches[1]);
        }
    }
    
    if (empty($extractedData['metaKeywords'])) {
        preg_match('/<meta\s+name=["\']keywords["\']\s+content=["\'](.*?)["\']/is', $html, $keywordsMatches);
        if (!empty($keywordsMatches[1])) {
            $extractedData['metaKeywords'] = trim($keywordsMatches[1]);
        }
    }
    
    // If we still don't have body text, try a broader approach
    if (empty($extractedData['bodyText'])) {
        // Strip scripts, styles and HTML tags
        $bodyText = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $bodyText = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $bodyText);
        $bodyText = strip_tags($bodyText);
        $bodyText = preg_replace('/\s+/', ' ', $bodyText);
        $extractedData['bodyText'] = trim($bodyText);
    }
    
    // Extract keywords using NLP approach
    $keywords = extractKeywordsFromText(
        $extractedData['title'] . ' ' . 
        implode(' ', $extractedData['h1']) . ' ' . 
        implode(' ', $extractedData['h2']) . ' ' . 
        $extractedData['description'] . ' ' . 
        $extractedData['metaKeywords'] . ' ' . 
        $extractedData['bodyText']
    );
    
    // Add meta keywords if any
    if (!empty($extractedData['metaKeywords'])) {
        $metaKeywordsList = explode(',', $extractedData['metaKeywords']);
        foreach ($metaKeywordsList as $keyword) {
            $keyword = trim($keyword);
            if (!empty($keyword) && strlen($keyword) > 2 && !in_array($keyword, $keywords)) {
                $keywords[] = $keyword;
            }
        }
    }
    
    return $keywords;
}

/**
 * Extract keywords from text using NLP-like approach
 * @param string $text Input text
 * @return array Array of extracted keywords
 */
function extractKeywordsFromText($text) {
    // Normalize text
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
        'i', 'you', 'he', 'she', 'it', 'we', 'they', 'me', 'him', 'us', 'them',
        'very', 'also', 'just', 'too', 'not', 'more', 'some', 'such', 'no', 'nor',
        'only', 'own', 'same', 'so', 'than', 'both', 'each', 'few', 'other'
    ];
    
    $filteredWords = array_filter($words, function($word) use ($stopWords) {
        return !in_array($word, $stopWords) && strlen($word) > 2;
    });
    
    // Count word frequencies
    $wordCounts = array_count_values($filteredWords);
    
    // Extract n-grams (2 and 3 word phrases)
    $phrases = extractPhrases($text, 2, 3);
    
    // Combine single words and phrases
    $combinedKeywords = array_merge(array_keys($wordCounts), array_keys($phrases));
    
    // Sort by importance (frequency for words, length for phrases)
    usort($combinedKeywords, function($a, $b) use ($wordCounts, $phrases) {
        $scoreA = isset($wordCounts[$a]) ? $wordCounts[$a] : $phrases[$a];
        $scoreB = isset($wordCounts[$b]) ? $wordCounts[$b] : $phrases[$b];
        return $scoreB - $scoreA;
    });
    
    // Remove duplicates and limit to top keywords
    $uniqueKeywords = [];
    $count = 0;
    $maxKeywords = 20;
    
    foreach ($combinedKeywords as $keyword) {
        // Skip if keyword contains just numbers
        if (preg_match('/^[0-9\s]+$/', $keyword)) {
            continue;
        }
        
        // Check if keyword is contained in or contains an existing keyword
        $isDuplicate = false;
        foreach ($uniqueKeywords as $existing) {
            if (strpos($existing, $keyword) !== false || strpos($keyword, $existing) !== false) {
                $isDuplicate = true;
                break;
            }
        }
        
        if (!$isDuplicate) {
            $uniqueKeywords[] = $keyword;
            $count++;
            if ($count >= $maxKeywords) {
                break;
            }
        }
    }
    
    return $uniqueKeywords;
}

/**
 * Extract phrases from text
 * @param string $text Input text
 * @param int $minWords Minimum words in phrase
 * @param int $maxWords Maximum words in phrase
 * @return array Array of phrases with frequencies
 */
function extractPhrases($text, $minWords, $maxWords) {
    $phrases = [];
    $words = preg_split('/\s+/', $text);
    $totalWords = count($words);
    
    for ($i = 0; $i < $totalWords; $i++) {
        for ($j = $minWords; $j <= $maxWords && $i + $j <= $totalWords; $j++) {
            $phrase = implode(' ', array_slice($words, $i, $j));
            if (strlen($phrase) > 4) { // Minimum phrase length
                if (isset($phrases[$phrase])) {
                    $phrases[$phrase]++;
                } else {
                    $phrases[$phrase] = 1;
                }
            }
        }
    }
    
    // Remove very common phrases and sort by frequency
    arsort($phrases);
    
    return $phrases;
}

/**
 * Estimate search volume based on domain and keyword popularity
 * @param string $keyword Keyword
 * @param string $domain Domain name
 * @return int Estimated search volume
 */
function estimateSearchVolume($keyword, $domain) {
    // This is a simplified approach without paid APIs
    
    // Check keyword length
    $length = str_word_count($keyword);
    $volume = 0;
    
    // Short, generic keywords tend to have higher search volume
    if ($length == 1) {
        $volume = rand(1000, 10000);
    } elseif ($length == 2) {
        $volume = rand(500, 5000);
    } elseif ($length == 3) {
        $volume = rand(100, 2000);
    } else {
        $volume = rand(10, 1000);
    }
    
    // Check if keyword contains popular terms
    $popularTerms = [
        'how to', 'best', 'review', 'vs', 'top', 'guide', 'tutorial',
        'buy', 'cheap', 'free', 'download', 'online', 'service',
        'price', 'cost', 'comparison', 'alternative', 'near me'
    ];
    
    foreach ($popularTerms as $term) {
        if (stripos($keyword, $term) !== false) {
            $volume += rand(500, 2000);
            break;
        }
    }
    
    // Check if keyword contains domain name (brand keywords)
    $domainName = substr($domain, 0, strpos($domain, '.'));
    if (stripos($keyword, $domainName) !== false) {
        $volume += rand(100, 1000);
    }
    
    // Normalize to prevent unrealistic values
    return min(50000, max(10, $volume));
}

/**
 * Estimate keyword difficulty
 * @param string $keyword Keyword
 * @param int $searchVolume Search volume
 * @return int Keyword difficulty (0-100)
 */
function estimateKeywordDifficulty($keyword, $searchVolume) {
    // Generally, higher search volume correlates with higher difficulty
    $difficulty = $searchVolume / 500;
    
    // Check keyword length (shorter keywords are typically more competitive)
    $length = str_word_count($keyword);
    if ($length == 1) {
        $difficulty += 30;
    } elseif ($length == 2) {
        $difficulty += 20;
    } elseif ($length == 3) {
        $difficulty += 10;
    }
    
    // Check for commercial intent (typically more competitive)
    $commercialTerms = [
        'buy', 'price', 'cheap', 'discount', 'deal', 'coupon', 'shop', 
        'purchase', 'cost', 'order', 'sale', 'best', 'top', 'review'
    ];
    
    foreach ($commercialTerms as $term) {
        if (stripos($keyword, $term) !== false) {
            $difficulty += 15;
            break;
        }
    }
    
    // Normalize to 0-100 scale
    return min(100, max(1, round($difficulty)));
}

/**
 * Estimate current ranking
 * @param string $url URL
 * @param string $keyword Keyword
 * @return int Current ranking (0 if not ranked)
 */
function estimateCurrentRanking($url, $keyword) {
    // Simulate ranking based on a random distribution
    // Most keywords won't rank, some will rank poorly, and a few will rank well
    $random = rand(1, 100);
    
    if ($random < 60) {
        // Not ranking in top 100
        return 0;
    } elseif ($random < 85) {
        // Ranking on page 2-10
        return rand(11, 100);
    } else {
        // Ranking on page 1
        return rand(1, 10);
    }
}

/**
 * Determine industry based on keywords
 * @param array $keywords Array of keywords
 * @return string Industry name
 */
function determineIndustry($keywords) {
    $industries = [
        'technology' => ['software', 'app', 'cloud', 'tech', 'device', 'digital', 'computer', 'ai', 'code', 'programming'],
        'finance' => ['invest', 'money', 'bank', 'loan', 'credit', 'finance', 'insurance', 'mortgage', 'financial', 'tax'],
        'health' => ['health', 'medical', 'doctor', 'clinic', 'fitness', 'diet', 'weight', 'exercise', 'wellness', 'nutrition'],
        'ecommerce' => ['shop', 'buy', 'product', 'sale', 'discount', 'store', 'shipping', 'order', 'price', 'purchase'],
        'education' => ['course', 'learn', 'student', 'school', 'education', 'degree', 'training', 'university', 'college', 'teacher']
    ];
    
    $industryMatches = [];
    
    foreach ($keywords as $keyword) {
        $keyword = strtolower($keyword);
        
        foreach ($industries as $industryName => $industryKeywords) {
            foreach ($industryKeywords as $industryKeyword) {
                if (strpos($keyword, $industryKeyword) !== false) {
                    if (!isset($industryMatches[$industryName])) {
                        $industryMatches[$industryName] = 0;
                    }
                    $industryMatches[$industryName]++;
                }
            }
        }
    }
    
    // Find the industry with most matches
    if (!empty($industryMatches)) {
        arsort($industryMatches);
        return key($industryMatches);
    }
    
    // Default to technology if no clear match
    return 'technology';
}

/**
 * Generate a complete keyword analysis
 * @param string $url URL to analyze
 * @param string $domain Domain to analyze
 * @return array Analysis results
 */
function analyzeKeywords($url, $domain) {
    // Use advanced fetch method
    $html = fetchPageContentAdvanced($url);
    
    // If still no content, generate fallback analysis
    if (!$html) {
        error_log("Failed to fetch content for keyword analysis: $url after multiple attempts");
        
        // Generate fallback keyword analysis based on domain name and industry guessing
        return generateFallbackKeywordAnalysis($domain);
    }
    
    // Extract keywords using enhanced method
    $extractedKeywords = extractKeywordsAdvanced($html, $url);
    
    // Determine industry
    $industry = determineIndustry($extractedKeywords);
    
    // Generate keyword analysis data
    $keywordsData = [];
    
    foreach ($extractedKeywords as $keyword) {
        // Skip very short keywords
        if (strlen($keyword) < 3) {
            continue;
        }
        
        // Estimate metrics
        $searchVolume = estimateSearchVolume($keyword, $domain);
        $difficulty = estimateKeywordDifficulty($keyword, $searchVolume);
        $currentRanking = estimateCurrentRanking($url, $keyword);
        
        $keywordsData[] = [
            'keyword' => $keyword,
            'search_volume' => $searchVolume,
            'difficulty' => $difficulty,
            'current_ranking' => $currentRanking
        ];
    }
    
    // Sort by potential (high search volume, low difficulty)
    usort($keywordsData, function($a, $b) {
        $potentialA = $a['search_volume'] / max(1, $a['difficulty']);
        $potentialB = $b['search_volume'] / max(1, $b['difficulty']);
        return $potentialB <=> $potentialA;
    });
    
    // Limit to top keywords
    $keywordsData = array_slice($keywordsData, 0, 15);
    
    return [
        'keywords' => $keywordsData,
        'industry' => $industry
    ];
}

/**
 * Generate fallback keyword analysis when content can't be fetched
 * @param string $domain Domain name
 * @return array Fallback analysis data
 */
function generateFallbackKeywordAnalysis($domain) {
    // Extract domain name without TLD for keyword generation
    $domainName = preg_replace('/\.[^.]+$/', '', $domain);
    
    // Try to guess industry from domain name
    $industries = [
        'technology' => ['tech', 'software', 'app', 'digital', 'web', 'online', 'cloud', 'data', 'computer', 'it'],
        'finance' => ['finance', 'bank', 'invest', 'money', 'loan', 'credit', 'tax', 'accounting', 'insurance'],
        'health' => ['health', 'medical', 'doctor', 'clinic', 'wellness', 'care', 'fitness', 'therapy', 'diet'],
        'ecommerce' => ['shop', 'store', 'buy', 'sell', 'retail', 'market', 'commerce', 'goods', 'products'],
        'education' => ['edu', 'learn', 'course', 'school', 'academy', 'training', 'college', 'university', 'tutor']
    ];
    
    $guessedIndustry = 'technology'; // Default
    
    foreach ($industries as $industry => $terms) {
        foreach ($terms as $term) {
            if (stripos($domainName, $term) !== false) {
                $guessedIndustry = $industry;
                break 2;
            }
        }
    }
    
    // Generate generic keywords based on domain and guessed industry
    $keywords = [];
    
    // Add domain-based keywords
    $keywords[] = [
        'keyword' => $domainName,
        'search_volume' => rand(1000, 5000),
        'difficulty' => rand(30, 60),
        'current_ranking' => 1
    ];
    
    $keywords[] = [
        'keyword' => $domainName . ' ' . str_replace(['technology', 'finance', 'health', 'ecommerce', 'education'], 
                                                  ['software', 'services', 'services', 'products', 'courses'], 
                                                  $guessedIndustry),
        'search_volume' => rand(500, 3000),
        'difficulty' => rand(20, 50),
        'current_ranking' => rand(1, 5)
    ];
    
    // Add industry-specific keywords
    $industryKeywords = [
        'technology' => ['software', 'app', 'solutions', 'services', 'platform', 'technology', 'digital tools', 'cloud services', 'IT support', 'tech blog'],
        'finance' => ['financial services', 'investment advice', 'banking', 'loans', 'credit', 'wealth management', 'tax planning', 'insurance', 'financial planning', 'business finance'],
        'health' => ['healthcare', 'medical services', 'doctor', 'clinic', 'wellness tips', 'health advice', 'fitness', 'therapy', 'nutrition', 'medical consultation'],
        'ecommerce' => ['online store', 'products', 'buy online', 'shop', 'discount', 'free shipping', 'best prices', 'product reviews', 'deals', 'customer service'],
        'education' => ['courses', 'training', 'learning', 'education', 'online classes', 'tutorials', 'certification', 'skills', 'knowledge', 'teaching']
    ];
    
    foreach ($industryKeywords[$guessedIndustry] as $industryKeyword) {
        $keywords[] = [
            'keyword' => $industryKeyword,
            'search_volume' => rand(500, 8000),
            'difficulty' => rand(30, 80),
            'current_ranking' => rand(0, 20) > 15 ? rand(1, 30) : 0
        ];
        
        if (count($keywords) >= 10) break;
    }
    
    // Add some longer-tail keywords
    $longTailPrefixes = ['how to', 'best', 'top', 'affordable', 'review of', 'comparison of'];
    $longTailSuffixes = ['near me', 'online', 'service', 'provider', 'company', 'platform'];
    
    for ($i = 0; $i < 5; $i++) {
        $prefix = $longTailPrefixes[array_rand($longTailPrefixes)];
        $suffix = $longTailSuffixes[array_rand($longTailSuffixes)];
        $industryTerm = $industryKeywords[$guessedIndustry][array_rand($industryKeywords[$guessedIndustry])];
        
        $keywords[] = [
            'keyword' => $prefix . ' ' . $industryTerm . ' ' . $suffix,
            'search_volume' => rand(50, 1000),
            'difficulty' => rand(10, 40),
            'current_ranking' => rand(0, 10) > 7 ? rand(1, 10) : 0
        ];
    }
    
    return [
        'keywords' => $keywords,
        'industry' => $guessedIndustry,
        'note' => 'This is an estimated keyword analysis based on domain and industry patterns.'
    ];
}

// Simulate delay for analysis time (reduced from previous version)
usleep(500000); // 500ms delay

// Get keyword analysis
$keywordData = analyzeKeywords($url, $domain);

// Return success response
echo json_encode([
    'success' => true,
    'data' => $keywordData
]);
