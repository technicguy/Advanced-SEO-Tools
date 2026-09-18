<?php
/**
 * page-structure-analyzer.php - Class to analyze HTML structure and semantic layout of pages
 * File path: includes/page-structure-analyzer.php
 */

class PageStructureAnalyzer {
    private $url;
    private $maxPages;
    private $visitedUrls = [];
    private $pagesData = [];
    private $domain;

    /**
     * Constructor
     * 
     * @param string $url The base URL to analyze
     * @param int $maxPages Maximum number of pages to analyze
     */
    public function __construct($url, $maxPages = 5) {
        $this->url = $url;
        $this->maxPages = $maxPages;
        
        // Extract domain from URL
        $parsedUrl = parse_url($url);
        $this->domain = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
    }
    
    /**
     * Perform page structure analysis
     * 
     * @return array Analysis data
     */
    public function analyze() {
        // Start crawling from the base URL
        $this->crawlPage($this->url, 0);
        
        // Calculate overall scores
        $headingScore = $this->calculateHeadingScore();
        $semanticScore = $this->calculateSemanticScore();
        $contentRatioScore = $this->calculateContentRatioScore();
        $schemaMarkupScore = $this->calculateSchemaMarkupScore();
        
        // Calculate overall score (weighted)
        $overallScore = round(
            ($headingScore * 0.3) + 
            ($semanticScore * 0.3) + 
            ($contentRatioScore * 0.2) + 
            ($schemaMarkupScore * 0.2)
        );
        
        // Generate issues and recommendations based on scores
        $issues = $this->identifyIssues();
        $recommendations = $this->generateRecommendations();
        
        // Prepare final data
        return [
            'overall_score' => $overallScore,
            'heading_structure_score' => $headingScore,
            'semantic_html_score' => $semanticScore,
            'content_html_ratio_score' => $contentRatioScore,
            'schema_markup_score' => $schemaMarkupScore,
            'analyzed_pages' => count($this->pagesData),
            'pages_data' => $this->pagesData,
            'issues' => $issues,
            'recommendations' => $recommendations
        ];
    }
    
    /**
     * Crawl a page and analyze its structure
     * 
     * @param string $url URL to crawl
     * @param int $depth Current depth
     */
    private function crawlPage($url, $depth) {
        // Skip if we've reached the maximum number of pages or already visited this URL
        if (count($this->visitedUrls) >= $this->maxPages || in_array($url, $this->visitedUrls)) {
            return;
        }
        
        // Add URL to visited list
        $this->visitedUrls[] = $url;
        
        // Fetch page content
        $html = $this->fetchPage($url);
        
        if (!$html) {
            return;
        }
        
        // Analyze page structure
        $pageData = $this->analyzePage($html, $url);
        
        // Store data for this page
        $this->pagesData[] = $pageData;
        
        // Stop at maximum depth
        if ($depth >= 2) {
            return;
        }
        
        // Extract links for crawling more pages
        $links = $this->extractLinks($html, $url);
        
        // Crawl internal links
        foreach ($links as $link) {
            if ($link['type'] === 'internal' && !in_array($link['url'], $this->visitedUrls)) {
                $this->crawlPage($link['url'], $depth + 1);
                
                // Stop if we've reached the maximum number of pages
                if (count($this->visitedUrls) >= $this->maxPages) {
                    break;
                }
            }
        }
    }
    
    /**
     * Fetch page content
     * 
     * @param string $url URL to fetch
     * @return string|bool Page content or false on failure
     */
    private function fetchPage($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $html = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        if ($status >= 200 && $status < 300 && $html) {
            return $html;
        }
        
        return false;
    }
    
    /**
     * Extract links from page HTML
     * 
     * @param string $html Page HTML
     * @param string $baseUrl Base URL for resolving relative links
     * @return array Extracted links
     */
    private function extractLinks($html, $baseUrl) {
        $links = [];
        
        // Create DOMDocument
        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        
        // Find all links
        $anchors = $doc->getElementsByTagName('a');
        
        foreach ($anchors as $anchor) {
            $href = $anchor->getAttribute('href');
            
            // Skip empty and javascript links
            if (empty($href) || substr($href, 0, 1) === '#' || substr($href, 0, 11) === 'javascript:') {
                continue;
            }
            
            // Resolve URL
            $absUrl = $this->resolveUrl($href, $baseUrl);
            
            if (!$absUrl) {
                continue;
            }
            
            // Determine if internal or external
            $type = $this->isInternalLink($absUrl) ? 'internal' : 'external';
            
            // Add to links
            $links[] = [
                'url' => $absUrl,
                'type' => $type
            ];
        }
        
        return $links;
    }
    
    /**
     * Resolve a URL against a base URL
     * 
     * @param string $url URL to resolve
     * @param string $baseUrl Base URL
     * @return string|bool Resolved URL or false on failure
     */
    private function resolveUrl($url, $baseUrl) {
        // Already absolute
        if (preg_match('~^https?://~i', $url)) {
            return $url;
        }
        
        // Handle tel: and mailto: links
        if (preg_match('~^(tel:|mailto:|ftp:|file:)~i', $url)) {
            return false;
        }
        
        // Parse base URL
        $parts = parse_url($baseUrl);
        
        if (!$parts) {
            return false;
        }
        
        // Handle root-relative URLs
        if (substr($url, 0, 1) === '/') {
            return $parts['scheme'] . '://' . $parts['host'] . $url;
        }
        
        // Handle relative URLs
        $basePath = isset($parts['path']) ? $parts['path'] : '/';
        $basePath = preg_replace('~/[^/]*$~', '/', $basePath);
        
        return $parts['scheme'] . '://' . $parts['host'] . $basePath . $url;
    }
    
    /**
     * Check if a URL is internal
     * 
     * @param string $url URL to check
     * @return bool True if internal, false if external
     */
    private function isInternalLink($url) {
        $parts = parse_url($url);
        
        if (!isset($parts['host'])) {
            return true;
        }
        
        return $parts['host'] === $this->domain;
    }
    
    /**
     * Analyze page structure
     * 
     * @param string $html HTML content
     * @param string $url URL of the page
     * @return array Page structure data
     */
    private function analyzePage($html, $url) {
        // Create DOMDocument
        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        
        // Create XPath
        $xpath = new DOMXPath($doc);
        
        // Analyze heading structure
        $headingStructure = $this->analyzeHeadingStructure($xpath);
        
        // Analyze semantic HTML
        $semanticElements = $this->analyzeSemanticElements($xpath);
        
        // Calculate content to HTML ratio
        $contentHtmlRatio = $this->calculateContentHtmlRatio($html);
        
        // Analyze schema markup
        $schemaMarkup = $this->detectSchemaMarkup($html, $xpath);
        
        // Get main content word count
        $mainContentWordCount = $this->getMainContentWordCount($xpath);
        
        // Prepare page data
        return [
            'url' => $url,
            'has_h1' => $headingStructure['has_h1'],
            'heading_structure' => $headingStructure,
            'semantic_elements' => $semanticElements,
            'content_html_ratio' => $contentHtmlRatio,
            'main_content_word_count' => $mainContentWordCount,
            'has_schema_markup' => $schemaMarkup['has_schema'],
            'schema_types' => $schemaMarkup['types']
        ];
    }
    
    /**
     * Analyze heading structure
     * 
     * @param DOMXPath $xpath XPath object
     * @return array Heading structure data
     */
    private function analyzeHeadingStructure($xpath) {
        // Count headings
        $h1Count = $xpath->query('//h1')->length;
        $h2Count = $xpath->query('//h2')->length;
        $h3Count = $xpath->query('//h3')->length;
        $h4Count = $xpath->query('//h4')->length;
        $h5Count = $xpath->query('//h5')->length;
        $h6Count = $xpath->query('//h6')->length;
        
        // Check if h1 is present
        $hasH1 = $h1Count > 0;
        
        // Check for multiple h1 tags
        $multipleH1 = $h1Count > 1;
        
        // Check if there's a proper hierarchy (h1 -> h2 -> h3)
        $properHierarchy = $hasH1 && $h2Count > 0;
        
        // Check for skipped levels (e.g., h1 -> h3 without h2)
        $skippedLevels = false;
        
        if ($hasH1 && $h3Count > 0 && $h2Count === 0) {
            $skippedLevels = true;
        }
        
        if ($h2Count > 0 && $h4Count > 0 && $h3Count === 0) {
            $skippedLevels = true;
        }
        
        // Get heading text for analysis
        $headings = [];
        $order = 0;
        
        for ($i = 1; $i <= 6; $i++) {
            $headingNodes = $xpath->query("//h$i");
            foreach ($headingNodes as $node) {
                $headings[] = [
                    'level' => $i,
                    'text' => trim($node->textContent),
                    'order' => $order++  // Fixed: Add order key
                ];
            }
        }
        
        // Sort headings by their order in the document
        usort($headings, function($a, $b) {
            return $a['order'] <=> $b['order'];
        });
        
        return [
            'has_h1' => $hasH1,
            'multiple_h1' => $multipleH1,
            'proper_hierarchy' => $properHierarchy,
            'skipped_levels' => $skippedLevels,
            'h1_count' => $h1Count,
            'h2_count' => $h2Count,
            'h3_count' => $h3Count,
            'h4_count' => $h4Count,
            'h5_count' => $h5Count,
            'h6_count' => $h6Count,
            'headings' => $headings
        ];
    }
    
    /**
     * Analyze semantic HTML elements
     * 
     * @param DOMXPath $xpath XPath object
     * @return array Semantic elements data
     */
    private function analyzeSemanticElements($xpath) {
        // Check for important semantic elements
        $hasHeader = $xpath->query('//header')->length > 0;
        $hasNav = $xpath->query('//nav')->length > 0;
        $hasMain = $xpath->query('//main')->length > 0;
        $hasFooter = $xpath->query('//footer')->length > 0;
        $hasSection = $xpath->query('//section')->length > 0;
        $hasArticle = $xpath->query('//article')->length > 0;
        $hasAside = $xpath->query('//aside')->length > 0;
        
        // Count semantic vs. div/span elements
        $semanticCount = $xpath->query('//header|//nav|//main|//footer|//section|//article|//aside|//figure|//figcaption|//time')->length;
        $divSpanCount = $xpath->query('//div|//span')->length;
        
        // Calculate semantic ratio
        $semanticRatio = $divSpanCount > 0 ? $semanticCount / ($semanticCount + $divSpanCount) : 0;
        
        return [
            'has_header' => $hasHeader,
            'has_nav' => $hasNav,
            'has_main' => $hasMain,
            'has_footer' => $hasFooter,
            'has_section' => $hasSection,
            'has_article' => $hasArticle,
            'has_aside' => $hasAside,
            'semantic_element_count' => $semanticCount,
            'div_span_count' => $divSpanCount,
            'semantic_ratio' => $semanticRatio
        ];
    }
    
    /**
     * Calculate content to HTML ratio
     * 
     * @param string $html HTML content
     * @return float Content to HTML ratio
     */
    private function calculateContentHtmlRatio($html) {
        // Strip comments
        $html = preg_replace('/<!--[\s\S]*?-->/', '', $html);
        
        // Calculate HTML size
        $htmlSize = strlen($html);
        
        // Strip HTML tags to get only text content
        $text = strip_tags($html);
        
        // Clean up whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        // Calculate text size
        $textSize = strlen($text);
        
        // Calculate ratio
        return $htmlSize > 0 ? $textSize / $htmlSize : 0;
    }
    
    /**
     * Detect schema markup
     * 
     * @param string $html HTML content
     * @param DOMXPath $xpath XPath object
     * @return array Schema markup data
     */
    private function detectSchemaMarkup($html, $xpath) {
        // Check for schema.org in the HTML
        $hasSchemaOrg = strpos($html, 'schema.org') !== false;
        
        // Check for JSON-LD script
        $jsonLdNodes = $xpath->query('//script[@type="application/ld+json"]');
        $hasJsonLd = $jsonLdNodes->length > 0;
        
        // Check for microdata
        $hasMicrodata = $xpath->query('//*[@itemtype]')->length > 0;
        
        // Extract schema types
        $schemaTypes = [];
        
        // From JSON-LD
        if ($hasJsonLd) {
            foreach ($jsonLdNodes as $node) {
                $json = trim($node->textContent);
                $data = json_decode($json, true);
                
                if (is_array($data) && isset($data['@type'])) {
                    $schemaTypes[] = $data['@type'];
                }
            }
        }
        
        // From microdata
        $microdataNodes = $xpath->query('//*[@itemtype]');
        foreach ($microdataNodes as $node) {
            $itemtype = $node->getAttribute('itemtype');
            if (strpos($itemtype, 'schema.org/') !== false) {
                $type = substr($itemtype, strrpos($itemtype, '/') + 1);
                $schemaTypes[] = $type;
            }
        }
        
        // Remove duplicates
        $schemaTypes = array_unique($schemaTypes);
        
        return [
            'has_schema' => $hasSchemaOrg || $hasJsonLd || $hasMicrodata,
            'has_json_ld' => $hasJsonLd,
            'has_microdata' => $hasMicrodata,
            'types' => $schemaTypes
        ];
    }
    
    /**
     * Get main content word count
     * 
     * @param DOMXPath $xpath XPath object
     * @return int Word count
     */
    private function getMainContentWordCount($xpath) {
        // Try to find main content using common selectors
        $contentNodes = $xpath->query('//main|//article|//div[@id="content"]|//div[contains(@class, "content")]');
        
        if ($contentNodes->length === 0) {
            // Fallback to body content
            $contentNodes = $xpath->query('//body');
        }
        
        $text = '';
        
        foreach ($contentNodes as $node) {
            $text .= ' ' . $node->textContent;
        }
        
        // Clean up text and count words
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        $words = explode(' ', $text);
        
        return count($words);
    }
    
    /**
     * Calculate heading structure score
     * 
     * @return int Score (0-100)
     */
    private function calculateHeadingScore() {
        $hasH1Count = 0;
        $multipleH1Count = 0;
        $properHierarchyCount = 0;
        $skippedLevelsCount = 0;
        
        foreach ($this->pagesData as $page) {
            if ($page['heading_structure']['has_h1']) {
                $hasH1Count++;
            }
            
            if ($page['heading_structure']['multiple_h1']) {
                $multipleH1Count++;
            }
            
            if ($page['heading_structure']['proper_hierarchy']) {
                $properHierarchyCount++;
            }
            
            if ($page['heading_structure']['skipped_levels']) {
                $skippedLevelsCount++;
            }
        }
        
        $totalPages = count($this->pagesData);
        
        if ($totalPages === 0) {
            return 50; // Default score
        }
        
        // Calculate percentages
        $hasH1Percent = ($hasH1Count / $totalPages) * 100;
        $noMultipleH1Percent = (($totalPages - $multipleH1Count) / $totalPages) * 100;
        $properHierarchyPercent = ($properHierarchyCount / $totalPages) * 100;
        $noSkippedLevelsPercent = (($totalPages - $skippedLevelsCount) / $totalPages) * 100;
        
        // Weight the factors
        $score = ($hasH1Percent * 0.4) + 
                 ($noMultipleH1Percent * 0.2) + 
                 ($properHierarchyPercent * 0.2) + 
                 ($noSkippedLevelsPercent * 0.2);
        
        return round($score);
    }
    
    /**
     * Calculate semantic HTML score
     * 
     * @return int Score (0-100)
     */
    private function calculateSemanticScore() {
        $hasHeaderCount = 0;
        $hasNavCount = 0;
        $hasMainCount = 0;
        $hasFooterCount = 0;
        $hasSectionCount = 0;
        $totalSemanticRatio = 0;
        
        foreach ($this->pagesData as $page) {
            if ($page['semantic_elements']['has_header']) {
                $hasHeaderCount++;
            }
            
            if ($page['semantic_elements']['has_nav']) {
                $hasNavCount++;
            }
            
            if ($page['semantic_elements']['has_main']) {
                $hasMainCount++;
            }
            
            if ($page['semantic_elements']['has_footer']) {
                $hasFooterCount++;
            }
            
            if ($page['semantic_elements']['has_section']) {
                $hasSectionCount++;
            }
            
            $totalSemanticRatio += $page['semantic_elements']['semantic_ratio'];
        }
        
        $totalPages = count($this->pagesData);
        
        if ($totalPages === 0) {
            return 50; // Default score
        }
        
        // Calculate percentages
        $hasHeaderPercent = ($hasHeaderCount / $totalPages) * 100;
        $hasNavPercent = ($hasNavCount / $totalPages) * 100;
        $hasMainPercent = ($hasMainCount / $totalPages) * 100;
        $hasFooterPercent = ($hasFooterCount / $totalPages) * 100;
        $hasSectionPercent = ($hasSectionCount / $totalPages) * 100;
        
        // Calculate average semantic ratio
        $avgSemanticRatio = $totalSemanticRatio / $totalPages;
        $semanticRatioScore = $avgSemanticRatio * 100;
        
        // Weight the factors
        $score = ($hasHeaderPercent * 0.15) + 
                 ($hasNavPercent * 0.15) + 
                 ($hasMainPercent * 0.15) + 
                 ($hasFooterPercent * 0.15) + 
                 ($hasSectionPercent * 0.15) + 
                 ($semanticRatioScore * 0.25);
        
        return round($score);
    }
    
    /**
     * Calculate content to HTML ratio score
     * 
     * @return int Score (0-100)
     */
    private function calculateContentRatioScore() {
        $totalRatio = 0;
        
        foreach ($this->pagesData as $page) {
            $totalRatio += $page['content_html_ratio'];
        }
        
        $totalPages = count($this->pagesData);
        
        if ($totalPages === 0) {
            return 50; // Default score
        }
        
        // Calculate average ratio
        $avgRatio = $totalRatio / $totalPages;
        
        // Score based on ratio (ideal is 0.25-0.35)
        if ($avgRatio < 0.1) {
            return 40; // Too little content
        } else if ($avgRatio < 0.2) {
            return 70;
        } else if ($avgRatio < 0.4) {
            return 90; // Ideal range
        } else {
            return 60; // Too much content relative to HTML (could be suspicious)
        }
    }
    
    /**
     * Calculate schema markup score
     * 
     * @return int Score (0-100)
     */
    private function calculateSchemaMarkupScore() {
        $hasSchemaCount = 0;
        
        foreach ($this->pagesData as $page) {
            if ($page['has_schema_markup']) {
                $hasSchemaCount++;
            }
        }
        
        $totalPages = count($this->pagesData);
        
        if ($totalPages === 0) {
            return 50; // Default score
        }
        
        // Calculate percentage of pages with schema markup
        $hasSchemaPercent = ($hasSchemaCount / $totalPages) * 100;
        
        return round($hasSchemaPercent);
    }
    
    /**
     * Identify issues based on analysis
     * 
     * @return array List of issues
     */
    private function identifyIssues() {
        $issues = [];
        
        // Check for missing H1 tags
        $missingH1Count = 0;
        foreach ($this->pagesData as $page) {
            if (!$page['heading_structure']['has_h1']) {
                $missingH1Count++;
            }
        }
        
        if ($missingH1Count > 0) {
            $severity = $missingH1Count === count($this->pagesData) ? 'high' : 'medium';
            $issues[] = [
                'description' => "Missing H1 tag on {$missingH1Count} page(s)",
                'severity' => $severity
            ];
        }
        
        // Check for multiple H1 tags
        $multipleH1Count = 0;
        foreach ($this->pagesData as $page) {
            if ($page['heading_structure']['multiple_h1']) {
                $multipleH1Count++;
            }
        }
        
        if ($multipleH1Count > 0) {
            $issues[] = [
                'description' => "Multiple H1 tags on {$multipleH1Count} page(s)",
                'severity' => 'medium'
            ];
        }
        
        // Check for skipped heading levels
        $skippedLevelsCount = 0;
        foreach ($this->pagesData as $page) {
            if ($page['heading_structure']['skipped_levels']) {
                $skippedLevelsCount++;
            }
        }
        
        if ($skippedLevelsCount > 0) {
            $issues[] = [
                'description' => "Skipped heading levels on {$skippedLevelsCount} page(s)",
                'severity' => 'low'
            ];
        }
        
        // Check for low semantic HTML usage
        $lowSemanticCount = 0;
        foreach ($this->pagesData as $page) {
            if ($page['semantic_elements']['semantic_ratio'] < 0.2) {
                $lowSemanticCount++;
            }
        }
        
        if ($lowSemanticCount > 0) {
            $issues[] = [
                'description' => "Low semantic HTML usage on {$lowSemanticCount} page(s)",
                'severity' => 'medium'
            ];
        }
        
        // Check for missing main semantic elements
        $missingMainElements = [];
        
        $missingHeaderCount = 0;
        $missingNavCount = 0;
        $missingMainCount = 0;
        $missingFooterCount = 0;
        
        foreach ($this->pagesData as $page) {
            if (!$page['semantic_elements']['has_header']) $missingHeaderCount++;
            if (!$page['semantic_elements']['has_nav']) $missingNavCount++;
            if (!$page['semantic_elements']['has_main']) $missingMainCount++;
            if (!$page['semantic_elements']['has_footer']) $missingFooterCount++;
        }
        
        $totalPages = count($this->pagesData);
        
        if ($missingHeaderCount === $totalPages) {
            $missingMainElements[] = 'header';
        }
        
        if ($missingNavCount === $totalPages) {
            $missingMainElements[] = 'nav';
        }
        
        if ($missingMainCount === $totalPages) {
            $missingMainElements[] = 'main';
        }
        
        if ($missingFooterCount === $totalPages) {
            $missingMainElements[] = 'footer';
        }
        
        if (!empty($missingMainElements)) {
            $issues[] = [
                'description' => "Missing semantic elements: " . implode(', ', $missingMainElements),
                'severity' => 'medium'
            ];
        }
        
        // Check for low content-to-HTML ratio
        $lowContentRatioCount = 0;
        foreach ($this->pagesData as $page) {
            if ($page['content_html_ratio'] < 0.1) {
                $lowContentRatioCount++;
            }
        }
        
        if ($lowContentRatioCount > 0) {
            $issues[] = [
                'description' => "Low content-to-HTML ratio on {$lowContentRatioCount} page(s)",
                'severity' => 'medium'
            ];
        }
        
        // Check for missing schema markup
        $missingSchemaCount = 0;
        foreach ($this->pagesData as $page) {
            if (!$page['has_schema_markup']) {
                $missingSchemaCount++;
            }
        }
        
        if ($missingSchemaCount === $totalPages) {
            $issues[] = [
                'description' => "No schema markup found on any page",
                'severity' => 'medium'
            ];
        } else if ($missingSchemaCount > 0) {
            $issues[] = [
                'description' => "Missing schema markup on {$missingSchemaCount} page(s)",
                'severity' => 'low'
            ];
        }
        
        return $issues;
    }
    
    /**
     * Generate recommendations based on analysis
     * 
     * @return array List of recommendations
     */
    private function generateRecommendations() {
        $recommendations = [];
        
        // Process heading structure issues
        $missingH1Count = 0;
        $multipleH1Count = 0;
        $skippedLevelsCount = 0;
        
        foreach ($this->pagesData as $page) {
            if (!$page['heading_structure']['has_h1']) {
                $missingH1Count++;
            }
            
            if ($page['heading_structure']['multiple_h1']) {
                $multipleH1Count++;
            }
            
            if ($page['heading_structure']['skipped_levels']) {
                $skippedLevelsCount++;
            }
        }
        
        $totalPages = count($this->pagesData);
        
        if ($missingH1Count > 0) {
            $priority = $missingH1Count === $totalPages ? 'high' : 'medium';
            $recommendations[] = [
                'description' => "Add H1 tags to pages missing them",
                'priority' => $priority,
                'details' => "Every page should have exactly one H1 tag that clearly describes the page's main topic. Currently, {$missingH1Count} page(s) are missing H1 tags."
            ];
        }
        
        if ($multipleH1Count > 0) {
            $recommendations[] = [
                'description' => "Fix pages with multiple H1 tags",
                'priority' => 'medium',
                'details' => "Each page should have exactly one H1 tag. Currently, {$multipleH1Count} page(s) have multiple H1 tags, which may confuse search engines about the main topic of the page."
            ];
        }
        
        if ($skippedLevelsCount > 0) {
            $recommendations[] = [
                'description' => "Fix skipped heading levels in content hierarchy",
                'priority' => 'low',
                'details' => "Maintain a proper heading structure (H1 → H2 → H3) without skipping levels. Currently, {$skippedLevelsCount} page(s) have skipped heading levels, which makes content hierarchy less clear to search engines and screen readers."
            ];
        }
        
        // Process semantic HTML issues
        $lowSemanticCount = 0;
        foreach ($this->pagesData as $page) {
            if ($page['semantic_elements']['semantic_ratio'] < 0.2) {
                $lowSemanticCount++;
            }
        }
        
        $missingMainElements = [];
        
        $missingHeaderCount = 0;
        $missingNavCount = 0;
        $missingMainCount = 0;
        $missingFooterCount = 0;
        
        foreach ($this->pagesData as $page) {
            if (!$page['semantic_elements']['has_header']) $missingHeaderCount++;
            if (!$page['semantic_elements']['has_nav']) $missingNavCount++;
            if (!$page['semantic_elements']['has_main']) $missingMainCount++;
            if (!$page['semantic_elements']['has_footer']) $missingFooterCount++;
        }
        
        if ($missingHeaderCount === $totalPages) {
            $missingMainElements[] = 'header';
        }
        
        if ($missingNavCount === $totalPages) {
            $missingMainElements[] = 'nav';
        }
        
        if ($missingMainCount === $totalPages) {
            $missingMainElements[] = 'main';
        }
        
        if ($missingFooterCount === $totalPages) {
            $missingMainElements[] = 'footer';
        }
        
        if (!empty($missingMainElements)) {
            $recommendations[] = [
                'description' => "Add missing semantic elements to your HTML structure",
                'priority' => 'medium',
                'details' => "Use semantic HTML elements like " . implode(', ', $missingMainElements) . " instead of generic div tags to improve SEO and accessibility."
            ];
        }
        
        if ($lowSemanticCount > 0) {
            $recommendations[] = [
                'description' => "Increase semantic HTML usage throughout the site",
                'priority' => 'medium',
                'details' => "Replace generic div and span tags with semantic HTML elements (header, nav, main, article, section, etc.) where appropriate. Currently, {$lowSemanticCount} page(s) have low semantic HTML usage."
            ];
        }
        
        // Process content ratio issues
        $lowContentRatioCount = 0;
        foreach ($this->pagesData as $page) {
            if ($page['content_html_ratio'] < 0.1) {
                $lowContentRatioCount++;
            }
        }
        
        if ($lowContentRatioCount > 0) {
            $recommendations[] = [
                'description' => "Improve content-to-HTML ratio",
                'priority' => 'medium',
                'details' => "Your pages have too much HTML code compared to actual content. Simplify your HTML structure, reduce unnecessary div nesting, and increase meaningful content to improve this ratio."
            ];
        }
        
        // Process schema markup issues
        $missingSchemaCount = 0;
        foreach ($this->pagesData as $page) {
            if (!$page['has_schema_markup']) {
                $missingSchemaCount++;
            }
        }
        
        if ($missingSchemaCount === $totalPages) {
            $recommendations[] = [
                'description' => "Implement schema markup across the site",
                'priority' => 'medium',
                'details' => "Add structured data using JSON-LD format to help search engines understand your content better and potentially enable rich snippets in search results.",
                'code_example' => $this->getSchemaMarkupExample()
            ];
        } else if ($missingSchemaCount > 0) {
            $recommendations[] = [
                'description' => "Add schema markup to pages missing it",
                'priority' => 'low',
                'details' => "Currently, {$missingSchemaCount} page(s) are missing schema markup. Add structured data to all important pages for better search engine visibility."
            ];
        }
        
        // Process main content word count issues
        $lowWordCountPages = 0;
        foreach ($this->pagesData as $page) {
            if ($page['main_content_word_count'] < 300) {
                $lowWordCountPages++;
            }
        }
        
        if ($lowWordCountPages > 0) {
            $recommendations[] = [
                'description' => "Increase content length on thin pages",
                'priority' => 'medium',
                'details' => "Currently, {$lowWordCountPages} page(s) have less than 300 words of content. For better SEO performance, aim for at least 500-1000 words of quality content on important pages."
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Get an example of schema markup
     * 
     * @return string Example schema markup code
     */
    private function getSchemaMarkupExample() {
        return '<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "Page Title",
  "description": "Page description goes here",
  "publisher": {
    "@type": "Organization",
    "name": "Organization Name",
    "logo": {
      "@type": "ImageObject",
      "url": "https://example.com/logo.png"
    }
  },
  "mainEntity": {
    "@type": "Article",
    "headline": "Article Headline",
    "author": {
      "@type": "Person",
      "name": "Author Name"
    },
    "datePublished": "2023-01-01T08:00:00+08:00",
    "dateModified": "2023-01-15T09:20:00+08:00"
  }
}
</script>';
    }
}
