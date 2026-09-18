<?php
/**
 * Meta Tags Analyzer
 * 
 * Analyzes meta tags on a webpage for SEO optimization
 */

class MetaAnalyzer {
    private $url;
    private $dom;
    private $metaTags = [];
    private $scores = [];
    private $issues = [];
    private $recommendations = [];
    
    /**
     * Constructor
     * 
     * @param string $url The URL to analyze
     */
    public function __construct($url) {
        $this->url = $url;
    }
    
    /**
     * Run the meta tags analysis
     * 
     * @return array Analysis results
     */
    public function analyze() {
        if (!$this->fetchPage()) {
            return [
                'success' => false,
                'message' => 'Failed to fetch the page'
            ];
        }
        
        $this->extractMetaTags();
        $this->analyzeTitleTag();
        $this->analyzeMetaDescription();
        $this->analyzeMetaKeywords();
        $this->analyzeViewport();
        $this->analyzeRobots();
        $this->analyzeCanonical();
        $this->analyzeSocialTags();
        $this->calculateScores();
        
        return [
            'success' => true,
            'url' => $this->url,
            'meta_tags' => $this->metaTags,
            'scores' => $this->scores,
            'issues' => $this->issues,
            'recommendations' => $this->recommendations
        ];
    }
    
    /**
     * Fetch the page and load it into DOM
     * 
     * @return boolean Success status
     */
    private function fetchPage() {
        // Use the existing makeHttpRequest function from config.php if available
        if (function_exists('makeHttpRequest')) {
            $html = makeHttpRequest($this->url);
            if (!$html) {
                return false;
            }
        } else {
            // Fallback to direct curl if the function doesn't exist
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            $html = curl_exec($ch);
            
            if (curl_errno($ch)) {
                return false;
            }
            
            curl_close($ch);
            
            if (!$html) {
                return false;
            }
        }
        
        // Create a new DOM Document
        $this->dom = new DOMDocument();
        
        // Use libxml to handle errors during loading
        libxml_use_internal_errors(true);
        $this->dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();
        
        return true;
    }
    
    /**
     * Extract all meta tags from the page
     */
    private function extractMetaTags() {
        // Get the page title
        $titleTags = $this->dom->getElementsByTagName('title');
        if ($titleTags->length > 0) {
            $this->metaTags['title'] = trim($titleTags->item(0)->nodeValue);
            $this->metaTags['title_length'] = mb_strlen($this->metaTags['title']);
        } else {
            $this->metaTags['title'] = null;
            $this->metaTags['title_length'] = 0;
        }
        
        // Get the meta tags
        $metaNodes = $this->dom->getElementsByTagName('meta');
        foreach ($metaNodes as $meta) {
            $name = '';
            $content = '';
            
            // Check various attributes for name/property
            if ($meta->hasAttribute('name')) {
                $name = strtolower($meta->getAttribute('name'));
                $content = $meta->getAttribute('content');
            } elseif ($meta->hasAttribute('property')) {
                $name = strtolower($meta->getAttribute('property'));
                $content = $meta->getAttribute('content');
            } elseif ($meta->hasAttribute('http-equiv')) {
                $name = strtolower($meta->getAttribute('http-equiv'));
                $content = $meta->getAttribute('content');
            }
            
            if ($name && $content) {
                $this->metaTags[$name] = $content;
            }
        }
        
        // Get canonical link
        $linkNodes = $this->dom->getElementsByTagName('link');
        foreach ($linkNodes as $link) {
            if ($link->hasAttribute('rel') && strtolower($link->getAttribute('rel')) === 'canonical') {
                $this->metaTags['canonical'] = $link->getAttribute('href');
            }
        }
    }
    
    /**
     * Analyze the title tag
     */
    private function analyzeTitleTag() {
        $title = isset($this->metaTags['title']) ? $this->metaTags['title'] : '';
        $titleLength = isset($this->metaTags['title_length']) ? $this->metaTags['title_length'] : 0;
        
        // Add to meta tags collection if not already there
        if (!isset($this->metaTags['title'])) {
            $this->metaTags['title'] = $title;
            $this->metaTags['title_length'] = $titleLength;
        }
        
        // Check if title exists
        if (!$title) {
            $this->issues[] = [
                'type' => 'title',
                'severity' => 'high',
                'message' => 'Missing title tag'
            ];
            
            $this->recommendations[] = [
                'type' => 'title',
                'priority' => 'high',
                'message' => 'Add a descriptive title tag that includes your main keyword'
            ];
            
            return;
        }
        
        // Check title length (Google typically displays the first 50–60 characters)
        if ($titleLength < 30) {
            $this->issues[] = [
                'type' => 'title',
                'severity' => 'medium',
                'message' => 'Title tag is too short (' . $titleLength . ' characters)'
            ];
            
            $this->recommendations[] = [
                'type' => 'title',
                'priority' => 'medium',
                'message' => 'Increase title length to between 50-60 characters for better SEO'
            ];
        } elseif ($titleLength > 60) {
            $this->issues[] = [
                'type' => 'title',
                'severity' => 'low',
                'message' => 'Title tag is too long (' . $titleLength . ' characters) and may be truncated in search results'
            ];
            
            $this->recommendations[] = [
                'type' => 'title',
                'priority' => 'low',
                'message' => 'Reduce title length to no more than 60 characters to prevent truncation in search results'
            ];
        }
        
        // Check for keyword stuffing (simple check for repeated words)
        $words = preg_split('/\s+/', $title);
        $wordCount = [];
        
        foreach ($words as $word) {
            $word = strtolower(preg_replace('/[^\p{L}\p{N}]+/u', '', $word));
            if (strlen($word) > 2) { // Skip very short words
                if (!isset($wordCount[$word])) {
                    $wordCount[$word] = 1;
                } else {
                    $wordCount[$word]++;
                }
            }
        }
        
        foreach ($wordCount as $word => $count) {
            if ($count > 2 && strlen($word) > 3) {
                $this->issues[] = [
                    'type' => 'title',
                    'severity' => 'medium',
                    'message' => 'Possible keyword stuffing detected in title. The word "' . $word . '" appears ' . $count . ' times'
                ];
                
                $this->recommendations[] = [
                    'type' => 'title',
                    'priority' => 'medium',
                    'message' => 'Avoid repeating keywords in the title tag to prevent keyword stuffing'
                ];
                
                break;
            }
        }
        
        // Check if title starts with brand name (often better to end with brand)
        $domain = parse_url($this->url, PHP_URL_HOST);
        $domain = preg_replace('/^www\./', '', $domain);
        $domainParts = explode('.', $domain);
        $brandName = $domainParts[0];
        
        if (stripos($title, $brandName) === 0) {
            $this->recommendations[] = [
                'type' => 'title',
                'priority' => 'low',
                'message' => 'Consider moving your brand name to the end of the title for better keyword prominence'
            ];
        }
    }
    
    /**
     * Analyze the meta description
     */
    private function analyzeMetaDescription() {
        $description = isset($this->metaTags['description']) ? $this->metaTags['description'] : null;
        
        // Check if description exists
        if (!$description) {
            $this->issues[] = [
                'type' => 'description',
                'severity' => 'high',
                'message' => 'Missing meta description'
            ];
            
            $this->recommendations[] = [
                'type' => 'description',
                'priority' => 'high',
                'message' => 'Add a compelling meta description that includes your target keywords and a call to action'
            ];
            
            return;
        }
        
        // Store description length
        $descriptionLength = mb_strlen($description);
        $this->metaTags['description_length'] = $descriptionLength;
        
        // Check description length (Google typically displays around 155-160 characters)
        if ($descriptionLength < 100) {
            $this->issues[] = [
                'type' => 'description',
                'severity' => 'medium',
                'message' => 'Meta description is too short (' . $descriptionLength . ' characters)'
            ];
            
            $this->recommendations[] = [
                'type' => 'description',
                'priority' => 'medium',
                'message' => 'Increase description length to between 150-160 characters for better SEO and click-through rates'
            ];
        } elseif ($descriptionLength > 160) {
            $this->issues[] = [
                'type' => 'description',
                'severity' => 'low',
                'message' => 'Meta description is too long (' . $descriptionLength . ' characters) and may be truncated in search results'
            ];
            
            $this->recommendations[] = [
                'type' => 'description',
                'priority' => 'low',
                'message' => 'Reduce description length to no more than 160 characters to prevent truncation in search results'
            ];
        }
        
        // Check for call to action phrases
        $ctaPhrases = ['learn', 'discover', 'find out', 'get', 'read', 'explore', 'check', 'view', 'see', 'start', 'try'];
        $hasCallToAction = false;
        
        foreach ($ctaPhrases as $phrase) {
            if (stripos($description, $phrase) !== false) {
                $hasCallToAction = true;
                break;
            }
        }
        
        if (!$hasCallToAction) {
            $this->recommendations[] = [
                'type' => 'description',
                'priority' => 'medium',
                'message' => 'Add a call-to-action phrase in your meta description to improve click-through rates'
            ];
        }
    }
    
    /**
     * Analyze meta keywords tag (less important for modern SEO but still used by some search engines)
     */
    private function analyzeMetaKeywords() {
        $keywords = isset($this->metaTags['keywords']) ? $this->metaTags['keywords'] : null;
        
        // Not critical if missing, as it's less important for most search engines now
        if (!$keywords) {
            $this->issues[] = [
                'type' => 'keywords',
                'severity' => 'low',
                'message' => 'Missing meta keywords tag'
            ];
            
            $this->recommendations[] = [
                'type' => 'keywords',
                'priority' => 'low',
                'message' => 'While less important for Google, consider adding a meta keywords tag for other search engines'
            ];
            
            return;
        }
        
        // Check if there are too many keywords
        $keywordArray = explode(',', $keywords);
        if (count($keywordArray) > 10) {
            $this->issues[] = [
                'type' => 'keywords',
                'severity' => 'low',
                'message' => 'Too many keywords in meta keywords tag (' . count($keywordArray) . ' keywords)'
            ];
            
            $this->recommendations[] = [
                'type' => 'keywords',
                'priority' => 'low',
                'message' => 'Limit meta keywords to 5-10 highly relevant terms'
            ];
        }
    }
    
    /**
     * Analyze viewport meta tag for mobile optimization
     */
    private function analyzeViewport() {
        $viewport = isset($this->metaTags['viewport']) ? $this->metaTags['viewport'] : null;
        
        // Check if viewport exists
        if (!$viewport) {
            $this->issues[] = [
                'type' => 'viewport',
                'severity' => 'high',
                'message' => 'Missing viewport meta tag for mobile optimization'
            ];
            
            $this->recommendations[] = [
                'type' => 'viewport',
                'priority' => 'high',
                'message' => 'Add a viewport meta tag: <meta name="viewport" content="width=device-width, initial-scale=1.0">'
            ];
            
            return;
        }
        
        // Check if viewport has width=device-width and initial-scale
        if (stripos($viewport, 'width=device-width') === false) {
            $this->issues[] = [
                'type' => 'viewport',
                'severity' => 'medium',
                'message' => 'Viewport meta tag is missing width=device-width'
            ];
            
            $this->recommendations[] = [
                'type' => 'viewport',
                'priority' => 'medium',
                'message' => 'Update viewport meta tag to include width=device-width'
            ];
        }
        
        if (stripos($viewport, 'initial-scale=1') === false) {
            $this->issues[] = [
                'type' => 'viewport',
                'severity' => 'low',
                'message' => 'Viewport meta tag is missing initial-scale=1'
            ];
            
            $this->recommendations[] = [
                'type' => 'viewport',
                'priority' => 'low',
                'message' => 'Update viewport meta tag to include initial-scale=1'
            ];
        }
    }
    
    /**
     * Analyze robots meta tag
     */
    private function analyzeRobots() {
        $robots = isset($this->metaTags['robots']) ? $this->metaTags['robots'] : null;
        
        // If no robots meta tag, assume default (index, follow) which is generally good
        if (!$robots) {
            $this->metaTags['robots'] = 'index, follow (default)';
            return;
        }
        
        // Check for noindex or nofollow
        if (stripos($robots, 'noindex') !== false) {
            $this->issues[] = [
                'type' => 'robots',
                'severity' => 'high',
                'message' => 'Page is set to noindex and will not appear in search results'
            ];
            
            $this->recommendations[] = [
                'type' => 'robots',
                'priority' => 'high',
                'message' => 'Remove noindex directive from robots meta tag if you want this page to be indexed by search engines'
            ];
        }
        
        if (stripos($robots, 'nofollow') !== false) {
            $this->issues[] = [
                'type' => 'robots',
                'severity' => 'medium',
                'message' => 'Page is set to nofollow and links will not be followed by search engines'
            ];
            
            $this->recommendations[] = [
                'type' => 'robots',
                'priority' => 'medium',
                'message' => 'Remove nofollow directive from robots meta tag if you want search engines to follow links on this page'
            ];
        }
    }
    
    /**
     * Analyze canonical link
     */
    private function analyzeCanonical() {
        $canonical = isset($this->metaTags['canonical']) ? $this->metaTags['canonical'] : null;
        
        // Check if canonical exists
        if (!$canonical) {
            $this->issues[] = [
                'type' => 'canonical',
                'severity' => 'medium',
                'message' => 'Missing canonical link tag'
            ];
            
            $this->recommendations[] = [
                'type' => 'canonical',
                'priority' => 'medium',
                'message' => 'Add a canonical link tag to prevent duplicate content issues'
            ];
            
            return;
        }
        
        // Check if canonical URL matches current URL (simple check, doesn't account for www vs non-www, etc.)
        if ($canonical !== $this->url && $canonical !== rtrim($this->url, '/')) {
            // Store the canonical URL for reference
            $this->metaTags['canonical_matches_url'] = false;
            
            // Not necessarily an issue, but worth noting
            $this->issues[] = [
                'type' => 'canonical',
                'severity' => 'low',
                'message' => 'Canonical URL (' . $canonical . ') does not match the current URL'
            ];
        } else {
            $this->metaTags['canonical_matches_url'] = true;
        }
    }
    
    /**
     * Analyze social media meta tags (Open Graph and Twitter)
     */
    private function analyzeSocialTags() {
        // Check for Open Graph tags
        $hasOgTitle = isset($this->metaTags['og:title']);
        $hasOgDescription = isset($this->metaTags['og:description']);
        $hasOgImage = isset($this->metaTags['og:image']);
        $hasOgUrl = isset($this->metaTags['og:url']);
        $hasOgType = isset($this->metaTags['og:type']);
        
        $this->metaTags['has_open_graph'] = ($hasOgTitle && $hasOgDescription && $hasOgImage);
        
        if (!$this->metaTags['has_open_graph']) {
            $this->issues[] = [
                'type' => 'social',
                'severity' => 'medium',
                'message' => 'Missing or incomplete Open Graph meta tags for social sharing'
            ];
            
            $this->recommendations[] = [
                'type' => 'social',
                'priority' => 'medium',
                'message' => 'Add Open Graph meta tags (og:title, og:description, og:image) to improve social sharing'
            ];
        }
        
        // Check specific missing OG tags
        if (!$hasOgTitle) {
            $this->recommendations[] = [
                'type' => 'social',
                'priority' => 'low',
                'message' => 'Add og:title meta tag for better social sharing'
            ];
        }
        
        if (!$hasOgDescription) {
            $this->recommendations[] = [
                'type' => 'social',
                'priority' => 'low',
                'message' => 'Add og:description meta tag for better social sharing'
            ];
        }
        
        if (!$hasOgImage) {
            $this->recommendations[] = [
                'type' => 'social',
                'priority' => 'medium',
                'message' => 'Add og:image meta tag to display an image when sharing on social media'
            ];
        }
        
        // Check for Twitter Card tags
        $hasTwitterCard = isset($this->metaTags['twitter:card']);
        $hasTwitterTitle = isset($this->metaTags['twitter:title']);
        $hasTwitterDescription = isset($this->metaTags['twitter:description']);
        $hasTwitterImage = isset($this->metaTags['twitter:image']);
        
        $this->metaTags['has_twitter_cards'] = ($hasTwitterCard && ($hasTwitterTitle || $hasTwitterDescription || $hasTwitterImage));
        
        if (!$this->metaTags['has_twitter_cards']) {
            $this->issues[] = [
                'type' => 'social',
                'severity' => 'low',
                'message' => 'Missing or incomplete Twitter Card meta tags for Twitter sharing'
            ];
            
            $this->recommendations[] = [
                'type' => 'social',
                'priority' => 'low',
                'message' => 'Add Twitter Card meta tags to improve appearance when sharing on Twitter'
            ];
        }
    }
    
    /**
     * Calculate scores based on analysis results
     */
    private function calculateScores() {
        // Initialize scores
        $titleScore = 100;
        $descriptionScore = 100;
        $viewportScore = 100;
        $robotsScore = 100;
        $canonicalScore = 100;
        $socialScore = 100;
        
        // Adjust scores based on issues
        foreach ($this->issues as $issue) {
            $score = 0;
            
            switch ($issue['severity']) {
                case 'high':
                    $score = -50;
                    break;
                case 'medium':
                    $score = -25;
                    break;
                case 'low':
                    $score = -10;
                    break;
            }
            
            switch ($issue['type']) {
                case 'title':
                    $titleScore += $score;
                    break;
                case 'description':
                    $descriptionScore += $score;
                    break;
                case 'viewport':
                    $viewportScore += $score;
                    break;
                case 'robots':
                    $robotsScore += $score;
                    break;
                case 'canonical':
                    $canonicalScore += $score;
                    break;
                case 'social':
                    $socialScore += $score;
                    break;
            }
        }
        
        // Ensure scores are in range 0-100
        $titleScore = max(0, min(100, $titleScore));
        $descriptionScore = max(0, min(100, $descriptionScore));
        $viewportScore = max(0, min(100, $viewportScore));
        $robotsScore = max(0, min(100, $robotsScore));
        $canonicalScore = max(0, min(100, $canonicalScore));
        $socialScore = max(0, min(100, $socialScore));
        
        // Calculate overall score (weighted average)
        $overallScore = round(
            ($titleScore * 0.3) +
            ($descriptionScore * 0.25) +
            ($viewportScore * 0.15) +
            ($robotsScore * 0.1) +
            ($canonicalScore * 0.1) +
            ($socialScore * 0.1)
        );
        
        // Set scores
        $this->scores = [
            'title' => $titleScore,
            'description' => $descriptionScore,
            'viewport' => $viewportScore,
            'robots' => $robotsScore,
            'canonical' => $canonicalScore,
            'social' => $socialScore,
            'overall' => $overallScore
        ];
    }
}
