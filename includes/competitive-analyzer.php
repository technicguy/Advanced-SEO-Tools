<?php
/**
 * competitive-analyzer.php - Class to analyze website against competitors
 * File path: includes/competitive-analyzer.php
 */

class CompetitiveAnalyzer {
    private $url;
    private $competitors;
    private $timeout = 30; // Timeout in seconds
    
    /**
     * Constructor
     * 
     * @param string $url URL to analyze
     * @param array $competitors Array of competitor URLs
     */
    public function __construct($url, $competitors = []) {
        $this->url = $url;
        $this->competitors = $competitors;
        
        // If no competitors provided, try to find top competitors
        if (empty($this->competitors)) {
            $this->competitors = $this->findTopCompetitors();
        }
    }
    
    /**
     * Run competitive analysis
     * 
     * @return array Analysis results
     */
    public function analyze() {
        // Analyze main website
        $mainSiteData = $this->analyzeSite($this->url, true);
        
        // Analyze competitors
        $competitorsData = [];
        foreach ($this->competitors as $competitor) {
            try {
                $competitorData = $this->analyzeSite($competitor);
                if ($competitorData) {
                    $competitorsData[] = $competitorData;
                }
            } catch (Exception $e) {
                // If competitor analysis fails, continue with others
                continue;
            }
        }
        
        // Compare and generate insights
        $analysisResults = $this->compareAndGenerateInsights($mainSiteData, $competitorsData);
        
        // Return final results
        return [
            'main_site' => $mainSiteData,
            'competitors' => $competitorsData,
            'comparative_analysis' => $analysisResults['comparative_analysis'],
            'strengths_weaknesses' => $analysisResults['strengths_weaknesses'],
            'opportunities' => $analysisResults['opportunities'],
            'keyword_gap' => $analysisResults['keyword_gap'],
            'content_gap' => $analysisResults['content_gap'],
            'backlink_opportunities' => $analysisResults['backlink_opportunities'],
            'overall_score' => $analysisResults['overall_score']
        ];
    }
    
    /**
     * Find top competitors based on industry and keywords
     * 
     * @return array Competitor URLs
     */
    private function findTopCompetitors() {
        // In a production environment, this would use keyword research APIs
        // or industry databases to find real competitors
        
        // For demonstration, return a few generic competitors
        return [
            'https://example-competitor1.com',
            'https://example-competitor2.com',
            'https://example-competitor3.com'
        ];
    }
    
    /**
     * Analyze a single website
     * 
     * @param string $url The website URL
     * @param bool $isMainSite Whether this is the main site being analyzed
     * @return array Site analysis data
     */
    private function analyzeSite($url, $isMainSite = false) {
        // Initialize site data
        $siteData = [
            'url' => $url,
            'domain' => $this->extractDomain($url),
            'title' => '',
            'description' => '',
            'page_data' => [
                'word_count' => 0,
                'heading_count' => 0,
                'image_count' => 0,
                'link_count' => 0
            ],
            'performance' => [
                'load_time' => 0,
                'page_size' => 0
            ],
            'seo_elements' => [
                'title_tag' => false,
                'meta_description' => false,
                'h1_tag' => false,
                'image_alt_text' => 0,
                'schema_markup' => false,
                'canonical_tag' => false
            ],
            'keywords' => [],
            'content_topics' => [],
            'backlink_data' => [
                'total_backlinks' => 0,
                'domain_authority' => 0,
                'referring_domains' => 0
            ],
            'social_signals' => [
                'facebook_shares' => 0,
                'twitter_shares' => 0,
                'linkedin_shares' => 0
            ],
            'technology_stack' => []
        ];
        
        // Fetch page content
        $html = $this->fetchPage($url);
        if (!$html) {
            throw new Exception('Failed to fetch page content');
        }
        
        // Extract basic page data
        $this->extractBasicPageData($html, $siteData);
        
        // Extract SEO elements
        $this->extractSeoElements($html, $siteData);
        
        // Extract keywords and topics (would use real NLP in production)
        $this->extractKeywordsAndTopics($html, $siteData);
        
        // Extract backlink data (simulated for demo)
        $this->simulateBacklinkData($siteData, $isMainSite);
        
        // Extract social signals (simulated for demo)
        $this->simulateSocialSignals($siteData);
        
        // Extract technology stack (simulated for demo)
        $this->simulateTechnologyStack($siteData);
        
        return $siteData;
    }
    
    /**
     * Fetch page content
     * 
     * @param string $url URL to fetch
     * @return string|bool Page content or false on failure
     */
    private function fetchPage($url) {
        // Check if URL is valid
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            // For demo purposes, return simulated HTML for example.com domains
            if (strpos($url, 'example-competitor') !== false) {
                return $this->getSimulatedHtml($url);
            }
            return false;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $start = microtime(true);
        $html = curl_exec($ch);
        $loadTime = microtime(true) - $start;
        
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $pageSize = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
        
        curl_close($ch);
        
        if ($status >= 200 && $status < 300 && $html) {
            // For simulated competitor domains, use simulated HTML
            if (strpos($url, 'example-competitor') !== false) {
                return $this->getSimulatedHtml($url);
            }
            
            // Save performance metrics
            return $html;
        }
        
        // For demo purposes, return simulated HTML for example.com domains
        if (strpos($url, 'example-competitor') !== false) {
            return $this->getSimulatedHtml($url);
        }
        
        return false;
    }
    
    /**
     * Get simulated HTML for competitor sites
     * 
     * @param string $url Competitor URL
     * @return string Simulated HTML
     */
    private function getSimulatedHtml($url) {
        $domain = $this->extractDomain($url);
        $competitorNumber = 1;
        
        if (strpos($domain, '1') !== false) {
            $competitorNumber = 1;
        } else if (strpos($domain, '2') !== false) {
            $competitorNumber = 2;
        } else if (strpos($domain, '3') !== false) {
            $competitorNumber = 3;
        }
        
        // Create different titles and descriptions for each competitor
        $titles = [
            1 => 'Premium Widget Solutions | Industry Leader',
            2 => 'Professional Widget Services & Solutions',
            3 => 'Custom Widgets for Business & Enterprise'
        ];
        
        $descriptions = [
            1 => 'Get premium widget solutions for your business. We offer the best quality widgets with expert support.',
            2 => 'Professional widget services for enterprises. Our solutions help businesses scale and grow efficiently.',
            3 => 'Custom-built widgets for all business needs. Explore our enterprise-grade widget solutions today.'
        ];
        
        // Create simulated HTML with varied content
        return '<!DOCTYPE html>
<html>
<head>
    <title>' . $titles[$competitorNumber] . '</title>
    <meta name="description" content="' . $descriptions[$competitorNumber] . '">
    <link rel="canonical" href="' . $url . '">
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "Competitor ' . $competitorNumber . ' Inc.",
        "url": "' . $url . '"
    }
    </script>
</head>
<body>
    <header>
        <h1>Welcome to ' . $domain . '</h1>
        <nav>
            <ul>
                <li><a href="#">Home</a></li>
                <li><a href="#">Products</a></li>
                <li><a href="#">Services</a></li>
                <li><a href="#">About</a></li>
                <li><a href="#">Contact</a></li>
            </ul>
        </nav>
    </header>
    <main>
        <section>
            <h2>Our Widgets</h2>
            <p>We provide high-quality widgets for all your business needs. Our widgets are designed to improve efficiency and productivity.</p>
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam auctor, nisl eget ultricies tincidunt, nisl nisl aliquam nisl, eget ultricies nisl nisl eget nisl.</p>
            <h3>Widget Features</h3>
            <ul>
                <li>Feature 1</li>
                <li>Feature 2</li>
                <li>Feature 3</li>
            </ul>
            <img src="widget1.jpg" alt="Widget 1">
            <img src="widget2.jpg" alt="Widget 2">
        </section>
        <section>
            <h2>Our Services</h2>
            <p>We offer professional widget installation and maintenance services. Our team of experts will ensure your widgets are working optimally.</p>
            <h3>Service Benefits</h3>
            <ul>
                <li>Benefit 1</li>
                <li>Benefit 2</li>
                <li>Benefit 3</li>
            </ul>
        </section>
    </main>
    <footer>
        <p>&copy; 2023 ' . $domain . '. All rights reserved.</p>
    </footer>
</body>
</html>';
    }
    
    /**
     * Extract basic page data
     * 
     * @param string $html Page HTML
     * @param array &$siteData Site data array (passed by reference)
     */
    private function extractBasicPageData($html, &$siteData) {
        // Create DOM document
        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new DOMXPath($doc);
        
        // Get title
        $titleTags = $xpath->query('//title');
        if ($titleTags->length > 0) {
            $siteData['title'] = trim($titleTags->item(0)->textContent);
        }
        
        // Get meta description
        $metaDescTags = $xpath->query('//meta[@name="description"]');
        if ($metaDescTags->length > 0) {
            $siteData['description'] = trim($metaDescTags->item(0)->getAttribute('content'));
        }
        
        // Count words
        $bodyText = $xpath->query('//body')->item(0)->textContent;
        $bodyText = preg_replace('/\s+/', ' ', $bodyText);
        $wordCount = str_word_count(strip_tags($bodyText));
        $siteData['page_data']['word_count'] = $wordCount;
        
        // Count headings
        $headings = $xpath->query('//h1|//h2|//h3|//h4|//h5|//h6');
        $siteData['page_data']['heading_count'] = $headings->length;
        
        // Count images
        $images = $xpath->query('//img');
        $siteData['page_data']['image_count'] = $images->length;
        
        // Count links
        $links = $xpath->query('//a[@href]');
        $siteData['page_data']['link_count'] = $links->length;
        
        // Set performance metrics (simulated for demo)
        $siteData['performance']['load_time'] = rand(800, 3000) / 1000; // 0.8 to 3.0 seconds
        $siteData['performance']['page_size'] = strlen($html) / 1024; // Size in KB
    }
    
    /**
     * Extract SEO elements
     * 
     * @param string $html Page HTML
     * @param array &$siteData Site data array (passed by reference)
     */
    private function extractSeoElements($html, &$siteData) {
        // Create DOM document
        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        $xpath = new DOMXPath($doc);
        
        // Check title tag
        $titleTags = $xpath->query('//title');
        $siteData['seo_elements']['title_tag'] = $titleTags->length > 0;
        
        // Check meta description
        $metaDescTags = $xpath->query('//meta[@name="description"]');
        $siteData['seo_elements']['meta_description'] = $metaDescTags->length > 0;
        
        // Check H1 tag
        $h1Tags = $xpath->query('//h1');
        $siteData['seo_elements']['h1_tag'] = $h1Tags->length > 0;
        
        // Check image alt text
        $images = $xpath->query('//img[@alt]');
        $siteData['seo_elements']['image_alt_text'] = $images->length;
        
        // Check schema markup
        $schemaMarkup = $xpath->query('//script[@type="application/ld+json"]');
        $siteData['seo_elements']['schema_markup'] = $schemaMarkup->length > 0;
        
        // Check canonical tag
        $canonicalTags = $xpath->query('//link[@rel="canonical"]');
        $siteData['seo_elements']['canonical_tag'] = $canonicalTags->length > 0;
    }
    
    /**
     * Extract keywords and topics
     * 
     * @param string $html Page HTML
     * @param array &$siteData Site data array (passed by reference)
     */
    private function extractKeywordsAndTopics($html, &$siteData) {
        // In a production environment, this would use natural language processing
        // or keyword extraction APIs to find real keywords and topics
        
        // For demo purposes, extract text and do simple keyword extraction
        $text = strip_tags($html);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = strtolower(trim($text));
        
        // Remove common stop words
        $stopWords = ['a', 'an', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'with', 'by', 'about', 'as', 'of', 'from'];
        $words = explode(' ', $text);
        $filteredWords = [];
        
        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) > 3 && !in_array($word, $stopWords)) {
                $filteredWords[] = $word;
            }
        }
        
        // Count word frequencies
        $wordFreq = array_count_values($filteredWords);
        
        // Sort by frequency
        arsort($wordFreq);
        
        // Take top 10 as keywords
        $keywords = array_slice($wordFreq, 0, 10, true);
        
        // Format for output
        $keywordsOutput = [];
        foreach ($keywords as $word => $freq) {
            $keywordsOutput[] = [
                'keyword' => $word,
                'frequency' => $freq,
                'density' => round(($freq / count($filteredWords)) * 100, 2)
            ];
        }
        
        $siteData['keywords'] = $keywordsOutput;
        
        // For topics, we would use more advanced clustering or topic modeling
        // For demo, just take a few combinations of top keywords
        $topics = [];
        $keywordKeys = array_keys($keywords);
        
        if (count($keywordKeys) >= 5) {
            $topics[] = $keywordKeys[0] . ' ' . $keywordKeys[1];
            $topics[] = $keywordKeys[2] . ' ' . $keywordKeys[3];
            $topics[] = $keywordKeys[0] . ' ' . $keywordKeys[4];
        }
        
        $siteData['content_topics'] = $topics;
    }
    
    /**
     * Simulate backlink data (for demo purposes)
     * 
     * @param array &$siteData Site data array (passed by reference)
     * @param bool $isMainSite Whether this is the main site being analyzed
     */
    private function simulateBacklinkData(&$siteData, $isMainSite) {
        // For main site, use lower values to show opportunity for improvement
        if ($isMainSite) {
            $siteData['backlink_data'] = [
                'total_backlinks' => rand(100, 500),
                'domain_authority' => rand(20, 40),
                'referring_domains' => rand(30, 80)
            ];
        } else {
            // For competitors, use higher values to create contrast
            $siteData['backlink_data'] = [
                'total_backlinks' => rand(500, 2000),
                'domain_authority' => rand(35, 60),
                'referring_domains' => rand(70, 200)
            ];
        }
    }
    
    /**
     * Simulate social signals (for demo purposes)
     * 
     * @param array &$siteData Site data array (passed by reference)
     */
    private function simulateSocialSignals(&$siteData) {
        $siteData['social_signals'] = [
            'facebook_shares' => rand(10, 500),
            'twitter_shares' => rand(5, 300),
            'linkedin_shares' => rand(2, 100)
        ];
    }
    
    /**
     * Simulate technology stack (for demo purposes)
     * 
     * @param array &$siteData Site data array (passed by reference)
     */
    private function simulateTechnologyStack(&$siteData) {
        $possibleTechnologies = [
            'JavaScript Frameworks' => ['React', 'Angular', 'Vue.js', 'jQuery'],
            'CMS' => ['WordPress', 'Drupal', 'Joomla', 'Magento', 'Shopify'],
            'Server' => ['Apache', 'Nginx', 'IIS', 'LiteSpeed'],
            'Analytics' => ['Google Analytics', 'Adobe Analytics', 'Hotjar'],
            'Advertising' => ['Google Ads', 'Facebook Pixel', 'AdRoll']
        ];
        
        $technologies = [];
        
        foreach ($possibleTechnologies as $category => $items) {
            $randomItem = $items[array_rand($items)];
            $technologies[$category] = $randomItem;
        }
        
        $siteData['technology_stack'] = $technologies;
    }
    
    /**
     * Compare data and generate insights
     * 
     * @param array $mainSiteData Main site data
     * @param array $competitorsData Competitors data
     * @return array Analysis results
     */
    private function compareAndGenerateInsights($mainSiteData, $competitorsData) {
        // Initialize results
        $results = [
            'comparative_analysis' => [],
            'strengths_weaknesses' => [
                'strengths' => [],
                'weaknesses' => []
            ],
            'opportunities' => [],
            'keyword_gap' => [],
            'content_gap' => [],
            'backlink_opportunities' => [],
            'overall_score' => 0
        ];
        
        // Check if we have competitors data
        if (empty($competitorsData)) {
            $results['comparative_analysis'] = [
                'status' => 'No competitors data available for comparison'
            ];
            $results['overall_score'] = 50; // Default score
            return $results;
        }
        
        // Generate comparative analysis
        $results['comparative_analysis'] = $this->generateComparativeAnalysis($mainSiteData, $competitorsData);
        
        // Identify strengths and weaknesses
        $results['strengths_weaknesses'] = $this->identifyStrengthsWeaknesses($mainSiteData, $competitorsData);
        
        // Generate opportunities
        $results['opportunities'] = $this->generateOpportunities($mainSiteData, $competitorsData);
        
        // Analyze keyword gap
        $results['keyword_gap'] = $this->analyzeKeywordGap($mainSiteData, $competitorsData);
        
        // Analyze content gap
        $results['content_gap'] = $this->analyzeContentGap($mainSiteData, $competitorsData);
        
        // Identify backlink opportunities
        $results['backlink_opportunities'] = $this->identifyBacklinkOpportunities($mainSiteData, $competitorsData);
        
        // Calculate overall score
        $results['overall_score'] = $this->calculateOverallScore($mainSiteData, $competitorsData);
        
        return $results;
    }
    
    /**
     * Generate comparative analysis
     * 
     * @param array $mainSiteData Main site data
     * @param array $competitorsData Competitors data
     * @return array Comparative analysis
     */
    private function generateComparativeAnalysis($mainSiteData, $competitorsData) {
        $comparativeAnalysis = [
            'metrics_comparison' => [],
            'avg_word_count' => 0,
            'avg_heading_count' => 0,
            'avg_image_count' => 0,
            'avg_backlinks' => 0,
            'avg_domain_authority' => 0
        ];
        
        // Calculate averages for competitors
        $totalWordCount = 0;
        $totalHeadingCount = 0;
        $totalImageCount = 0;
        $totalBacklinks = 0;
        $totalDomainAuthority = 0;
        
        foreach ($competitorsData as $competitor) {
            $totalWordCount += $competitor['page_data']['word_count'];
            $totalHeadingCount += $competitor['page_data']['heading_count'];
            $totalImageCount += $competitor['page_data']['image_count'];
            $totalBacklinks += $competitor['backlink_data']['total_backlinks'];
            $totalDomainAuthority += $competitor['backlink_data']['domain_authority'];
        }
        
        $competitorCount = count($competitorsData);
        
        $comparativeAnalysis['avg_word_count'] = round($totalWordCount / $competitorCount);
        $comparativeAnalysis['avg_heading_count'] = round($totalHeadingCount / $competitorCount);
        $comparativeAnalysis['avg_image_count'] = round($totalImageCount / $competitorCount);
        $comparativeAnalysis['avg_backlinks'] = round($totalBacklinks / $competitorCount);
        $comparativeAnalysis['avg_domain_authority'] = round($totalDomainAuthority / $competitorCount);
        
        // Generate metrics comparison
        $metricsComparison = [
            'word_count' => [
                'your_site' => $mainSiteData['page_data']['word_count'],
                'competitor_avg' => $comparativeAnalysis['avg_word_count'],
                'difference' => $mainSiteData['page_data']['word_count'] - $comparativeAnalysis['avg_word_count'],
                'difference_percent' => $comparativeAnalysis['avg_word_count'] > 0 ? 
                    round((($mainSiteData['page_data']['word_count'] - $comparativeAnalysis['avg_word_count']) / $comparativeAnalysis['avg_word_count']) * 100, 1) : 0
            ],
            'heading_count' => [
                'your_site' => $mainSiteData['page_data']['heading_count'],
                'competitor_avg' => $comparativeAnalysis['avg_heading_count'],
                'difference' => $mainSiteData['page_data']['heading_count'] - $comparativeAnalysis['avg_heading_count'],
                'difference_percent' => $comparativeAnalysis['avg_heading_count'] > 0 ? 
                    round((($mainSiteData['page_data']['heading_count'] - $comparativeAnalysis['avg_heading_count']) / $comparativeAnalysis['avg_heading_count']) * 100, 1) : 0
            ],
            'image_count' => [
                'your_site' => $mainSiteData['page_data']['image_count'],
                'competitor_avg' => $comparativeAnalysis['avg_image_count'],
                'difference' => $mainSiteData['page_data']['image_count'] - $comparativeAnalysis['avg_image_count'],
                'difference_percent' => $comparativeAnalysis['avg_image_count'] > 0 ? 
                    round((($mainSiteData['page_data']['image_count'] - $comparativeAnalysis['avg_image_count']) / $comparativeAnalysis['avg_image_count']) * 100, 1) : 0
            ],
            'backlinks' => [
                'your_site' => $mainSiteData['backlink_data']['total_backlinks'],
                'competitor_avg' => $comparativeAnalysis['avg_backlinks'],
                'difference' => $mainSiteData['backlink_data']['total_backlinks'] - $comparativeAnalysis['avg_backlinks'],
                'difference_percent' => $comparativeAnalysis['avg_backlinks'] > 0 ? 
                    round((($mainSiteData['backlink_data']['total_backlinks'] - $comparativeAnalysis['avg_backlinks']) / $comparativeAnalysis['avg_backlinks']) * 100, 1) : 0
            ],
            'domain_authority' => [
                'your_site' => $mainSiteData['backlink_data']['domain_authority'],
                'competitor_avg' => $comparativeAnalysis['avg_domain_authority'],
                'difference' => $mainSiteData['backlink_data']['domain_authority'] - $comparativeAnalysis['avg_domain_authority'],
                'difference_percent' => $comparativeAnalysis['avg_domain_authority'] > 0 ? 
                    round((($mainSiteData['backlink_data']['domain_authority'] - $comparativeAnalysis['avg_domain_authority']) / $comparativeAnalysis['avg_domain_authority']) * 100, 1) : 0
            ]
        ];
        
        $comparativeAnalysis['metrics_comparison'] = $metricsComparison;
        
        return $comparativeAnalysis;
    }
    
    /**
     * Identify strengths and weaknesses
     * 
     * @param array $mainSiteData Main site data
     * @param array $competitorsData Competitors data
     * @return array Strengths and weaknesses
     */
    private function identifyStrengthsWeaknesses($mainSiteData, $competitorsData) {
        $strengths = [];
        $weaknesses = [];
        
        // Calculate averages for competitors
        $avgWordCount = 0;
        $avgHeadingCount = 0;
        $avgImageCount = 0;
        $avgBacklinks = 0;
        $avgDomainAuthority = 0;
        
        foreach ($competitorsData as $competitor) {
            $avgWordCount += $competitor['page_data']['word_count'];
            $avgHeadingCount += $competitor['page_data']['heading_count'];
            $avgImageCount += $competitor['page_data']['image_count'];
            $avgBacklinks += $competitor['backlink_data']['total_backlinks'];
            $avgDomainAuthority += $competitor['backlink_data']['domain_authority'];
        }
        
        $competitorCount = count($competitorsData);
        
        $avgWordCount = round($avgWordCount / $competitorCount);
        $avgHeadingCount = round($avgHeadingCount / $competitorCount);
        $avgImageCount = round($avgImageCount / $competitorCount);
        $avgBacklinks = round($avgBacklinks / $competitorCount);
        $avgDomainAuthority = round($avgDomainAuthority / $competitorCount);
        
        // Compare content length
        if ($mainSiteData['page_data']['word_count'] >= $avgWordCount * 1.1) {
            $strengths[] = [
                'area' => 'Content Length',
                'description' => 'Your content is longer than the competitor average (' . $mainSiteData['page_data']['word_count'] . ' vs ' . $avgWordCount . ' words).',
                'impact' => 'medium'
            ];
        } else if ($mainSiteData['page_data']['word_count'] <= $avgWordCount * 0.9) {
            $weaknesses[] = [
                'area' => 'Content Length',
                'description' => 'Your content is shorter than the competitor average (' . $mainSiteData['page_data']['word_count'] . ' vs ' . $avgWordCount . ' words).',
                'impact' => 'medium'
            ];
        }
        
        // Compare heading usage
        if ($mainSiteData['page_data']['heading_count'] >= $avgHeadingCount * 1.1) {
            $strengths[] = [
                'area' => 'Content Structure',
                'description' => 'Your content has more headings than competitors, which improves readability and SEO.',
                'impact' => 'low'
            ];
        } else if ($mainSiteData['page_data']['heading_count'] <= $avgHeadingCount * 0.8) {
            $weaknesses[] = [
                'area' => 'Content Structure',
                'description' => 'Your content has fewer headings than competitors, which may impact readability and SEO.',
                'impact' => 'low'
            ];
        }
        
        // Compare image usage
        if ($mainSiteData['page_data']['image_count'] >= $avgImageCount * 1.2) {
            $strengths[] = [
                'area' => 'Visual Content',
                'description' => 'Your page uses more images than competitors, improving engagement.',
                'impact' => 'low'
            ];
        } else if ($mainSiteData['page_data']['image_count'] <= $avgImageCount * 0.7) {
            $weaknesses[] = [
                'area' => 'Visual Content',
                'description' => 'Your page uses fewer images than competitors, potentially reducing engagement.',
                'impact' => 'low'
            ];
        }
        
        // Compare backlinks
        if ($mainSiteData['backlink_data']['total_backlinks'] >= $avgBacklinks * 1.1) {
            $strengths[] = [
                'area' => 'Backlink Profile',
                'description' => 'Your site has more backlinks than the competitor average.',
                'impact' => 'high'
            ];
        } else if ($mainSiteData['backlink_data']['total_backlinks'] <= $avgBacklinks * 0.7) {
            $weaknesses[] = [
                'area' => 'Backlink Profile',
                'description' => 'Your site has significantly fewer backlinks than competitors.',
                'impact' => 'high'
            ];
        }
        
        // Compare domain authority
        if ($mainSiteData['backlink_data']['domain_authority'] >= $avgDomainAuthority * 1.1) {
            $strengths[] = [
                'area' => 'Domain Authority',
                'description' => 'Your domain authority is higher than the competitor average.',
                'impact' => 'high'
            ];
        } else if ($mainSiteData['backlink_data']['domain_authority'] <= $avgDomainAuthority * 0.8) {
            $weaknesses[] = [
                'area' => 'Domain Authority',
                'description' => 'Your domain authority is lower than the competitor average.',
                'impact' => 'high'
            ];
        }
        
        // Check SEO elements
        $seoElementsComparison = $this->compareSeoElements($mainSiteData, $competitorsData);
        
        foreach ($seoElementsComparison as $element => $comparison) {
            if ($comparison['status'] === 'better') {
                $strengths[] = [
                    'area' => 'On-Page SEO',
                    'description' => $comparison['description'],
                    'impact' => 'medium'
                ];
            } else if ($comparison['status'] === 'worse') {
                $weaknesses[] = [
                    'area' => 'On-Page SEO',
                    'description' => $comparison['description'],
                    'impact' => 'medium'
                ];
            }
        }
        
        return [
            'strengths' => $strengths,
            'weaknesses' => $weaknesses
        ];
    }
    
    /**
     * Compare SEO elements between main site and competitors
     * 
     * @param array $mainSiteData Main site data
     * @param array $competitorsData Competitors data
     * @return array SEO elements comparison
     */
    private function compareSeoElements($mainSiteData, $competitorsData) {
        $elementsComparison = [];
        
        // Check title tag and meta description
        $titleCount = 0;
        $descriptionCount = 0;
        $h1Count = 0;
        $schemaCount = 0;
        $canonicalCount = 0;
        
        foreach ($competitorsData as $competitor) {
            if ($competitor['seo_elements']['title_tag']) $titleCount++;
            if ($competitor['seo_elements']['meta_description']) $descriptionCount++;
            if ($competitor['seo_elements']['h1_tag']) $h1Count++;
            if ($competitor['seo_elements']['schema_markup']) $schemaCount++;
            if ($competitor['seo_elements']['canonical_tag']) $canonicalCount++;
        }
        
        $competitorCount = count($competitorsData);
        
        // Title tag comparison
        if ($mainSiteData['seo_elements']['title_tag'] && $titleCount < $competitorCount) {
            $elementsComparison['title_tag'] = [
                'status' => 'better',
                'description' => 'Your site has a proper title tag, while some competitors do not.'
            ];
        } else if (!$mainSiteData['seo_elements']['title_tag'] && $titleCount > 0) {
            $elementsComparison['title_tag'] = [
                'status' => 'worse',
                'description' => 'Your site is missing a title tag, while competitors have them.'
            ];
        } else {
            $elementsComparison['title_tag'] = [
                'status' => 'same',
                'description' => 'Your title tag implementation is similar to competitors.'
            ];
        }
        
        // Meta description comparison
        if ($mainSiteData['seo_elements']['meta_description'] && $descriptionCount < $competitorCount) {
            $elementsComparison['meta_description'] = [
                'status' => 'better',
                'description' => 'Your site has a meta description, while some competitors do not.'
            ];
        } else if (!$mainSiteData['seo_elements']['meta_description'] && $descriptionCount > 0) {
            $elementsComparison['meta_description'] = [
                'status' => 'worse',
                'description' => 'Your site is missing a meta description, while competitors have them.'
            ];
        } else {
            $elementsComparison['meta_description'] = [
                'status' => 'same',
                'description' => 'Your meta description implementation is similar to competitors.'
            ];
        }
        
        // H1 tag comparison
        if ($mainSiteData['seo_elements']['h1_tag'] && $h1Count < $competitorCount) {
            $elementsComparison['h1_tag'] = [
                'status' => 'better',
                'description' => 'Your site has an H1 tag, while some competitors do not.'
            ];
        } else if (!$mainSiteData['seo_elements']['h1_tag'] && $h1Count > 0) {
            $elementsComparison['h1_tag'] = [
                'status' => 'worse',
                'description' => 'Your site is missing an H1 tag, while competitors have them.'
            ];
        } else {
            $elementsComparison['h1_tag'] = [
                'status' => 'same',
                'description' => 'Your H1 tag implementation is similar to competitors.'
            ];
        }
        
        // Schema markup comparison
        if ($mainSiteData['seo_elements']['schema_markup'] && $schemaCount < $competitorCount) {
            $elementsComparison['schema_markup'] = [
                'status' => 'better',
                'description' => 'Your site uses schema markup, while some competitors do not.'
            ];
        } else if (!$mainSiteData['seo_elements']['schema_markup'] && $schemaCount > 0) {
            $elementsComparison['schema_markup'] = [
                'status' => 'worse',
                'description' => 'Your site does not use schema markup, while competitors do.'
            ];
        } else {
            $elementsComparison['schema_markup'] = [
                'status' => 'same',
                'description' => 'Your schema markup implementation is similar to competitors.'
            ];
        }
        
        return $elementsComparison;
    }
    
    /**
     * Generate opportunities based on comparison
     * 
     * @param array $mainSiteData Main site data
     * @param array $competitorsData Competitors data
     * @return array Opportunities
     */
    private function generateOpportunities($mainSiteData, $competitorsData) {
        $opportunities = [];
        
        // Calculate averages for competitors
        $avgWordCount = 0;
        $avgBacklinks = 0;
        $avgDomainAuthority = 0;
        
        foreach ($competitorsData as $competitor) {
            $avgWordCount += $competitor['page_data']['word_count'];
            $avgBacklinks += $competitor['backlink_data']['total_backlinks'];
            $avgDomainAuthority += $competitor['backlink_data']['domain_authority'];
        }
        
        $competitorCount = count($competitorsData);
        
        $avgWordCount = round($avgWordCount / $competitorCount);
        $avgBacklinks = round($avgBacklinks / $competitorCount);
        $avgDomainAuthority = round($avgDomainAuthority / $competitorCount);
        
        // Content length opportunity
        if ($mainSiteData['page_data']['word_count'] < $avgWordCount * 0.9) {
            $opportunities[] = [
                'category' => 'Content',
                'title' => 'Increase content length',
                'description' => 'Your content is ' . $mainSiteData['page_data']['word_count'] . ' words, while competitors average ' . $avgWordCount . ' words. Consider expanding your content to match or exceed the competitor average.',
                'priority' => 'medium',
                'difficulty' => 'low'
            ];
        }
        
        // Backlink opportunities
        if ($mainSiteData['backlink_data']['total_backlinks'] < $avgBacklinks * 0.7) {
            $opportunities[] = [
                'category' => 'Off-Page SEO',
                'title' => 'Build more backlinks',
                'description' => 'Your site has ' . $mainSiteData['backlink_data']['total_backlinks'] . ' backlinks, compared to the competitor average of ' . $avgBacklinks . '. Focus on building more high-quality backlinks to improve your authority.',
                'priority' => 'high',
                'difficulty' => 'high'
            ];
        }
        
        // Schema markup opportunity
        if (!$mainSiteData['seo_elements']['schema_markup']) {
            $schemaCount = 0;
            foreach ($competitorsData as $competitor) {
                if ($competitor['seo_elements']['schema_markup']) $schemaCount++;
            }
            
            if ($schemaCount > 0) {
                $opportunities[] = [
                    'category' => 'Technical SEO',
                    'title' => 'Implement schema markup',
                    'description' => $schemaCount . ' out of ' . $competitorCount . ' competitors use schema markup. Adding schema markup can improve how search engines understand your content and enable rich snippets in search results.',
                    'priority' => 'medium',
                    'difficulty' => 'medium'
                ];
            }
        }
        
        // Meta description opportunity
        if (!$mainSiteData['seo_elements']['meta_description']) {
            $descriptionCount = 0;
            foreach ($competitorsData as $competitor) {
                if ($competitor['seo_elements']['meta_description']) $descriptionCount++;
            }
            
            if ($descriptionCount > 0) {
                $opportunities[] = [
                    'category' => 'On-Page SEO',
                    'title' => 'Add meta description',
                    'description' => 'Your site is missing a meta description, while ' . $descriptionCount . ' out of ' . $competitorCount . ' competitors have one. Adding a compelling meta description can improve click-through rates from search results.',
                    'priority' => 'high',
                    'difficulty' => 'low'
                ];
            }
        }
        
        // Image opportunity
        $avgImageCount = 0;
        foreach ($competitorsData as $competitor) {
            $avgImageCount += $competitor['page_data']['image_count'];
        }
        $avgImageCount = round($avgImageCount / $competitorCount);
        
        if ($mainSiteData['page_data']['image_count'] < $avgImageCount * 0.7) {
            $opportunities[] = [
                'category' => 'Content',
                'title' => 'Add more visual content',
                'description' => 'Your page has ' . $mainSiteData['page_data']['image_count'] . ' images, while competitors average ' . $avgImageCount . '. Consider adding more relevant images, infographics, or videos to improve engagement.',
                'priority' => 'low',
                'difficulty' => 'low'
            ];
        }
        
        return $opportunities;
    }
    
    /**
     * Analyze keyword gap
     * 
     * @param array $mainSiteData Main site data
     * @param array $competitorsData Competitors data
     * @return array Keyword gap analysis
     */
    private function analyzeKeywordGap($mainSiteData, $competitorsData) {
        // Extract keywords from main site
        $mainSiteKeywords = [];
        foreach ($mainSiteData['keywords'] as $keyword) {
            $mainSiteKeywords[$keyword['keyword']] = $keyword;
        }
        
        // Collect all competitor keywords
        $competitorKeywords = [];
        
        foreach ($competitorsData as $competitorIndex => $competitor) {
            foreach ($competitor['keywords'] as $keyword) {
                $keywordText = $keyword['keyword'];
                
                if (!isset($competitorKeywords[$keywordText])) {
                    $competitorKeywords[$keywordText] = [
                        'keyword' => $keywordText,
                        'count' => 1,
                        'avg_frequency' => $keyword['frequency'],
                        'competitors' => [$competitorIndex]
                    ];
                } else {
                    $competitorKeywords[$keywordText]['count']++;
                    $competitorKeywords[$keywordText]['avg_frequency'] += $keyword['frequency'];
                    $competitorKeywords[$keywordText]['competitors'][] = $competitorIndex;
                }
            }
        }
        
        // Calculate average frequency
        foreach ($competitorKeywords as $keyword => $data) {
            $competitorKeywords[$keyword]['avg_frequency'] = round($data['avg_frequency'] / $data['count']);
        }
        
        // Find keywords that competitors use but main site doesn't
        $gapKeywords = [];
        
        foreach ($competitorKeywords as $keyword => $data) {
            if (!isset($mainSiteKeywords[$keyword]) && $data['count'] >= 2) {
                $gapKeywords[] = [
                    'keyword' => $keyword,
                    'competitor_count' => $data['count'],
                    'avg_frequency' => $data['avg_frequency'],
                    'opportunity_score' => min(100, $data['count'] * 25 + $data['avg_frequency'] / 2)
                ];
            }
        }
        
        // Sort by opportunity score
        usort($gapKeywords, function($a, $b) {
            return $b['opportunity_score'] <=> $a['opportunity_score'];
        });
        
        // Take top 10
        $gapKeywords = array_slice($gapKeywords, 0, 10);
        
        return [
            'gap_keywords' => $gapKeywords,
            'competitor_keywords_count' => count($competitorKeywords),
            'your_site_keywords_count' => count($mainSiteKeywords),
            'opportunity_count' => count($gapKeywords)
        ];
    }
    
    /**
     * Analyze content gap
     * 
     * @param array $mainSiteData Main site data
     * @param array $competitorsData Competitors data
     * @return array Content gap analysis
     */
    private function analyzeContentGap($mainSiteData, $competitorsData) {
        // Extract content topics from main site
        $mainSiteTopics = $mainSiteData['content_topics'];
        
        // Collect all competitor topics
        $competitorTopics = [];
        
        foreach ($competitorsData as $competitorIndex => $competitor) {
            foreach ($competitor['content_topics'] as $topic) {
                if (!isset($competitorTopics[$topic])) {
                    $competitorTopics[$topic] = [
                        'topic' => $topic,
                        'count' => 1,
                        'competitors' => [$competitorIndex]
                    ];
                } else {
                    $competitorTopics[$topic]['count']++;
                    $competitorTopics[$topic]['competitors'][] = $competitorIndex;
                }
            }
        }
        
        // Find topics that competitors cover but main site doesn't
        $gapTopics = [];
        
        foreach ($competitorTopics as $topic => $data) {
            if (!in_array($topic, $mainSiteTopics) && $data['count'] >= 2) {
                $gapTopics[] = [
                    'topic' => $topic,
                    'competitor_count' => $data['count'],
                    'opportunity_score' => min(100, $data['count'] * 30)
                ];
            }
        }
        
        // Sort by opportunity score
        usort($gapTopics, function($a, $b) {
            return $b['opportunity_score'] <=> $a['opportunity_score'];
        });
        
        // Take top 5
        $gapTopics = array_slice($gapTopics, 0, 5);
        
        // Generate content suggestions
        $contentSuggestions = [];
				
		foreach ($gapTopics as $topic) {
			$topicName = $topic['topic']; // Extract the topic string from the array
			
			$suggestions = [
				'How to ' . $topicName,
				'Ultimate guide to ' . $topicName,
				$topicName . ' best practices',
				'Top 10 ' . $topicName . ' tips',
				$topicName . ' case study'
			];
			
			$contentSuggestions[] = [
				'topic' => $topic['topic'],
				'competitor_count' => $topic['competitor_count'],
				'opportunity_score' => $topic['opportunity_score'],
				'content_ideas' => $suggestions
			];
		}
        
        return [
            'gap_topics' => $gapTopics,
            'content_suggestions' => $contentSuggestions,
            'competitor_topics_count' => count($competitorTopics),
            'your_site_topics_count' => count($mainSiteTopics),
            'opportunity_count' => count($gapTopics)
        ];
    }
    
    /**
     * Identify backlink opportunities
     * 
     * @param array $mainSiteData Main site data
     * @param array $competitorsData Competitors data
     * @return array Backlink opportunities
     */
    private function identifyBacklinkOpportunities($mainSiteData, $competitorsData) {
        // In a real implementation, this would analyze backlink profiles of competitors
        // to identify domains linking to them but not to the main site
        
        // For demo purposes, generate simulated opportunities
        $opportunities = [];
        
        // Generate domain names based on keywords
        $keywords = [];
        foreach ($mainSiteData['keywords'] as $keyword) {
            $keywords[] = $keyword['keyword'];
        }
        
        // Add some industry-specific domains
        $domainTypes = ['blog', 'news', 'review', 'forum', 'directory'];
        
        for ($i = 0; $i < 5; $i++) {
            $keyword = $keywords[array_rand($keywords)];
            $domainType = $domainTypes[array_rand($domainTypes)];
            
            $domain = $keyword . '-' . $domainType . '.com';
            
            $opportunities[] = [
                'domain' => $domain,
                'domain_authority' => rand(20, 70),
                'linked_competitors' => rand(1, count($competitorsData)),
                'opportunity_score' => rand(50, 95)
            ];
        }
        
        // Sort by opportunity score
        usort($opportunities, function($a, $b) {
            return $b['opportunity_score'] <=> $a['opportunity_score'];
        });
        
        return [
            'opportunities' => $opportunities,
            'total_opportunities' => count($opportunities)
        ];
    }
    
    /**
     * Calculate overall score
     * 
     * @param array $mainSiteData Main site data
     * @param array $competitorsData Competitors data
     * @return int Overall score (0-100)
     */
    private function calculateOverallScore($mainSiteData, $competitorsData) {
        // Calculate scores for different areas
        $contentScore = $this->calculateContentScore($mainSiteData, $competitorsData);
        $seoScore = $this->calculateSeoScore($mainSiteData, $competitorsData);
        $backlinkScore = $this->calculateBacklinkScore($mainSiteData, $competitorsData);
        
        // Calculate weighted average
        $overallScore = ($contentScore * 0.3) + ($seoScore * 0.3) + ($backlinkScore * 0.4);
        
        return round($overallScore);
    }
    
    /**
     * Calculate content score
     * 
     * @param array $mainSiteData Main site data
     * @param array $competitorsData Competitors data
     * @return int Content score (0-100)
     */
    private function calculateContentScore($mainSiteData, $competitorsData) {
        // Calculate averages for competitors
        $avgWordCount = 0;
        $avgHeadingCount = 0;
        $avgImageCount = 0;
        
        foreach ($competitorsData as $competitor) {
            $avgWordCount += $competitor['page_data']['word_count'];
            $avgHeadingCount += $competitor['page_data']['heading_count'];
            $avgImageCount += $competitor['page_data']['image_count'];
        }
        
        $competitorCount = count($competitorsData);
        
        $avgWordCount = $avgWordCount / $competitorCount;
        $avgHeadingCount = $avgHeadingCount / $competitorCount;
        $avgImageCount = $avgImageCount / $competitorCount;
        
        // Calculate word count score (0-100)
        $wordCountRatio = $mainSiteData['page_data']['word_count'] / max(1, $avgWordCount);
        $wordCountScore = min(100, $wordCountRatio * 50);
        
        // Calculate heading count score (0-100)
        $headingCountRatio = $mainSiteData['page_data']['heading_count'] / max(1, $avgHeadingCount);
        $headingCountScore = min(100, $headingCountRatio * 50);
        
        // Calculate image count score (0-100)
        $imageCountRatio = $mainSiteData['page_data']['image_count'] / max(1, $avgImageCount);
        $imageCountScore = min(100, $imageCountRatio * 50);
        
        // Calculate weighted average
        $contentScore = ($wordCountScore * 0.5) + ($headingCountScore * 0.3) + ($imageCountScore * 0.2);
        
        return round($contentScore);
    }
    
    /**
     * Calculate SEO score
     * 
     * @param array $mainSiteData Main site data
     * @param array $competitorsData Competitors data
     * @return int SEO score (0-100)
     */
    private function calculateSeoScore($mainSiteData, $competitorsData) {
        // Base score for on-page SEO elements
        $seoScore = 0;
        
        // Title tag (20 points)
        if ($mainSiteData['seo_elements']['title_tag']) {
            $seoScore += 20;
        }
        
        // Meta description (15 points)
        if ($mainSiteData['seo_elements']['meta_description']) {
            $seoScore += 15;
        }
        
        // H1 tag (15 points)
        if ($mainSiteData['seo_elements']['h1_tag']) {
            $seoScore += 15;
        }
        
        // Image alt text (10 points)
        $imageCount = $mainSiteData['page_data']['image_count'];
        if ($imageCount > 0) {
            $altTextRatio = $mainSiteData['seo_elements']['image_alt_text'] / $imageCount;
            $seoScore += round($altTextRatio * 10);
        }
        
        // Schema markup (20 points)
        if ($mainSiteData['seo_elements']['schema_markup']) {
            $seoScore += 20;
        }
        
        // Canonical tag (10 points)
        if ($mainSiteData['seo_elements']['canonical_tag']) {
            $seoScore += 10;
        }
        
        // Compare with competitors for bonus/penalty
        $competitorSeoScores = [];
        
        foreach ($competitorsData as $competitor) {
            $competitorScore = 0;
            
            if ($competitor['seo_elements']['title_tag']) $competitorScore += 20;
            if ($competitor['seo_elements']['meta_description']) $competitorScore += 15;
            if ($competitor['seo_elements']['h1_tag']) $competitorScore += 15;
            
            $competitorImageCount = $competitor['page_data']['image_count'];
            if ($competitorImageCount > 0) {
                $competitorAltTextRatio = $competitor['seo_elements']['image_alt_text'] / $competitorImageCount;
                $competitorScore += round($competitorAltTextRatio * 10);
            }
            
            if ($competitor['seo_elements']['schema_markup']) $competitorScore += 20;
            if ($competitor['seo_elements']['canonical_tag']) $competitorScore += 10;
            
            $competitorSeoScores[] = $competitorScore;
        }
        
        // Calculate average competitor score
        $avgCompetitorScore = array_sum($competitorSeoScores) / count($competitorSeoScores);
        
        // Apply bonus/penalty based on comparison (max ±10 points)
        $scoreDiff = $seoScore - $avgCompetitorScore;
        $bonus = min(10, max(-10, $scoreDiff / 5));
        
        $seoScore += $bonus;
        
        // Ensure score is between 0 and 100
        return max(0, min(100, $seoScore));
    }
    
    /**
     * Calculate backlink score
     * 
     * @param array $mainSiteData Main site data
     * @param array $competitorsData Competitors data
     * @return int Backlink score (0-100)
     */
    private function calculateBacklinkScore($mainSiteData, $competitorsData) {
        // Calculate averages for competitors
        $avgBacklinks = 0;
        $avgDomainAuthority = 0;
        $avgReferringDomains = 0;
        
        foreach ($competitorsData as $competitor) {
            $avgBacklinks += $competitor['backlink_data']['total_backlinks'];
            $avgDomainAuthority += $competitor['backlink_data']['domain_authority'];
            $avgReferringDomains += $competitor['backlink_data']['referring_domains'];
        }
        
        $competitorCount = count($competitorsData);
        
        $avgBacklinks = $avgBacklinks / $competitorCount;
        $avgDomainAuthority = $avgDomainAuthority / $competitorCount;
        $avgReferringDomains = $avgReferringDomains / $competitorCount;
        
        // Calculate backlink count score (0-100)
        $backlinkRatio = $mainSiteData['backlink_data']['total_backlinks'] / max(1, $avgBacklinks);
        $backlinkScore = min(100, $backlinkRatio * 50);
        
        // Calculate domain authority score (0-100)
        $daRatio = $mainSiteData['backlink_data']['domain_authority'] / max(1, $avgDomainAuthority);
        $daScore = min(100, $daRatio * 50);
        
        // Calculate referring domains score (0-100)
        $refDomainsRatio = $mainSiteData['backlink_data']['referring_domains'] / max(1, $avgReferringDomains);
        $refDomainsScore = min(100, $refDomainsRatio * 50);
        
        // Calculate weighted average
        $backlinkScore = ($backlinkScore * 0.3) + ($daScore * 0.4) + ($refDomainsScore * 0.3);
        
        return round($backlinkScore);
    }
    
    /**
     * Extract domain from URL
     * 
     * @param string $url URL
     * @return string Domain
     */
    private function extractDomain($url) {
        $parsedUrl = parse_url($url);
        $domain = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
        
        // Remove www. if present
        if (substr($domain, 0, 4) === 'www.') {
            $domain = substr($domain, 4);
        }
        
        return $domain;
    }
}
