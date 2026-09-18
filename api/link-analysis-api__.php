<?php
/**
 * link-analyzer.php - Class to analyze internal and external links on a website
 * File path: includes/link-analyzer.php
 */
 // This should be properly conditional
if (file_exists('../includes/broken-link-checker.php')) {
    require_once '../includes/broken-link-checker.php';
}

class LinkAnalyzer {
    private $baseUrl;
    private $maxPages;
    private $visitedUrls = [];
    private $allLinks = [];
    private $brokenLinks = [];
    private $linkDistribution = [];
    private $domain;

    /**
     * Constructor
     * 
     * @param string $url The base URL to analyze
     * @param int $maxPages Maximum number of pages to analyze
     */
    public function __construct($url, $maxPages = 10) {
        $this->baseUrl = $url;
        $this->maxPages = $maxPages;
        
        // Extract domain from URL
        $parsedUrl = parse_url($url);
        $this->domain = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
    }
    
    /**
     * Perform link analysis
     * 
     * @return array Analysis data
     */
    public function analyze() {
        // Start crawling from the base URL
        $this->crawlPage($this->baseUrl, 0);
        
        // Check for broken links
        $this->checkBrokenLinks();
        
        // Calculate metrics and generate visualization data
        $internalLinks = 0;
        $externalLinks = 0;
        $nofollowLinks = 0;
        
        foreach ($this->linkDistribution as $page) {
            $internalLinks += isset($page['internal']) ? $page['internal'] : 0;
            $externalLinks += isset($page['external']) ? $page['external'] : 0;
            $nofollowLinks += isset($page['nofollow']) ? $page['nofollow'] : 0;
        }
        
        // Calculate scores
        $internalLinkScore = $this->calculateInternalLinkScore();
        $externalLinkScore = $this->calculateExternalLinkScore();
        $brokenLinkScore = $this->calculateBrokenLinkScore();
        
        // Calculate overall score
        $overallScore = round(($internalLinkScore * 0.6) + ($externalLinkScore * 0.3) + ($brokenLinkScore * 0.1));
        
        // Prepare visualization data
        $visualizationData = $this->generateVisualizationData();
        
        // Create issues and recommendations
        $issues = $this->identifyIssues();
        $recommendations = $this->generateRecommendations();
        
        // Prepare final data
        return [
            'overall_score' => $overallScore,
            'internal_link_score' => $internalLinkScore,
            'external_link_score' => $externalLinkScore,
            'broken_link_score' => $brokenLinkScore,
            'total_internal_links' => $internalLinks,
            'total_external_links' => $externalLinks,
            'total_nofollow_links' => $nofollowLinks,
            'total_broken_links' => count($this->brokenLinks),
            'analyzed_pages' => count($this->visitedUrls),
            'orphaned_pages' => $this->countOrphanedPages(),
            'link_distribution' => $this->linkDistribution,
            'broken_links' => $this->brokenLinks,
            'pages_data' => $this->preparePageData(),
            'visualization_data' => $visualizationData,
            'issues' => $issues,
            'recommendations' => $recommendations
        ];
    }
    
    /**
     * Crawl a page and extract links
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
        
        // Extract links from the page
        $links = $this->extractLinks($html, $url);
        
        // Store links for this page
        $this->allLinks[$url] = $links;
        
        // Initialize link distribution
        $this->linkDistribution[$url] = [
            'internal' => 0,
            'external' => 0,
            'nofollow' => 0
        ];
        
        // Count link types
        foreach ($links as $link) {
            if (isset($link['type'])) {
                $this->linkDistribution[$url][$link['type']]++;
            }
            
            if (isset($link['nofollow']) && $link['nofollow']) {
                $this->linkDistribution[$url]['nofollow']++;
            }
        }
        
        // Stop at maximum depth
        if ($depth >= 2) {
            return;
        }
        
        // Crawl internal links
        foreach ($links as $link) {
            if (isset($link['type']) && $link['type'] === 'internal' && 
                isset($link['url']) && !in_array($link['url'], $this->visitedUrls)) {
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
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        
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
            
            // Get link text
            $text = trim($anchor->textContent);
            
            // Check if nofollow
            $rel = $anchor->getAttribute('rel');
            $nofollow = (strpos($rel, 'nofollow') !== false);
            
            // Determine if internal or external
            $type = $this->isInternalLink($absUrl) ? 'internal' : 'external';
            
            // Add to links
            $links[] = [
                'url' => $absUrl,
                'text' => $text,
                'type' => $type,
                'nofollow' => $nofollow
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
     * Check for broken links
     */
    private function checkBrokenLinks() {
        $this->brokenLinks = [];
        
        foreach ($this->allLinks as $source => $links) {
            foreach ($links as $link) {
                // Skip checking internal links that we've already visited
                if ($link['type'] === 'internal' && in_array($link['url'], $this->visitedUrls)) {
                    continue;
                }
                
                // Check external links and internal links we haven't visited
                $status = $this->checkLinkStatus($link['url']);
                
                if ($status >= 400 || $status === 0) {
                    $this->brokenLinks[] = [
                        'url' => $link['url'],
                        'source' => $source,
                        'status' => $status === 0 ? 'Connection Failed' : $status,
                        'type' => $link['type']
                    ];
                }
            }
        }
    }
    
    /**
     * Check if a link is broken
     * 
     * @param string $url URL to check
     * @return int HTTP status code (0 for connection failure)
     */
    private function checkLinkStatus($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        
        curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        
        return $status;
    }
    
    /**
     * Count orphaned pages (internal pages with no incoming links)
     * 
     * @return int Number of orphaned pages
     */
    private function countOrphanedPages() {
        $incomingLinks = [];
        
        // Count incoming links for each page
        foreach ($this->allLinks as $source => $links) {
            foreach ($links as $link) {
                if ($link['type'] === 'internal') {
                    if (!isset($incomingLinks[$link['url']])) {
                        $incomingLinks[$link['url']] = 0;
                    }
                    
                    $incomingLinks[$link['url']]++;
                }
            }
        }
        
        // Count orphaned pages
        $orphanedCount = 0;
        
        foreach ($this->visitedUrls as $url) {
            if ($url !== $this->baseUrl && (!isset($incomingLinks[$url]) || $incomingLinks[$url] === 0)) {
                $orphanedCount++;
            }
        }
        
        return $orphanedCount;
    }
    
    /**
     * Calculate internal link score
     * 
     * @return int Score (0-100)
     */
    private function calculateInternalLinkScore() {
        $totalPages = count($this->visitedUrls);
        
        if ($totalPages <= 1) {
            return 50; // Default score
        }
        
        // Get total internal links
        $totalInternalLinks = 0;
        
        foreach ($this->linkDistribution as $page) {
            $totalInternalLinks += isset($page['internal']) ? $page['internal'] : 0;
        }
        
        // Calculate average internal links per page
        $avgInternalLinks = $totalInternalLinks / $totalPages;
        
        // Score based on average internal links (ideal is around 10-20 per page)
        if ($avgInternalLinks < 3) {
            return 40; // Too few internal links
        } else if ($avgInternalLinks < 7) {
            return 70; // Below ideal but acceptable
        } else if ($avgInternalLinks < 25) {
            return 90; // Ideal range
        } else {
            return 60; // Too many internal links
        }
    }
    
    /**
     * Calculate external link score
     * 
     * @return int Score (0-100)
     */
    private function calculateExternalLinkScore() {
        $totalPages = count($this->visitedUrls);
        
        if ($totalPages === 0) {
            return 50; // Default score
        }
        
        // Get total external links
        $totalExternalLinks = 0;
        
        foreach ($this->linkDistribution as $page) {
            $totalExternalLinks += isset($page['external']) ? $page['external'] : 0;
        }
        
        // Calculate average external links per page
        $avgExternalLinks = $totalExternalLinks / $totalPages;
        
        // Score based on average external links (ideal is around 2-5 per page)
        if ($avgExternalLinks === 0) {
            return 40; // No external links
        } else if ($avgExternalLinks < 1) {
            return 60; // Few external links
        } else if ($avgExternalLinks < 8) {
            return 90; // Ideal range
        } else {
            return 70; // Many external links
        }
    }
    
    /**
     * Calculate broken link score
     * 
     * @return int Score (0-100)
     */
    private function calculateBrokenLinkScore() {
        $totalLinks = 0;
        
        foreach ($this->linkDistribution as $page) {
            $totalLinks += isset($page['internal']) ? $page['internal'] : 0;
            $totalLinks += isset($page['external']) ? $page['external'] : 0;
        }
        
        if ($totalLinks === 0) {
            return 50; // Default score
        }
        
        $brokenLinkCount = count($this->brokenLinks);
        $brokenLinkRatio = $brokenLinkCount / $totalLinks;
        
        // Score based on percentage of broken links
        if ($brokenLinkCount === 0) {
            return 100; // No broken links
        } else if ($brokenLinkRatio < 0.01) {
            return 90; // Less than 1% broken
        } else if ($brokenLinkRatio < 0.05) {
            return 70; // Less than 5% broken
        } else if ($brokenLinkRatio < 0.1) {
            return 40; // Less than 10% broken
        } else {
            return 20; // More than 10% broken
        }
    }
    
    /**
     * Generate visualization data for network graph
     * 
     * @return array Visualization data
     */
    private function generateVisualizationData() {
        $nodes = [];
        $links = [];
        $nodeIds = [];
        
        // Create main node for the base URL
        $mainUrl = $this->baseUrl;
        $mainId = count($nodes);
        $nodes[] = [
            'id' => $mainId,
            'label' => $this->getDomainLabel($mainUrl),
            'url' => $mainUrl,
            'group' => 'main',
            'size' => 25
        ];
        $nodeIds[$mainUrl] = $mainId;
        
        // Create nodes for each page
        foreach ($this->visitedUrls as $i => $url) {
            if ($url === $mainUrl) {
                continue;
            }
            
            $nodeId = count($nodes);
            $nodes[] = [
                'id' => $nodeId,
                'label' => $this->getPageLabel($url),
                'url' => $url,
                'group' => 'internal',
                'size' => 15
            ];
            $nodeIds[$url] = $nodeId;
        }
        
        // Create nodes for external sites (unique domains only)
        $externalDomains = [];
        
        foreach ($this->allLinks as $source => $pageLinks) {
            foreach ($pageLinks as $link) {
                if ($link['type'] === 'external') {
                    $domain = $this->getDomainFromUrl($link['url']);
                    
                    if (!isset($externalDomains[$domain])) {
                        $nodeId = count($nodes);
                        $nodes[] = [
                            'id' => $nodeId,
                            'label' => $domain,
                            'url' => $link['url'],
                            'group' => 'external',
                            'size' => 10
                        ];
                        $externalDomains[$domain] = $nodeId;
                    }
                }
            }
        }
        
        // Create links
        foreach ($this->allLinks as $source => $pageLinks) {
            if (!isset($nodeIds[$source])) {
                continue;
            }
            
            $sourceId = $nodeIds[$source];
            
            foreach ($pageLinks as $link) {
                $targetId = null;
                
                if ($link['type'] === 'internal') {
                    if (isset($nodeIds[$link['url']])) {
                        $targetId = $nodeIds[$link['url']];
                    }
                } else {
                    $domain = $this->getDomainFromUrl($link['url']);
                    if (isset($externalDomains[$domain])) {
                        $targetId = $externalDomains[$domain];
                    }
                }
                
                if ($targetId !== null) {
                    $links[] = [
                        'source' => $sourceId,
                        'target' => $targetId,
                        'type' => $link['type']
                    ];
                }
            }
        }
        
        return [
            'nodes' => $nodes,
            'links' => $links
        ];
    }
    
    /**
     * Get a domain label from a URL
     * 
     * @param string $url The URL
     * @return string Domain label
     */
    private function getDomainLabel($url) {
        $host = parse_url($url, PHP_URL_HOST);
        return $host ?: 'Unknown';
    }
    
    /**
     * Get a page label from a URL
     * 
     * @param string $url The URL
     * @return string Page label
     */
    private function getPageLabel($url) {
        $path = parse_url($url, PHP_URL_PATH);
        
        if (!$path || $path === '/') {
            return 'Home';
        }
        
        // Get the last part of the path
        $parts = explode('/', trim($path, '/'));
        return end($parts);
    }
    
    /**
     * Extract domain from URL
     * 
     * @param string $url The URL
     * @return string Domain
     */
    private function getDomainFromUrl($url) {
        $host = parse_url($url, PHP_URL_HOST);
        return $host ?: 'Unknown';
    }
    
    /**
     * Identify issues based on analysis
     * 
     * @return array List of issues
     */
    private function identifyIssues() {
        $issues = [];
        
        // Check for broken links
        $brokenLinkCount = count($this->brokenLinks);
        if ($brokenLinkCount > 0) {
            $severity = $brokenLinkCount > 10 ? 'high' : ($brokenLinkCount > 3 ? 'medium' : 'low');
            $issues[] = [
                'description' => "Found {$brokenLinkCount} broken links",
                'severity' => $severity
            ];
        }
        
        // Check for orphaned pages
        $orphanedPages = $this->countOrphanedPages();
        if ($orphanedPages > 0) {
            $severity = $orphanedPages > 5 ? 'high' : ($orphanedPages > 2 ? 'medium' : 'low');
            $issues[] = [
                'description' => "Found {$orphanedPages} orphaned pages (no incoming links)",
                'severity' => $severity
            ];
        }
        
        // Check for internal linking issues
        $avgInternalLinks = 0;
        $totalPages = count($this->visitedUrls);
        
        if ($totalPages > 0) {
            $totalInternalLinks = 0;
            
            foreach ($this->linkDistribution as $page) {
                $totalInternalLinks += isset($page['internal']) ? $page['internal'] : 0;
            }
            
            $avgInternalLinks = $totalInternalLinks / $totalPages;
        }
        
        if ($avgInternalLinks < 3) {
            $issues[] = [
                'description' => "Insufficient internal linking (avg. " . round($avgInternalLinks, 1) . " links per page)",
                'severity' => 'medium'
            ];
        }
        
        // Check for external linking issues
        $totalExternalLinks = 0;
        
        foreach ($this->linkDistribution as $page) {
            $totalExternalLinks += isset($page['external']) ? $page['external'] : 0;
        }
        
        if ($totalExternalLinks === 0 && $totalPages > 0) {
            $issues[] = [
                'description' => "No external links found",
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
        
        // Fix broken links
        if (count($this->brokenLinks) > 0) {
            $priority = count($this->brokenLinks) > 5 ? 'high' : 'medium';
            $recommendations[] = [
                'description' => "Fix " . count($this->brokenLinks) . " broken links",
                'priority' => $priority,
                'details' => "Broken links provide a poor user experience and can negatively impact SEO. Use the provided list to identify and fix or remove all broken links."
            ];
        }
        
        // Fix orphaned pages
        $orphanedPages = $this->countOrphanedPages();
        if ($orphanedPages > 0) {
            $recommendations[] = [
                'description' => "Add internal links to " . $orphanedPages . " orphaned pages",
                'priority' => 'medium',
                'details' => "Orphaned pages are difficult for both users and search engines to discover. Add internal links to these pages from relevant content to improve their visibility."
            ];
        }
        
        // Improve internal linking
        $totalPages = count($this->visitedUrls);
        if ($totalPages > 0) {
            $totalInternalLinks = 0;
            
            foreach ($this->linkDistribution as $page) {
                $totalInternalLinks += isset($page['internal']) ? $page['internal'] : 0;
            }
            
            $avgInternalLinks = $totalInternalLinks / $totalPages;
            
            if ($avgInternalLinks < 5) {
                $recommendations[] = [
                    'description' => "Increase internal linking throughout the site",
                    'priority' => 'high',
                    'details' => "Your site has an average of only " . round($avgInternalLinks, 1) . " internal links per page. Aim for 10-20 internal links per page to strengthen your internal link structure."
                ];
            }
        }
        
        // Add external links if needed
        $totalExternalLinks = 0;
        
        foreach ($this->linkDistribution as $page) {
            $totalExternalLinks += isset($page['external']) ? $page['external'] : 0;
        }
        
        if ($totalExternalLinks === 0 && $totalPages > 0) {
            $recommendations[] = [
                'description' => "Add external links to authoritative sources",
                'priority' => 'low',
                'details' => "Linking to relevant, high-quality external resources can improve credibility and provide more value to your visitors."
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Prepare page data for detailed view
     * 
     * @return array Page data
     */
    private function preparePageData() {
        $pagesData = [];
        
        foreach ($this->allLinks as $pageUrl => $links) {
            $pagesData[] = [
                'url' => $pageUrl,
                'links' => $links
            ];
        }
        
        return $pagesData;
    }
}
