<?php
/**
 * link-analyzer.php - Analyzes internal and external link structures
 * File path: includes/link-analyzer.php
 */

class LinkAnalyzer {
    private $url;
    private $domain;
    private $internalLinks = [];
    private $externalLinks = [];
    private $brokenLinks = [];
    private $linkTexts = [];
    private $maxPages = 10;
    private $visitedUrls = [];
    private $pageDepth = [];
    private $noFollowLinks = [];
    private $orphanedPages = [];
    private $linkDistribution = [];
    private $maxDepth = 3;
    
    /**
     * Constructor
     *
     * @param string $url The URL to analyze
     * @param int $maxPages Maximum number of pages to analyze
     */
    public function __construct($url, $maxPages = 10) {
        $this->url = $url;
        $this->maxPages = $maxPages;
        
        // Extract domain
        $parsedUrl = parse_url($url);
        $this->domain = $parsedUrl['host'];
    }
    
    /**
     * Run the link analysis
     *
     * @return array Analysis results
     */
    public function analyze() {
        // Start with the homepage
        $this->analyzePageLinks($this->url, 0);
        
        // Calculate link metrics
        $totalInternalLinks = count($this->internalLinks);
        $totalExternalLinks = count($this->externalLinks);
        $totalBrokenLinks = count($this->brokenLinks);
        $totalNoFollowLinks = count($this->noFollowLinks);
        
        // Calculate internal link score
        $internalLinkScore = $this->calculateInternalLinkScore($totalInternalLinks, $totalBrokenLinks);
        
        // Calculate external link score
        $externalLinkScore = $this->calculateExternalLinkScore($totalExternalLinks, $totalNoFollowLinks);
        
        // Prepare issues and recommendations
        $issues = $this->identifyIssues();
        $recommendations = $this->generateRecommendations($issues);
        
        // Prepare link data for visualization
        $visualizationData = $this->prepareVisualizationData();
        
        // Return complete analysis data
        return [
            'url' => $this->url,
            'domain' => $this->domain,
            'total_internal_links' => $totalInternalLinks,
            'total_external_links' => $totalExternalLinks,
            'total_broken_links' => $totalBrokenLinks,
            'total_nofollow_links' => $totalNoFollowLinks,
            'orphaned_pages' => count($this->orphanedPages),
            'internal_link_score' => $internalLinkScore,
            'external_link_score' => $externalLinkScore,
            'overall_score' => round(($internalLinkScore * 0.7) + ($externalLinkScore * 0.3)),
            'broken_links' => array_slice($this->brokenLinks, 0, 20), // Limit to 20 for display
            'issues' => $issues,
            'recommendations' => $recommendations,
            'visualization_data' => $visualizationData,
            'link_distribution' => $this->linkDistribution,
            'analyzed_pages' => count($this->visitedUrls)
        ];
    }
    
    /**
     * Analyze links on a single page
     *
     * @param string $url The URL to analyze
     * @param int $depth Current crawl depth
     * @return void
     */
    private function analyzePageLinks($url, $depth) {
        // Stop if we've reached the maximum pages to analyze
        if (count($this->visitedUrls) >= $this->maxPages) {
            return;
        }
        
        // Stop if we've already visited this URL
        if (in_array($url, $this->visitedUrls)) {
            return;
        }
        
        // Stop if we've reached maximum depth
        if ($depth > $this->maxDepth) {
            return;
        }
        
        // Add to visited URLs
        $this->visitedUrls[] = $url;
        $this->pageDepth[$url] = $depth;
        
        // Fetch page content
        $html = $this->fetchUrl($url);
        if (!$html) {
            return;
        }
        
        // Extract links from the page
        $links = $this->extractLinks($html, $url);
        
        // Count links for link distribution
        $this->linkDistribution[$url] = [
            'internal' => 0,
            'external' => 0,
            'nofollow' => 0
        ];
        
        // Process each link
        foreach ($links as $link) {
            $linkUrl = $link['url'];
            $linkText = $link['text'];
            $isNoFollow = $link['nofollow'];
            $isExternal = $link['external'];
            
            // Store link text for analysis
            if (!empty($linkText)) {
                $this->linkTexts[] = [
                    'url' => $linkUrl,
                    'text' => $linkText,
                    'source' => $url
                ];
            }
            
            // Check for nofollow
            if ($isNoFollow) {
                $this->noFollowLinks[$linkUrl] = [
                    'url' => $linkUrl,
                    'source' => $url,
                    'text' => $linkText
                ];
                $this->linkDistribution[$url]['nofollow']++;
            }
            
            // Process internal vs external links
            if ($isExternal) {
                // External link
                if (!isset($this->externalLinks[$linkUrl])) {
                    $this->externalLinks[$linkUrl] = [
                        'url' => $linkUrl,
                        'sources' => [$url],
                        'text' => $linkText,
                        'status' => $this->checkLinkStatus($linkUrl)
                    ];
                } else {
                    // Add this page as a source if not already listed
                    if (!in_array($url, $this->externalLinks[$linkUrl]['sources'])) {
                        $this->externalLinks[$linkUrl]['sources'][] = $url;
                    }
                }
                
                $this->linkDistribution[$url]['external']++;
                
                // Check if it's broken
                if ($this->externalLinks[$linkUrl]['status'] !== 200) {
                    $this->brokenLinks[] = [
                        'url' => $linkUrl,
                        'source' => $url,
                        'type' => 'external',
                        'status' => $this->externalLinks[$linkUrl]['status']
                    ];
                }
            } else {
                // Internal link
                if (!isset($this->internalLinks[$linkUrl])) {
                    $this->internalLinks[$linkUrl] = [
                        'url' => $linkUrl,
                        'sources' => [$url],
                        'text' => $linkText,
                        'status' => null // Will check later if we crawl this page
                    ];
                } else {
                    // Add this page as a source if not already listed
                    if (!in_array($url, $this->internalLinks[$linkUrl]['sources'])) {
                        $this->internalLinks[$linkUrl]['sources'][] = $url;
                    }
                }
                
                $this->linkDistribution[$url]['internal']++;
                
                // Crawl this internal link if we haven't reached the max
                if (count($this->visitedUrls) < $this->maxPages) {
                    $this->analyzePageLinks($linkUrl, $depth + 1);
                }
            }
        }
        
        // Check for orphaned pages (internal pages with no incoming links)
        foreach ($this->internalLinks as $linkUrl => $linkData) {
            if (count($linkData['sources']) <= 1 && !in_array($linkUrl, [$this->url, $this->url . '/'])) {
                // Not the homepage and has 1 or fewer incoming links
                $this->orphanedPages[] = [
                    'url' => $linkUrl,
                    'source' => isset($linkData['sources'][0]) ? $linkData['sources'][0] : null
                ];
            }
        }
    }
    
    /**
     * Fetch URL content
     *
     * @param string $url URL to fetch
     * @return string|false HTML content or false on failure
     */
    private function fetchUrl($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SEO Analysis Tool/1.0');
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300 && $html) {
            return $html;
        }
        
        return false;
    }
    
    /**
     * Extract links from HTML
     *
     * @param string $html HTML content
     * @param string $baseUrl Base URL for relative links
     * @return array Extracted links
     */
    private function extractLinks($html, $baseUrl) {
        $links = [];
        $dom = new DOMDocument();
        
        // Suppress errors from malformed HTML
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        $anchors = $xpath->query('//a[@href]');
        
        foreach ($anchors as $anchor) {
            $href = $anchor->getAttribute('href');
            $rel = $anchor->getAttribute('rel');
            $text = trim($anchor->textContent);
            
            // Skip empty, javascript, mailto, tel links
            if (empty($href) || 
                strpos($href, 'javascript:') === 0 || 
                strpos($href, 'mailto:') === 0 || 
                strpos($href, 'tel:') === 0 || 
                $href === '#') {
                continue;
            }
            
            // Convert relative URLs to absolute
            if (strpos($href, 'http') !== 0) {
                if (strpos($href, '/') === 0) {
                    // Absolute path
                    $parsedBase = parse_url($baseUrl);
                    $href = $parsedBase['scheme'] . '://' . $parsedBase['host'] . $href;
                } else {
                    // Relative path
                    $href = rtrim($baseUrl, '/') . '/' . ltrim($href, '/');
                }
            }
            
            // Determine if external
            $parsedHref = parse_url($href);
            $isExternal = !isset($parsedHref['host']) ? false : 
                          ($parsedHref['host'] !== $this->domain && 
                           'www.' . $parsedHref['host'] !== $this->domain && 
                           $parsedHref['host'] !== 'www.' . $this->domain);
            
            // Check for nofollow
            $isNoFollow = (strpos($rel, 'nofollow') !== false);
            
            // Add to links array
            $links[] = [
                'url' => $href,
                'text' => $text,
                'nofollow' => $isNoFollow,
                'external' => $isExternal
            ];
        }
        
        return $links;
    }
    
    /**
     * Check if a link is active
     *
     * @param string $url URL to check
     * @return int HTTP status code
     */
    private function checkLinkStatus($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SEO Analysis Tool/1.0');
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode;
    }
    
    /**
     * Calculate internal link score
     *
     * @param int $totalInternalLinks Total internal links
     * @param int $totalBrokenLinks Total broken links
     * @return int Score from 0-100
     */
    private function calculateInternalLinkScore($totalInternalLinks, $totalBrokenLinks) {
        // Base score
        $score = 100;
        
        // Deduct for broken links (more impactful)
        if ($totalInternalLinks > 0) {
            $brokenRatio = $totalBrokenLinks / $totalInternalLinks;
            $score -= min(50, $brokenRatio * 200); // Max 50 point deduction
        }
        
        // Deduct for orphaned pages
        $score -= min(30, count($this->orphanedPages) * 5); // 5 points per orphaned page, max 30
        
        // Adjust based on link distribution
        if (count($this->linkDistribution) > 0) {
            $avgLinksPerPage = $totalInternalLinks / count($this->linkDistribution);
            
            // Penalize for too few internal links
            if ($avgLinksPerPage < 5) {
                $score -= 20 * (1 - ($avgLinksPerPage / 5));
            }
            
            // Penalize for excessive internal links
            if ($avgLinksPerPage > 100) {
                $score -= min(20, ($avgLinksPerPage - 100) / 10);
            }
        }
        
        return max(0, min(100, round($score)));
    }
    
    /**
     * Calculate external link score
     *
     * @param int $totalExternalLinks Total external links
     * @param int $totalNoFollowLinks Total nofollow links
     * @return int Score from 0-100
     */
    private function calculateExternalLinkScore($totalExternalLinks, $totalNoFollowLinks) {
        // Base score
        $score = 100;
        
        // Deduct for broken external links
        $brokenExternalLinks = 0;
        foreach ($this->brokenLinks as $link) {
            if ($link['type'] === 'external') {
                $brokenExternalLinks++;
            }
        }
        
        if ($totalExternalLinks > 0) {
            $brokenRatio = $brokenExternalLinks / $totalExternalLinks;
            $score -= min(50, $brokenRatio * 200); // Max 50 point deduction
        }
        
        // Check nofollow usage
        if ($totalExternalLinks > 0) {
            $nofollowRatio = $totalNoFollowLinks / $totalExternalLinks;
            
            // Penalize for not using nofollow on external links (SEO best practice)
            if ($nofollowRatio < 0.3) {
                $score -= 20 * (1 - ($nofollowRatio / 0.3));
            }
        }
        
        return max(0, min(100, round($score)));
    }
    
    /**
     * Identify issues based on analysis
     *
     * @return array List of issues
     */
    private function identifyIssues() {
        $issues = [];
        
        // Check for broken links
        if (count($this->brokenLinks) > 0) {
            $issues[] = [
                'description' => count($this->brokenLinks) . ' broken links detected',
                'severity' => count($this->brokenLinks) > 10 ? 'high' : 'medium'
            ];
        }
        
        // Check for orphaned pages
        if (count($this->orphanedPages) > 0) {
            $issues[] = [
                'description' => count($this->orphanedPages) . ' orphaned pages detected with few or no incoming links',
                'severity' => count($this->orphanedPages) > 5 ? 'high' : 'medium'
            ];
        }
        
        // Check for excessive external links
        $excessiveExternalLinks = false;
        foreach ($this->linkDistribution as $url => $counts) {
            if ($counts['external'] > 50) {
                $excessiveExternalLinks = true;
                break;
            }
        }
        
        if ($excessiveExternalLinks) {
            $issues[] = [
                'description' => 'Some pages have excessive external links (>50)',
                'severity' => 'medium'
            ];
        }
        
        // Check for poor nofollow usage
        $totalExternalLinks = count($this->externalLinks);
        $totalNoFollowLinks = count($this->noFollowLinks);
        
        if ($totalExternalLinks > 10 && $totalNoFollowLinks / $totalExternalLinks < 0.2) {
            $issues[] = [
                'description' => 'Low usage of nofollow attributes for external links',
                'severity' => 'medium'
            ];
        }
        
        // Check for shallow link depth
        $deepPages = 0;
        foreach ($this->pageDepth as $url => $depth) {
            if ($depth >= 2) {
                $deepPages++;
            }
        }
        
        if ($deepPages < 3 && count($this->visitedUrls) > 5) {
            $issues[] = [
                'description' => 'Shallow link structure - most content is within 1-2 clicks from homepage',
                'severity' => 'low'
            ];
        }
        
        // Check for improper anchor text (empty or generic)
        $genericAnchors = 0;
        $genericAnchorTerms = ['click here', 'read more', 'more', 'link', 'this page', 'here'];
        
        foreach ($this->linkTexts as $link) {
            $text = strtolower(trim($link['text']));
            if (empty($text) || in_array($text, $genericAnchorTerms)) {
                $genericAnchors++;
            }
        }
        
        if ($genericAnchors > 5) {
            $issues[] = [
                'description' => $genericAnchors . ' links with generic or empty anchor text detected',
                'severity' => 'medium'
            ];
        }
        
        return $issues;
    }
    
    /**
     * Generate recommendations based on issues
     *
     * @param array $issues List of identified issues
     * @return array List of recommendations
     */
    private function generateRecommendations($issues) {
        $recommendations = [];
        
        // Process each issue
        foreach ($issues as $issue) {
            switch (true) {
                case strpos($issue['description'], 'broken links') !== false:
                    $recommendations[] = [
                        'description' => 'Fix broken links to improve user experience and SEO ranking',
                        'priority' => $issue['severity'],
                        'details' => 'Review the list of broken links and either remove them, update them to valid URLs, or restore the missing content.'
                    ];
                    break;
                    
                case strpos($issue['description'], 'orphaned pages') !== false:
                    $recommendations[] = [
                        'description' => 'Add internal links to orphaned pages to improve their visibility',
                        'priority' => $issue['severity'],
                        'details' => 'Create links to orphaned pages from relevant, high-traffic pages on your site.'
                    ];
                    break;
                    
                case strpos($issue['description'], 'excessive external links') !== false:
                    $recommendations[] = [
                        'description' => 'Reduce the number of external links on pages with excessive outbound links',
                        'priority' => $issue['severity'],
                        'details' => 'Too many external links can dilute your page authority and confuse users. Limit to the most relevant external resources.'
                    ];
                    break;
                    
                case strpos($issue['description'], 'nofollow attributes') !== false:
                    $recommendations[] = [
                        'description' => 'Add nofollow attributes to appropriate external links',
                        'priority' => $issue['severity'],
                        'details' => 'Use rel="nofollow" for sponsored links, user-generated content, and links to untrusted sites to avoid passing authority unnecessarily.'
                    ];
                    break;
                    
                case strpos($issue['description'], 'Shallow link structure') !== false:
                    $recommendations[] = [
                        'description' => 'Improve site hierarchy with a deeper, more organized link structure',
                        'priority' => $issue['severity'],
                        'details' => 'Create category pages and ensure content is organized in a logical hierarchy, with links connecting related content.'
                    ];
                    break;
                    
                case strpos($issue['description'], 'generic or empty anchor text') !== false:
                    $recommendations[] = [
                        'description' => 'Replace generic anchor text with descriptive, keyword-rich alternatives',
                        'priority' => $issue['severity'],
                        'details' => 'Instead of "click here" or "read more", use descriptive text that indicates what the linked page is about.'
                    ];
                    break;
            }
        }
        
        // Add general recommendations if few issues were found
        if (count($recommendations) < 3) {
            // Add recommendations for good link practices
            $recommendations[] = [
                'description' => 'Implement a clear site navigation structure',
                'priority' => 'medium',
                'details' => 'Ensure your main navigation includes links to all important sections of your website.'
            ];
            
            $recommendations[] = [
                'description' => 'Add contextual internal links between related content',
                'priority' => 'medium',
                'details' => 'Link between pages with related topics to help users and search engines discover related content.'
            ];
            
            $recommendations[] = [
                'description' => 'Create a comprehensive sitemap',
                'priority' => 'medium',
                'details' => 'Maintain both an HTML sitemap for users and an XML sitemap for search engines.'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Prepare data for visualization
     *
     * @return array Visualization data
     */
    private function prepareVisualizationData() {
        $nodes = [];
        $links = [];
        
        // Add the main domain as the central node
        $nodes[] = [
            'id' => $this->domain,
            'label' => $this->domain,
            'group' => 'main',
            'size' => 20
        ];
        
        // Add visited pages as nodes
        $nodeIds = [$this->domain];
        $counter = 1;
        
        foreach ($this->visitedUrls as $url) {
            $parsedUrl = parse_url($url);
            $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '/';
            $label = $path === '/' ? 'Homepage' : substr($path, 1);
            
            // Generate a unique ID
            $id = 'page' . $counter++;
            
            // Add to nodes
            $nodes[] = [
                'id' => $id,
                'url' => $url,
                'label' => $label,
                'group' => 'internal',
                'size' => 10
            ];
            
            $nodeIds[] = $id;
            
            // Add link to the main domain
            $links[] = [
                'source' => $id,
                'target' => $this->domain,
                'type' => 'internal'
            ];
        }
        
        // Add some key external domains
        $externalCounter = 0;
        foreach ($this->externalLinks as $url => $data) {
            if ($externalCounter++ >= 10) break;
            
            $parsedUrl = parse_url($url);
            $externalDomain = isset($parsedUrl['host']) ? $parsedUrl['host'] : 'external';
            
            // Generate a unique ID
            $id = 'ext' . $externalCounter;
            
            // Add to nodes
            $nodes[] = [
                'id' => $id,
                'url' => $url,
                'label' => $externalDomain,
                'group' => 'external',
                'size' => 8
            ];
            
            // Add link to the main domain
            $links[] = [
                'source' => $this->domain,
                'target' => $id,
                'type' => 'external'
            ];
        }
        
        return [
            'nodes' => $nodes,
            'links' => $links
        ];
    }
}
