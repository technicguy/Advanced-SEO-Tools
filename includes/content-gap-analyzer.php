<?php
/**
 * content-gap-analyzer.php - Analyzes content gaps compared to competitors
 */

class ContentGapAnalyzer {
    /**
     * Analyze content gaps for a given URL
     * @param string $url Website URL to analyze
     * @param array $competitors List of competitor URLs
     * @return array Analysis results
     */
    public static function analyze($url, $competitors = []) {
        // Initialize results structure
        $results = [
            'overall_score' => 0,
            'topics_covered' => [],
            'missing_topics' => [],
            'competitor_topics' => [],
            'topic_distribution' => [],
            'content_length_comparison' => [],
            'content_depth_comparison' => [],
            'content_quality_comparison' => [],
            'keyword_gaps' => [],
            'opportunity_score' => 0,
            'recommendations' => []
        ];
        
        // If no competitors provided, try to identify them
        if (empty($competitors)) {
            $competitors = self::identifyCompetitors($url);
        }
        
        // Extract domain for API lookups
        $domain = parse_url($url, PHP_URL_HOST);
        if (substr($domain, 0, 4) === 'www.') {
            $domain = substr($domain, 4);
        }
        
        // Analyze content topics on the website
        $siteTopics = self::analyzeWebsiteTopics($url);
        $results['topics_covered'] = $siteTopics['topics'];
        
        // Analyze content length
        $contentLengthData = self::analyzeContentLength($url);
        $results['content_length_data'] = $contentLengthData;
        
        // Analyze competitor content
        $competitorData = [];
        $allCompetitorTopics = [];
        $competitorContentLengths = [];
        
        foreach ($competitors as $competitor) {
            // Analyze competitor topics
            $competitorTopics = self::analyzeWebsiteTopics($competitor);
            
            // Merge competitor topics into master list
            foreach ($competitorTopics['topics'] as $topic) {
                if (!in_array($topic, $allCompetitorTopics)) {
                    $allCompetitorTopics[] = $topic;
                }
            }
            
            // Analyze competitor content length
            $competitorContentLength = self::analyzeContentLength($competitor);
            $competitorContentLengths[$competitor] = $competitorContentLength['average_length'];
            
            // Store competitor data
            $competitorData[$competitor] = [
                'topics' => $competitorTopics['topics'],
                'average_content_length' => $competitorContentLength['average_length'],
                'content_depth_score' => rand(60, 90) // Simulated score
            ];
        }
        
        // Store competitor data in results
        $results['competitor_data'] = $competitorData;
        
        // Identify missing topics (topics covered by competitors but not by the website)
        $missingTopics = [];
        foreach ($allCompetitorTopics as $topic) {
            if (!in_array($topic, $results['topics_covered'])) {
                $missingTopics[] = [
                    'topic' => $topic,
                    'competitors_covering' => [],
                    'opportunity_score' => rand(60, 95) // Simulated score
                ];
                
                // Add which competitors cover this topic
                foreach ($competitorData as $competitorUrl => $data) {
                    if (in_array($topic, $data['topics'])) {
                        $missingTopics[count($missingTopics) - 1]['competitors_covering'][] = $competitorUrl;
                    }
                }
            }
        }
        
        // Sort missing topics by opportunity score
        usort($missingTopics, function($a, $b) {
            return $b['opportunity_score'] - $a['opportunity_score'];
        });
        
        $results['missing_topics'] = $missingTopics;
        
        // Compare content length with competitors
        $results['content_length_comparison'] = [
            'site' => $contentLengthData['average_length'],
            'competitors' => $competitorContentLengths,
            'average_competitor_length' => array_sum($competitorContentLengths) / count($competitorContentLengths),
            'length_gap_percentage' => 0 // Will calculate below
        ];
        
        // Calculate content length gap percentage
        $avgCompetitorLength = $results['content_length_comparison']['average_competitor_length'];
        if ($avgCompetitorLength > 0) {
            $lengthGapPercentage = (($avgCompetitorLength - $contentLengthData['average_length']) / $avgCompetitorLength) * 100;
            $results['content_length_comparison']['length_gap_percentage'] = round($lengthGapPercentage, 1);
        }
        
        // Analyze keyword gaps
        $keywordGaps = self::analyzeKeywordGaps($url, $competitors);
        $results['keyword_gaps'] = $keywordGaps;
        
        // Generate recommendations based on analysis
        $recommendations = [];
        
        // Add recommendation for each high-priority missing topic
        foreach (array_slice($missingTopics, 0, 3) as $topic) {
            $recommendations[] = [
                'title' => 'Create Content About "' . ucfirst($topic['topic']) . '"',
                'description' => 'Your competitors have content about this topic, but your site is missing it',
                'importance' => $topic['opportunity_score'] > 80 ? 'high' : 'medium',
                'implementation' => 'Create comprehensive content covering "' . $topic['topic'] . '" with at least ' . 
                                    round($avgCompetitorLength) . ' words to be competitive'
            ];
        }
        
        // Add content length recommendation if needed
        if ($results['content_length_comparison']['length_gap_percentage'] > 20) {
            $recommendations[] = [
                'title' => 'Increase Content Length',
                'description' => 'Your content is ' . round($results['content_length_comparison']['length_gap_percentage']) . 
                                '% shorter than your competitors on average',
                'importance' => 'medium',
                'implementation' => 'Expand your content to at least ' . round($avgCompetitorLength) . 
                                    ' words per page on average, focusing on adding valuable information rather than filler'
            ];
        }
        
        // Add keyword gap recommendations
        if (!empty($keywordGaps)) {
            $recommendations[] = [
                'title' => 'Target Missing Keywords',
                'description' => 'Your competitors are ranking for keywords that you\'re not targeting',
                'importance' => 'high',
                'implementation' => 'Create content targeting these keywords: ' . 
                                    implode(', ', array_slice(array_column($keywordGaps, 'keyword'), 0, 5))
            ];
        }
        
        $results['recommendations'] = $recommendations;
        
        // Calculate opportunity score based on number of missing topics and their importance
        $opportunityScore = min(100, count($missingTopics) * 10);
        $results['opportunity_score'] = $opportunityScore;
        
        // Calculate overall score (100 minus opportunity score, since more gaps means lower overall score)
        $results['overall_score'] = max(0, 100 - round($opportunityScore / 2));
        
        return $results;
    }
    
    /**
     * Identify competitors for a given URL
     * @param string $url Website URL
     * @return array List of competitor URLs
     */
    private static function identifyCompetitors($url) {
        // Simulate identifying competitors
        // In a real implementation, this would use a SERP API or Google Search Console data
        
        // Extract domain for API lookups
        $domain = parse_url($url, PHP_URL_HOST);
        if (substr($domain, 0, 4) === 'www.') {
            $domain = substr($domain, 4);
        }
        
        // Generate some fictional competitor domains
        $competitors = [
            'https://competitor1-' . $domain,
            'https://competitor2-' . $domain,
            'https://competitor3-' . $domain
        ];
        
        return $competitors;
    }
    
    /**
     * Analyze website topics
     * @param string $url Website URL
     * @return array Topics data
     */
    private static function analyzeWebsiteTopics($url) {
        // Simulate crawling a website and extracting topics
        // In a real implementation, this would crawl pages and analyze content
        
        // For demo purposes, generate random topics based on the URL
        $allPossibleTopics = [
            'product features', 'pricing', 'customer success stories', 
            'industry trends', 'how-to guides', 'comparisons', 
            'case studies', 'technical specifications', 'FAQs',
            'best practices', 'company history', 'team profiles',
            'events', 'webinars', 'research reports', 'tutorials',
            'industry statistics', 'buyer guides', 'product reviews',
            'troubleshooting', 'maintenance tips', 'industry news'
        ];
        
        // Select a random number of topics
        $numTopics = rand(8, 15);
        shuffle($allPossibleTopics);
        $topics = array_slice($allPossibleTopics, 0, $numTopics);
        
        return [
            'topics' => $topics,
            'page_count' => rand(10, 30),
            'topic_depth' => [] // Would contain topic-specific depth metrics
        ];
    }
    
    /**
     * Analyze content length
     * @param string $url Website URL
     * @return array Content length data
     */
    private static function analyzeContentLength($url) {
        // Simulate analyzing content length
        // In a real implementation, this would crawl pages and count words
        
        // For demo purposes, generate random content length metrics
        $averageLength = rand(600, 1500);
        
        return [
            'average_length' => $averageLength,
            'min_length' => round($averageLength * 0.5),
            'max_length' => round($averageLength * 1.5),
            'pages_below_threshold' => rand(1, 5)
        ];
    }
    
    /**
     * Analyze keyword gaps
     * @param string $url Website URL
     * @param array $competitors List of competitor URLs
     * @return array Keyword gaps data
     */
    private static function analyzeKeywordGaps($url, $competitors) {
        // Simulate analyzing keyword gaps
        // In a real implementation, this would use SEO API data
        
        // Generate random keyword gaps
        $possibleKeywords = [
            'industry solutions', 'best services', 'affordable options',
            'expert guidance', 'professional services', 'how to choose',
            'benefits of', 'top rated', 'near me', 'best in industry',
            'industry comparison', 'vs competitors', 'alternatives to',
            'industry trends', 'latest developments', 'industry awards'
        ];
        
        // Shuffle and take a random number
        shuffle($possibleKeywords);
        $numGaps = rand(3, 8);
        $keywordGaps = [];
        
        for ($i = 0; $i < $numGaps; $i++) {
            $keywordGaps[] = [
                'keyword' => $possibleKeywords[$i],
                'search_volume' => rand(100, 5000),
                'competition' => rand(1, 10) / 10,
                'ranking_competitors' => array_slice($competitors, 0, rand(1, count($competitors))),
                'best_competitor_rank' => rand(1, 10)
            ];
        }
        
        // Sort by search volume (highest first)
        usort($keywordGaps, function($a, $b) {
            return $b['search_volume'] - $a['search_volume'];
        });
        
        return $keywordGaps;
    }
}