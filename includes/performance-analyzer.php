<?php
/**
 * performance-analyzer.php - Class to analyze Core Web Vitals and performance metrics
 * File path: includes/performance-analyzer.php
 */

class PerformanceAnalyzer {
    private $url;
    private $apiKey; // Google PageSpeed Insights API key (optional)
    private $timeout = 30; // Timeout in seconds

    /**
     * Constructor
     * 
     * @param string $url URL to analyze
     * @param string $apiKey Optional Google PageSpeed Insights API key
     */
    public function __construct($url, $apiKey = null) {
        $this->url = $url;
        $this->apiKey = $apiKey;
    }
    
    /**
     * Run performance analysis
     * 
     * @return array Analysis results
     */
    public function analyze() {
        // Measure local TTFB (Time to First Byte)
        $ttfb = $this->measureTTFB();
        
        // Get PageSpeed Insights data
        $pageSpeedData = $this->getPageSpeedInsights();
        
        // Combine data and calculate scores
        return $this->processResults($ttfb, $pageSpeedData);
    }
    
    /**
     * Measure TTFB (Time to First Byte)
     * 
     * @return float TTFB in milliseconds or null on failure
     */
    private function measureTTFB() {
        $start = microtime(true);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36');
        
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        if ($info['http_code'] >= 200 && $info['http_code'] < 300) {
            return round($info['starttransfer_time'] * 1000); // Convert to milliseconds
        }
        
        return null;
    }
    
    /**
     * Get data from Google PageSpeed Insights API
     * 
     * @return array|null PageSpeed Insights data or null on failure
     */
    private function getPageSpeedInsights() {
        $apiUrl = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=' . urlencode($this->url) . '&strategy=mobile';
        
        // Add API key if provided
        if ($this->apiKey) {
            $apiUrl .= '&key=' . $this->apiKey;
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300 && $response) {
            return json_decode($response, true);
        } else {
            // Fallback to estimate data
            return $this->getEstimatedData();
        }
    }
    
    /**
     * Generate estimated data when API is unavailable
     * 
     * @return array Estimated performance data
     */
    private function getEstimatedData() {
        // Use local measurements to estimate performance
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36');
        
        $startTime = microtime(true);
        $html = curl_exec($ch);
        $endTime = microtime(true);
        $info = curl_getinfo($ch);
        curl_close($ch);
        
        // Calculate load time
        $loadTime = ($endTime - $startTime) * 1000; // milliseconds
        
        // Estimate performance metrics
        $resources = $this->countResources($html);
        
        return [
            'loadingExperience' => [
                'metrics' => [
                    'LARGEST_CONTENTFUL_PAINT_MS' => [
                        'percentile' => round($loadTime * 0.8)
                    ],
                    'FIRST_INPUT_DELAY_MS' => [
                        'percentile' => 50 // Default estimate
                    ],
                    'CUMULATIVE_LAYOUT_SHIFT_SCORE' => [
                        'percentile' => 0.25 // Default estimate
                    ]
                ]
            ],
            'lighthouseResult' => [
                'categories' => [
                    'performance' => [
                        'score' => $this->estimatePerformanceScore($loadTime, $resources)
                    ]
                ],
                'audits' => [
                    'server-response-time' => [
                        'displayValue' => round($info['starttransfer_time'] * 1000) . ' ms',
                        'numericValue' => round($info['starttransfer_time'] * 1000)
                    ],
                    'total-blocking-time' => [
                        'displayValue' => round($loadTime * 0.2) . ' ms',
                        'numericValue' => round($loadTime * 0.2)
                    ],
                    'largest-contentful-paint' => [
                        'displayValue' => round($loadTime * 0.8) . ' ms',
                        'numericValue' => round($loadTime * 0.8)
                    ],
                    'cumulative-layout-shift' => [
                        'displayValue' => '0.25',
                        'numericValue' => 0.25
                    ],
                    'first-contentful-paint' => [
                        'displayValue' => round($loadTime * 0.4) . ' ms',
                        'numericValue' => round($loadTime * 0.4)
                    ]
                ]
            ],
            'estimated' => true
        ];
    }
    
    /**
     * Count resources in HTML
     * 
     * @param string $html HTML content
     * @return array Resource counts
     */
    private function countResources($html) {
        $result = [
            'scripts' => 0,
            'styles' => 0,
            'images' => 0,
            'total' => 0
        ];
        
        if (!$html) {
            return $result;
        }
        
        // Create DOMDocument
        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        
        // Count scripts
        $scripts = $doc->getElementsByTagName('script');
        $result['scripts'] = $scripts->length;
        
        // Count styles
        $styles = $doc->getElementsByTagName('link');
        foreach ($styles as $style) {
            if ($style->getAttribute('rel') === 'stylesheet') {
                $result['styles']++;
            }
        }
        
        // Count images
        $images = $doc->getElementsByTagName('img');
        $result['images'] = $images->length;
        
        // Calculate total
        $result['total'] = $result['scripts'] + $result['styles'] + $result['images'];
        
        return $result;
    }
    
    /**
     * Estimate performance score based on load time and resources
     * 
     * @param float $loadTime Load time in milliseconds
     * @param array $resources Resource counts
     * @return float Score between 0 and 1
     */
    private function estimatePerformanceScore($loadTime, $resources) {
        // Calculate score components
        $loadTimeScore = $this->calculateLoadTimeScore($loadTime);
        $resourceScore = $this->calculateResourceScore($resources['total']);
        
        // Combine scores with weights
        $score = ($loadTimeScore * 0.7) + ($resourceScore * 0.3);
        
        // Ensure score is between 0 and 1
        return max(0, min(1, $score));
    }
    
    /**
     * Calculate load time score component
     * 
     * @param float $loadTime Load time in milliseconds
     * @return float Score between 0 and 1
     */
    private function calculateLoadTimeScore($loadTime) {
        if ($loadTime <= 1000) {
            return 1.0; // Excellent
        } else if ($loadTime <= 2500) {
            return 0.9 - (($loadTime - 1000) / 15000); // Good
        } else if ($loadTime <= 4000) {
            return 0.7 - (($loadTime - 2500) / 15000); // Needs Improvement
        } else {
            return max(0.1, 0.5 - (($loadTime - 4000) / 20000)); // Poor
        }
    }
    
    /**
     * Calculate resource score component
     * 
     * @param int $totalResources Total number of resources
     * @return float Score between 0 and 1
     */
    private function calculateResourceScore($totalResources) {
        if ($totalResources <= 20) {
            return 1.0; // Excellent
        } else if ($totalResources <= 50) {
            return 0.9 - (($totalResources - 20) / 300); // Good
        } else if ($totalResources <= 100) {
            return 0.7 - (($totalResources - 50) / 500); // Needs Improvement
        } else {
            return max(0.1, 0.5 - (($totalResources - 100) / 1000)); // Poor
        }
    }
    
    /**
     * Process and format results
     * 
     * @param float $ttfb TTFB in milliseconds
     * @param array $pageSpeedData PageSpeed Insights data
     * @return array Formatted results
     */
    private function processResults($ttfb, $pageSpeedData) {
        $results = [
            'overall_score' => 0,
            'is_estimated' => isset($pageSpeedData['estimated']) && $pageSpeedData['estimated'],
            'metrics' => [],
            'scores' => [],
            'opportunities' => [],
            'diagnostics' => []
        ];
        
        // Extract metrics from PageSpeed data
        if ($pageSpeedData && isset($pageSpeedData['lighthouseResult'])) {
            // Performance score
            if (isset($pageSpeedData['lighthouseResult']['categories']['performance']['score'])) {
                $perfScore = $pageSpeedData['lighthouseResult']['categories']['performance']['score'];
                $results['scores']['performance'] = round($perfScore * 100);
                $results['overall_score'] = $results['scores']['performance'];
            }
            
            // Extract key metrics
            $audits = $pageSpeedData['lighthouseResult']['audits'] ?? [];
            
            // Core Web Vitals
            $lcpData = $audits['largest-contentful-paint'] ?? null;
            $clsData = $audits['cumulative-layout-shift'] ?? null;
            $tbtData = $audits['total-blocking-time'] ?? null;
            $fidData = isset($pageSpeedData['loadingExperience']['metrics']['FIRST_INPUT_DELAY_MS']) 
                       ? $pageSpeedData['loadingExperience']['metrics']['FIRST_INPUT_DELAY_MS'] 
                       : null;
            
            // Additional metrics
            $fcpData = $audits['first-contentful-paint'] ?? null;
            $ttfbData = $audits['server-response-time'] ?? null;
            
            // Store metrics
            $results['metrics'] = [
                'lcp' => [
                    'name' => 'Largest Contentful Paint (LCP)',
                    'value' => isset($lcpData['numericValue']) ? round($lcpData['numericValue']) : null,
                    'display_value' => $lcpData['displayValue'] ?? 'Unknown',
                    'score' => isset($lcpData['score']) ? round($lcpData['score'] * 100) : null,
                    'status' => $this->getLCPStatus(isset($lcpData['numericValue']) ? $lcpData['numericValue'] : null),
                    'description' => 'Measures loading performance. To provide a good user experience, LCP should occur within 2.5 seconds of when the page first starts loading.'
                ],
                'fid' => [
                    'name' => 'First Input Delay (FID)',
                    'value' => isset($fidData['percentile']) ? $fidData['percentile'] : null,
                    'display_value' => isset($fidData['percentile']) ? $fidData['percentile'] . ' ms' : 'Unknown',
                    'score' => isset($fidData['percentile']) ? $this->calculateFIDScore($fidData['percentile']) : null,
                    'status' => $this->getFIDStatus(isset($fidData['percentile']) ? $fidData['percentile'] : null),
                    'description' => 'Measures interactivity. To provide a good user experience, pages should have a FID of 100 milliseconds or less.'
                ],
                'cls' => [
                    'name' => 'Cumulative Layout Shift (CLS)',
                    'value' => isset($clsData['numericValue']) ? $clsData['numericValue'] : null,
                    'display_value' => $clsData['displayValue'] ?? 'Unknown',
                    'score' => isset($clsData['score']) ? round($clsData['score'] * 100) : null,
                    'status' => $this->getCLSStatus(isset($clsData['numericValue']) ? $clsData['numericValue'] : null),
                    'description' => 'Measures visual stability. To provide a good user experience, pages should maintain a CLS of 0.1 or less.'
                ],
                'ttfb' => [
                    'name' => 'Time to First Byte (TTFB)',
                    'value' => $ttfb ?? (isset($ttfbData['numericValue']) ? round($ttfbData['numericValue']) : null),
                    'display_value' => ($ttfb ?? (isset($ttfbData['numericValue']) ? round($ttfbData['numericValue']) : null)) . ' ms',
                    'score' => $this->calculateTTFBScore($ttfb ?? (isset($ttfbData['numericValue']) ? $ttfbData['numericValue'] : null)),
                    'status' => $this->getTTFBStatus($ttfb ?? (isset($ttfbData['numericValue']) ? $ttfbData['numericValue'] : null)),
                    'description' => 'Measures the time it takes for the server to respond to a request. A fast TTFB helps ensure a responsive user experience.'
                ],
                'tbt' => [
                    'name' => 'Total Blocking Time (TBT)',
                    'value' => isset($tbtData['numericValue']) ? round($tbtData['numericValue']) : null,
                    'display_value' => $tbtData['displayValue'] ?? 'Unknown',
                    'score' => isset($tbtData['score']) ? round($tbtData['score'] * 100) : null,
                    'status' => $this->getTBTStatus(isset($tbtData['numericValue']) ? $tbtData['numericValue'] : null),
                    'description' => 'Measures the total amount of time that a page is blocked from responding to user input, such as mouse clicks, screen taps, or keyboard presses.'
                ],
                'fcp' => [
                    'name' => 'First Contentful Paint (FCP)',
                    'value' => isset($fcpData['numericValue']) ? round($fcpData['numericValue']) : null,
                    'display_value' => $fcpData['displayValue'] ?? 'Unknown',
                    'score' => isset($fcpData['score']) ? round($fcpData['score'] * 100) : null,
                    'status' => $this->getFCPStatus(isset($fcpData['numericValue']) ? $fcpData['numericValue'] : null),
                    'description' => 'Measures the time from when the page starts loading to when any part of the page\'s content is rendered on the screen.'
                ]
            ];
            
            // Extract opportunities and diagnostics
            if (isset($pageSpeedData['lighthouseResult']['audits'])) {
                foreach ($pageSpeedData['lighthouseResult']['audits'] as $id => $audit) {
                    if (isset($audit['details']) && isset($audit['score']) && $audit['score'] < 1) {
                        if ($this->isOpportunity($id)) {
                            $results['opportunities'][] = [
                                'id' => $id,
                                'title' => $audit['title'] ?? $id,
                                'description' => $audit['description'] ?? '',
                                'score' => $audit['score'] ?? 0,
                                'display_value' => $audit['displayValue'] ?? ''
                            ];
                        } else if ($this->isDiagnostic($id)) {
                            $results['diagnostics'][] = [
                                'id' => $id,
                                'title' => $audit['title'] ?? $id,
                                'description' => $audit['description'] ?? '',
                                'score' => $audit['score'] ?? 0,
                                'display_value' => $audit['displayValue'] ?? ''
                            ];
                        }
                    }
                }
            }
        } else {
            // Use estimated or fallback data
            $results['is_estimated'] = true;
            
            // If we have TTFB but no other data, estimate performance
            if ($ttfb) {
                $results['metrics']['ttfb'] = [
                    'name' => 'Time to First Byte (TTFB)',
                    'value' => $ttfb,
                    'display_value' => $ttfb . ' ms',
                    'score' => $this->calculateTTFBScore($ttfb),
                    'status' => $this->getTTFBStatus($ttfb),
                    'description' => 'Measures the time it takes for the server to respond to a request. A fast TTFB helps ensure a responsive user experience.'
                ];
                
                // Estimate performance score from TTFB
                $estimatedScore = max(0, min(100, 100 - ($ttfb / 30)));
                $results['overall_score'] = round($estimatedScore);
                $results['scores']['performance'] = $results['overall_score'];
                
                // Estimate other metrics based on TTFB
                $estimatedLCP = $ttfb * 3; // Rough estimate
                $results['metrics']['lcp'] = [
                    'name' => 'Largest Contentful Paint (LCP)',
                    'value' => $estimatedLCP,
                    'display_value' => $estimatedLCP . ' ms (estimated)',
                    'score' => $this->calculateLCPScore($estimatedLCP),
                    'status' => $this->getLCPStatus($estimatedLCP),
                    'description' => 'Measures loading performance. To provide a good user experience, LCP should occur within 2.5 seconds of when the page first starts loading.'
                ];
                
                // Default values for other metrics
                $results['metrics']['fid'] = [
                    'name' => 'First Input Delay (FID)',
                    'value' => 50, // Default estimate
                    'display_value' => '50 ms (estimated)',
                    'score' => 90,
                    'status' => 'good',
                    'description' => 'Measures interactivity. To provide a good user experience, pages should have a FID of 100 milliseconds or less.'
                ];
                
                $results['metrics']['cls'] = [
                    'name' => 'Cumulative Layout Shift (CLS)',
                    'value' => 0.25, // Default estimate
                    'display_value' => '0.25 (estimated)',
                    'score' => 50,
                    'status' => 'needs-improvement',
                    'description' => 'Measures visual stability. To provide a good user experience, pages should maintain a CLS of 0.1 or less.'
                ];
            } else {
                // No data available
                $results['overall_score'] = 0;
                $results['scores']['performance'] = 0;
                $results['error'] = 'Could not retrieve performance data';
            }
        }
        
        // Generate recommendations based on results
        $results['recommendations'] = $this->generateRecommendations($results);
        
        return $results;
    }
    
    /**
     * Calculate LCP score
     * 
     * @param float $lcp LCP in milliseconds
     * @return int Score between 0 and 100
     */
    private function calculateLCPScore($lcp) {
        if (!$lcp) return null;
        
        if ($lcp <= 2500) {
            return 100; // Good
        } else if ($lcp <= 4000) {
            // Scale from 90 to 50 between 2500ms and 4000ms
            return 90 - (($lcp - 2500) / 1500 * 40);
        } else {
            // Scale from 50 to 0 between 4000ms and 8000ms
            return max(0, 50 - (($lcp - 4000) / 4000 * 50));
        }
    }
    
    /**
     * Calculate FID score
     * 
     * @param float $fid FID in milliseconds
     * @return int Score between 0 and 100
     */
    private function calculateFIDScore($fid) {
        if (!$fid) return null;
        
        if ($fid <= 100) {
            return 100; // Good
        } else if ($fid <= 300) {
            // Scale from 90 to 50 between 100ms and 300ms
            return 90 - (($fid - 100) / 200 * 40);
        } else {
            // Scale from 50 to 0 between 300ms and 600ms
            return max(0, 50 - (($fid - 300) / 300 * 50));
        }
    }
    
    /**
     * Calculate TTFB score
     * 
     * @param float $ttfb TTFB in milliseconds
     * @return int Score between 0 and 100
     */
    private function calculateTTFBScore($ttfb) {
        if (!$ttfb) return null;
        
        if ($ttfb <= 200) {
            return 100; // Excellent
        } else if ($ttfb <= 500) {
            // Scale from 90 to 70 between 200ms and 500ms
            return 90 - (($ttfb - 200) / 300 * 20);
        } else if ($ttfb <= 1000) {
            // Scale from 70 to 50 between 500ms and 1000ms
            return 70 - (($ttfb - 500) / 500 * 20);
        } else {
            // Scale from 50 to 0 between 1000ms and 3000ms
            return max(0, 50 - (($ttfb - 1000) / 2000 * 50));
        }
    }
    
    /**
     * Get LCP status
     * 
     * @param float $lcp LCP in milliseconds
     * @return string Status (good, needs-improvement, poor)
     */
    private function getLCPStatus($lcp) {
        if (!$lcp) return 'unknown';
        
        if ($lcp <= 2500) {
            return 'good';
        } else if ($lcp <= 4000) {
            return 'needs-improvement';
        } else {
            return 'poor';
        }
    }
    
    /**
     * Get FID status
     * 
     * @param float $fid FID in milliseconds
     * @return string Status (good, needs-improvement, poor)
     */
    private function getFIDStatus($fid) {
        if (!$fid) return 'unknown';
        
        if ($fid <= 100) {
            return 'good';
        } else if ($fid <= 300) {
            return 'needs-improvement';
        } else {
            return 'poor';
        }
    }
    
    /**
     * Get CLS status
     * 
     * @param float $cls CLS value
     * @return string Status (good, needs-improvement, poor)
     */
    private function getCLSStatus($cls) {
        if (!$cls && $cls !== 0) return 'unknown';
        
        if ($cls <= 0.1) {
            return 'good';
        } else if ($cls <= 0.25) {
            return 'needs-improvement';
        } else {
            return 'poor';
        }
    }
    
    /**
     * Get TTFB status
     * 
     * @param float $ttfb TTFB in milliseconds
     * @return string Status (good, needs-improvement, poor)
     */
    private function getTTFBStatus($ttfb) {
        if (!$ttfb) return 'unknown';
        
        if ($ttfb <= 200) {
            return 'good';
        } else if ($ttfb <= 500) {
            return 'good';
        } else if ($ttfb <= 1000) {
            return 'needs-improvement';
        } else {
            return 'poor';
        }
    }
    
    /**
     * Get TBT status
     * 
     * @param float $tbt TBT in milliseconds
     * @return string Status (good, needs-improvement, poor)
     */
    private function getTBTStatus($tbt) {
        if (!$tbt) return 'unknown';
        
        if ($tbt <= 200) {
            return 'good';
        } else if ($tbt <= 600) {
            return 'needs-improvement';
        } else {
            return 'poor';
        }
    }
    
    /**
     * Get FCP status
     * 
     * @param float $fcp FCP in milliseconds
     * @return string Status (good, needs-improvement, poor)
     */
    private function getFCPStatus($fcp) {
        if (!$fcp) return 'unknown';
        
        if ($fcp <= 1800) {
            return 'good';
        } else if ($fcp <= 3000) {
            return 'needs-improvement';
        } else {
            return 'poor';
        }
    }
    
    /**
     * Check if audit is an opportunity
     * 
     * @param string $auditId Audit ID
     * @return bool True if it's an opportunity
     */
    private function isOpportunity($auditId) {
        $opportunities = [
            'render-blocking-resources',
            'unminified-css',
            'unminified-javascript',
            'unused-css-rules',
            'unused-javascript',
            'offscreen-images',
            'uses-responsive-images',
            'uses-optimized-images',
            'uses-webp-images',
            'uses-text-compression',
            'uses-rel-preconnect',
            'server-response-time',
            'redirects',
            'uses-rel-preload',
            'uses-http2',
            'efficient-animated-content',
            'preload-lcp-image',
            'third-party-summary',
            'third-party-facades',
            'legacy-javascript',
            'duplicated-javascript'
        ];
        
        return in_array($auditId, $opportunities);
    }
    
    /**
     * Check if audit is a diagnostic
     * 
     * @param string $auditId Audit ID
     * @return bool True if it's a diagnostic
     */
    private function isDiagnostic($auditId) {
        $diagnostics = [
            'dom-size',
            'critical-request-chains',
            'user-timings',
            'bootup-time',
            'mainthread-work-breakdown',
            'font-display',
            'resource-summary',
            'third-party-summary',
            'largest-contentful-paint-element',
            'layout-shifts',
            'long-tasks',
            'non-composited-animations',
            'unsized-images',
            'viewport',
            'network-requests',
            'network-rtt',
            'network-server-latency',
            'total-byte-weight',
            'no-document-write',
            'uses-passive-event-listeners'
        ];
        
        return in_array($auditId, $diagnostics);
    }
    
    /**
     * Generate recommendations based on performance analysis
     * 
     * @param array $results Analysis results
     * @return array Recommendations
     */
    private function generateRecommendations($results) {
        $recommendations = [];
        
        // Check Core Web Vitals and generate recommendations
        foreach ($results['metrics'] as $key => $metric) {
            if (isset($metric['status']) && $metric['status'] !== 'good' && $metric['status'] !== 'unknown') {
                $priority = $metric['status'] === 'poor' ? 'high' : 'medium';
                
                switch ($key) {
                    case 'lcp':
                        $recommendations[] = [
                            'title' => 'Improve Largest Contentful Paint (LCP)',
                            'description' => 'Your LCP is ' . $metric['display_value'] . ', which is ' . $metric['status'] . '. LCP measures loading performance.',
                            'priority' => $priority,
                            'actions' => [
                                'Optimize and compress images',
                                'Implement proper caching',
                                'Reduce server response time',
                                'Eliminate render-blocking resources',
                                'Optimize critical rendering path'
                            ]
                        ];
                        break;
                    
                    case 'fid':
                        $recommendations[] = [
                            'title' => 'Improve First Input Delay (FID)',
                            'description' => 'Your FID is ' . $metric['display_value'] . ', which is ' . $metric['status'] . '. FID measures interactivity.',
                            'priority' => $priority,
                            'actions' => [
                                'Break up long tasks',
                                'Optimize JavaScript execution',
                                'Remove unnecessary third-party scripts',
                                'Minimize main thread work',
                                'Keep request count low and transfer sizes small'
                            ]
                        ];
                        break;
                    
                    case 'cls':
                        $recommendations[] = [
                            'title' => 'Improve Cumulative Layout Shift (CLS)',
                            'description' => 'Your CLS is ' . $metric['display_value'] . ', which is ' . $metric['status'] . '. CLS measures visual stability.',
                            'priority' => $priority,
                            'actions' => [
                                'Include size attributes on images and videos',
                                'Reserve space for ads, embeds, and iframes',
                                'Avoid inserting new content above existing content',
                                'Preload fonts to prevent layout shifts',
                                'Minimize layout changes and reflows'
                            ]
                        ];
                        break;
                    
                    case 'ttfb':
                        $recommendations[] = [
                            'title' => 'Improve Time to First Byte (TTFB)',
                            'description' => 'Your TTFB is ' . $metric['display_value'] . ', which is ' . $metric['status'] . '. TTFB measures server response time.',
                            'priority' => $priority,
                            'actions' => [
                                'Optimize server configuration',
                                'Implement effective caching strategies',
                                'Use a Content Delivery Network (CDN)',
                                'Optimize database queries',
                                'Consider upgrading hosting if needed'
                            ]
                        ];
                        break;
                    
                    case 'tbt':
                        $recommendations[] = [
                            'title' => 'Reduce Total Blocking Time (TBT)',
                            'description' => 'Your TBT is ' . $metric['display_value'] . ', which is ' . $metric['status'] . '. TBT measures interactivity.',
                            'priority' => $priority,
                            'actions' => [
                                'Minimize main thread work',
                                'Reduce JavaScript execution time',
                                'Defer or remove non-critical third-party scripts',
                                'Use web workers for heavy computations',
                                'Optimize and split JavaScript bundles'
                            ]
                        ];
                        break;
                }
            }
        }
        
        // Add general recommendations if needed
        if (empty($recommendations)) {
            $recommendations[] = [
                'title' => 'Maintain good performance',
                'description' => 'Your Core Web Vitals are in good standing. Continue monitoring performance to maintain good user experience.',
                'priority' => 'low',
                'actions' => [
                    'Regularly monitor Core Web Vitals',
                    'Test performance on different devices and connections',
                    'Keep third-party scripts to a minimum',
                    'Continue optimizing images and assets',
                    'Consider implementing performance budgets'
                ]
            ];
        }
        
        // Add recommendations based on opportunities
        foreach ($results['opportunities'] as $opportunity) {
            $priority = $opportunity['score'] < 0.5 ? 'high' : 'medium';
            
            switch ($opportunity['id']) {
                case 'render-blocking-resources':
                    $recommendations[] = [
                        'title' => 'Eliminate render-blocking resources',
                        'description' => $opportunity['description'],
                        'priority' => $priority,
                        'actions' => [
                            'Inline critical CSS',
                            'Defer non-critical CSS',
                            'Defer or async JavaScript loading',
                            'Prioritize visible content'
                        ]
                    ];
                    break;
                
                case 'uses-optimized-images':
                case 'uses-webp-images':
                case 'offscreen-images':
                    $recommendations[] = [
                        'title' => 'Optimize images',
                        'description' => $opportunity['description'],
                        'priority' => $priority,
                        'actions' => [
                            'Compress images',
                            'Convert to modern formats (WebP, AVIF)',
                            'Implement lazy loading',
                            'Serve properly sized images'
                        ]
                    ];
                    break;
                
                case 'unminified-css':
                case 'unminified-javascript':
                case 'unused-css-rules':
                case 'unused-javascript':
                    $recommendations[] = [
                        'title' => 'Optimize CSS and JavaScript',
                        'description' => $opportunity['description'],
                        'priority' => $priority,
                        'actions' => [
                            'Minify CSS and JavaScript',
                            'Remove unused code',
                            'Implement code splitting',
                            'Optimize critical rendering path'
                        ]
                    ];
                    break;
                
                case 'uses-text-compression':
                    $recommendations[] = [
                        'title' => 'Enable text compression',
                        'description' => $opportunity['description'],
                        'priority' => $priority,
                        'actions' => [
                            'Enable Gzip or Brotli compression',
                            'Configure server settings',
                            'Verify compression is working properly'
                        ]
                    ];
                    break;
                
                case 'uses-rel-preconnect':
                case 'uses-rel-preload':
                    $recommendations[] = [
                        'title' => 'Use resource hints effectively',
                        'description' => $opportunity['description'],
                        'priority' => $priority,
                        'actions' => [
                            'Implement preconnect for important third-party origins',
                            'Preload critical resources',
                            'Consider prefetch for future navigation',
                            'Don\'t overuse resource hints'
                        ]
                    ];
                    break;
            }
        }
        
        // Remove duplicate recommendations
        $uniqueRecommendations = [];
        $titles = [];
        
        foreach ($recommendations as $recommendation) {
            if (!in_array($recommendation['title'], $titles)) {
                $titles[] = $recommendation['title'];
                $uniqueRecommendations[] = $recommendation;
            }
        }
        
        // Limit to top recommendations based on priority
        usort($uniqueRecommendations, function($a, $b) {
            $priorityOrder = ['high' => 0, 'medium' => 1, 'low' => 2];
            return $priorityOrder[$a['priority']] <=> $priorityOrder[$b['priority']];
        });
        
        return array_slice($uniqueRecommendations, 0, 5);
    }
}
