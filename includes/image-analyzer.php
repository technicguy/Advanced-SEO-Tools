<?php
/**
 * Image Analyzer - Analyzes image optimization across a website
 * File path: includes/image-analyzer.php
 */

class ImageAnalyzer {
    private $url;
    private $maxPages;
    private $domain;
    private $visitedUrls = [];
    private $imageData = [];
    private $totalScore = 0;
    private $scoreComponents = [];
    
    /**
     * Constructor
     * 
     * @param string $url The starting URL to analyze
     * @param int $maxPages Maximum number of pages to analyze
     */
    public function __construct($url, $maxPages = 5) {
        $this->url = $url;
        $this->maxPages = $maxPages;
        
        // Extract domain from URL
        $parsedUrl = parse_url($url);
        $this->domain = $parsedUrl['host'];
        if (substr($this->domain, 0, 4) === 'www.') {
            $this->domain = substr($this->domain, 4);
        }
    }
    
    /**
     * Run the image analysis
     * 
     * @return array Analysis results
     */
    public function analyze() {
        // Initialize data
        $this->visitedUrls = [];
        $this->imageData = [];
        
        // Start with the main URL
        $this->analyzeImagesOnPage($this->url);
        
        // Find and analyze additional pages if needed
        $this->findAndAnalyzeAdditionalPages();
        
        // Calculate overall score
        $this->calculateOverallScore();
        
        // Generate recommendations
        $recommendations = $this->generateRecommendations();
        
        // Prepare summary
        $summary = $this->prepareSummary();
        
        return [
            'overall_score' => $this->totalScore,
            'score_components' => $this->scoreComponents,
            'summary' => $summary,
            'pages_analyzed' => count($this->visitedUrls),
            'total_images' => $this->getTotalImageCount(),
            'images_with_alt' => $this->getImagesWithAltCount(),
            'images_without_alt' => $this->getImagesWithoutAltCount(),
            'oversized_images' => $this->getOversizedImagesCount(),
            'lazy_loaded_images' => $this->getLazyLoadedImagesCount(),
            'responsive_images' => $this->getResponsiveImagesCount(),
            'modern_format_images' => $this->getModernFormatImagesCount(),
            'recommendations' => $recommendations,
            'image_details' => $this->imageData
        ];
    }
    
    /**
     * Analyze images on a specific page
     * 
     * @param string $pageUrl URL of the page to analyze
     * @return void
     */
    private function analyzeImagesOnPage($pageUrl) {
        // Skip if already visited or max pages reached
        if (in_array($pageUrl, $this->visitedUrls) || count($this->visitedUrls) >= $this->maxPages) {
            return;
        }
        
        // Mark as visited
        $this->visitedUrls[] = $pageUrl;
        
        // Fetch the page content
        $html = $this->fetchPageContent($pageUrl);
        if (!$html) {
            return;
        }
        
        // Parse HTML
        $dom = new DOMDocument();
        @$dom->loadHTML($html); // Suppress errors for invalid HTML
        $xpath = new DOMXPath($dom);
        
        // Find all images
        $images = $xpath->query('//img');
        $pageImageData = [
            'url' => $pageUrl,
            'images' => []
        ];
        
        foreach ($images as $img) {
            $imageData = $this->analyzeImageElement($img, $pageUrl);
            $pageImageData['images'][] = $imageData;
        }
        
        // Add to collected data
        $this->imageData[] = $pageImageData;
    }
    
    /**
     * Analyze a single image element
     * 
     * @param DOMElement $img The image DOM element
     * @param string $pageUrl URL of the page containing the image
     * @return array Image analysis data
     */
    private function analyzeImageElement($img, $pageUrl) {
	
	try {
        $src = $img->getAttribute('src');
		
        // Handle empty or data URI sources
        if (empty($src) || strpos($src, 'data:') === 0) {
            return $this->getDefaultImageData();
        }		
        
        // Resolve relative URLs
        if (strpos($src, 'http') !== 0) {
            if (strpos($src, '//') === 0) {
                // Protocol-relative URL
                $parsedPageUrl = parse_url($pageUrl);
                $src = $parsedPageUrl['scheme'] . ':' . $src;
            } elseif (strpos($src, '/') === 0) {
                // Absolute path
                $parsedPageUrl = parse_url($pageUrl);
                $src = $parsedPageUrl['scheme'] . '://' . $parsedPageUrl['host'] . $src;
            } else {
                // Relative path
                $src = rtrim($pageUrl, '/') . '/' . $src;
            }
        }
        
        // Check if it's a data URI
        $isDataUri = strpos($src, 'data:') === 0;
        
        // Get image properties if not a data URI
        $size = 0;
        $width = $img->getAttribute('width');
        $height = $img->getAttribute('height');
        $naturalWidth = 0;
        $naturalHeight = 0;
        $format = '';
        
        if (!$isDataUri) {
            $imageInfo = $this->getImageProperties($src);
            $size = $imageInfo['size'];
            $naturalWidth = $imageInfo['width'];
            $naturalHeight = $imageInfo['height'];
            $format = $imageInfo['format'];
        }
        
        // Check alt text
        $hasAlt = $img->hasAttribute('alt');
        $altText = $hasAlt ? $img->getAttribute('alt') : '';
        $altQuality = $this->evaluateAltTextQuality($altText);
        
        // Check lazy loading
        $isLazyLoaded = $img->hasAttribute('loading') && $img->getAttribute('loading') === 'lazy';
        $hasDataLazy = $img->hasAttribute('data-src') || $img->hasAttribute('data-lazy-src');
        $usesLazyLoading = $isLazyLoaded || $hasDataLazy;
        
        // Check responsive image implementation
        $hasSrcset = $img->hasAttribute('srcset');
        $hasSizes = $img->hasAttribute('sizes');
        $isResponsive = $hasSrcset || ($width && strpos($width, '%') !== false);
        
        // Check image dimensions
        $hasDimensions = ($width && $height) ? true : false;
        
        // Check if image is oversized
        $isOversized = ($size > 100000) ? true : false; // Over 100KB
        
        // Check if using modern format
        $isModernFormat = in_array(strtolower($format), ['webp', 'avif']);
        
        return [
            'src' => $src,
            'alt_text' => $altText,
            'has_alt' => $hasAlt,
            'alt_quality' => $altQuality,
            'size' => $size,
            'width' => $width ?: $naturalWidth,
            'height' => $height ?: $naturalHeight,
            'format' => $format,
            'has_dimensions' => $hasDimensions,
            'uses_lazy_loading' => $usesLazyLoading,
            'is_responsive' => $isResponsive,
            'is_oversized' => $isOversized,
            'is_modern_format' => $isModernFormat,
            'optimization_score' => $this->calculateImageScore([
                'has_alt' => $hasAlt,
                'alt_quality' => $altQuality,
                'has_dimensions' => $hasDimensions,
                'uses_lazy_loading' => $usesLazyLoading,
                'is_responsive' => $isResponsive,
                'is_oversized' => $isOversized,
                'is_modern_format' => $isModernFormat
            ])
        ];
		
    } catch (Exception $e) {
        error_log("Error analyzing image element: " . $e->getMessage());
        return $this->getDefaultImageData();
    }		
		
    }
	
private function getDefaultImageData() {
    return [
        'src' => '',
        'alt_text' => '',
        'has_alt' => false,
        'alt_quality' => 'poor',
        'size' => 0,
        'width' => 0,
        'height' => 0,
        'format' => '',
        'has_dimensions' => false,
        'uses_lazy_loading' => false,
        'is_responsive' => false,
        'is_oversized' => false,
        'is_modern_format' => false,
        'optimization_score' => 0
    ];
}	
	
    
    /**
     * Calculate score for a single image
     * 
     * @param array $metrics Image metrics
     * @return int Score (0-100)
     */
    private function calculateImageScore($metrics) {
        $score = 100;
        
        // Deduct points for missing optimizations
        if (!$metrics['has_alt']) {
            $score -= 25;
        } elseif ($metrics['alt_quality'] === 'poor') {
            $score -= 15;
        }
        
        if (!$metrics['has_dimensions']) {
            $score -= 15;
        }
        
        if (!$metrics['uses_lazy_loading']) {
            $score -= 10;
        }
        
        if (!$metrics['is_responsive']) {
            $score -= 15;
        }
        
        if ($metrics['is_oversized']) {
            $score -= 20;
        }
        
        if (!$metrics['is_modern_format']) {
            $score -= 15;
        }
        
        return max(0, $score);
    }
    
    /**
     * Evaluate the quality of alt text
     * 
     * @param string $altText The alt text to evaluate
     * @return string Quality rating (good, average, poor)
     */
    private function evaluateAltTextQuality($altText) {
        if (empty($altText)) {
            return 'poor';
        }
        
        // Too short alt text
        if (strlen($altText) < 5) {
            return 'poor';
        }
        
        // Good length alt text
        if (strlen($altText) >= 5 && strlen($altText) <= 100) {
            // Check if it's just a filename
            if (preg_match('/\.(jpg|jpeg|png|gif|webp|avif)$/i', $altText)) {
                return 'poor';
            }
            
            return 'good';
        }
        
        // Too long alt text
        return 'average';
    }
    
    /**
     * Get properties of an image
     * 
     * @param string $url URL of the image
     * @return array Image properties (size, width, height, format)
     */
/**
 * Get properties of an image
 * 
 * @param string $url URL of the image
 * @return array Image properties (size, width, height, format)
 */
private function getImageProperties($url) {
    $result = [
        'size' => 0,
        'width' => 0,
        'height' => 0,
        'format' => ''
    ];
    
    // Skip analysis for private IP addresses or localhost
    if (preg_match('/^https?:\/\/(localhost|127\.0\.0\.1|10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.|192\.168\.)/', $url)) {
        // Extract format from extension if possible
        if (preg_match('/\.([a-zA-Z0-9]+)(\?.*)?$/', $url, $matches)) {
            $result['format'] = strtolower($matches[1]);
        }
        return $result;
    }
    
    try {
        // Set a stream context with a timeout
        $context = stream_context_create([
            'http' => [
                'timeout' => 2, // 2 second timeout
                'user_agent' => 'Mozilla/5.0 (compatible; SEO-Analyzer/1.0)'
            ]
        ]);
        
        // Try to get headers with the context and suppress warnings
        $headers = @get_headers($url, 1, $context);
        
        if ($headers !== false && isset($headers['Content-Length'])) {
            $contentLength = $headers['Content-Length'];
            if (is_array($contentLength)) {
                // Sometimes Content-Length can be an array (for multiple responses)
                $contentLength = end($contentLength);
            }
            $result['size'] = intval($contentLength);
        }
        
        // Try to get image dimensions safely
        if (function_exists('curl_init')) {
            // Use cURL to get just enough data to determine image type and dimensions
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_TIMEOUT => 2,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; SEO-Analyzer/1.0)',
                CURLOPT_RANGE => '0-32768', // Get only the first 32KB, enough for most image headers
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            $data = curl_exec($ch);
            curl_close($ch);
            
            if ($data) {
                // Create a temp file to analyze the image
                $tmpFile = tempnam(sys_get_temp_dir(), 'img_');
                if ($tmpFile) {
                    file_put_contents($tmpFile, $data);
                    $imageInfo = @getimagesize($tmpFile);
                    unlink($tmpFile); // Clean up
                    
                    if ($imageInfo) {
                        $result['width'] = $imageInfo[0];
                        $result['height'] = $imageInfo[1];
                        
                        // Get format from image type
                        $mimeType = $imageInfo['mime'];
                        if (strpos($mimeType, 'image/') === 0) {
                            $result['format'] = substr($mimeType, 6);
                        }
                    }
                }
            }
        } else {
            // Extract format from extension if cURL isn't available
            if (preg_match('/\.([a-zA-Z0-9]+)(\?.*)?$/', $url, $matches)) {
                $result['format'] = strtolower($matches[1]);
            }
        }
    } catch (Exception $e) {
        // Log the error but don't let it stop the entire analysis
        error_log("Error getting image properties for {$url}: " . $e->getMessage());
    }
    
    return $result;
}
    
    /**
     * Find and analyze additional pages
     * 
     * @return void
     */
    private function findAndAnalyzeAdditionalPages() {
        $pagesToVisit = [];
        
        // Extract links from already visited pages
        foreach ($this->imageData as $pageData) {
            $html = $this->fetchPageContent($pageData['url']);
            if (!$html) {
                continue;
            }
            
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);
            
            // Find internal links
            $links = $xpath->query('//a[@href]');
            foreach ($links as $link) {
                $href = $link->getAttribute('href');
                
                // Filter internal links
                if ($this->isInternalLink($href, $pageData['url'])) {
                    $absoluteUrl = $this->getAbsoluteUrl($href, $pageData['url']);
                    if (!in_array($absoluteUrl, $this->visitedUrls) && !in_array($absoluteUrl, $pagesToVisit)) {
                        $pagesToVisit[] = $absoluteUrl;
                    }
                }
            }
        }
        
        // Visit additional pages up to max limit
        foreach ($pagesToVisit as $pageUrl) {
            if (count($this->visitedUrls) >= $this->maxPages) {
                break;
            }
            
            $this->analyzeImagesOnPage($pageUrl);
        }
    }
    
    /**
     * Check if a link is internal
     * 
     * @param string $href Link HREF attribute
     * @param string $baseUrl Base URL for relative links
     * @return bool True if internal
     */
    private function isInternalLink($href, $baseUrl) {
        // Skip empty, javascript, and anchor links
        if (empty($href) || strpos($href, 'javascript:') === 0 || strpos($href, '#') === 0) {
            return false;
        }
        
        // Check if it's an absolute URL
        if (strpos($href, 'http') === 0) {
            // Check if it's for the same domain
            $parsedHref = parse_url($href);
            $hrefDomain = isset($parsedHref['host']) ? $parsedHref['host'] : '';
            
            if (substr($hrefDomain, 0, 4) === 'www.') {
                $hrefDomain = substr($hrefDomain, 4);
            }
            
            return $hrefDomain === $this->domain;
        }
        
        // Relative URLs are internal
        return true;
    }
    
    /**
     * Convert relative URL to absolute
     * 
     * @param string $href Link HREF attribute
     * @param string $baseUrl Base URL for relative links
     * @return string Absolute URL
     */
    private function getAbsoluteUrl($href, $baseUrl) {
        // Already absolute
        if (strpos($href, 'http') === 0) {
            return $href;
        }
        
        $parsedBase = parse_url($baseUrl);
        
        // Protocol-relative URL
        if (strpos($href, '//') === 0) {
            return $parsedBase['scheme'] . ':' . $href;
        }
        
        // Absolute path
        if (strpos($href, '/') === 0) {
            return $parsedBase['scheme'] . '://' . $parsedBase['host'] . $href;
        }
        
        // Relative path
        return rtrim($baseUrl, '/') . '/' . $href;
    }
    
    /**
     * Fetch page content
     * 
     * @param string $url URL to fetch
     * @return string|bool HTML content or false on failure
     */
    private function fetchPageContent($url) {
        return makeHttpRequest($url);
    }
    
    /**
     * Calculate overall score
     * 
     * @return void
     */
    private function calculateOverallScore() {
        $altTextScore = $this->calculateAltTextScore();
        $dimensionsScore = $this->calculateDimensionsScore();
        $lazyLoadingScore = $this->calculateLazyLoadingScore();
        $responsiveImageScore = $this->calculateResponsiveImageScore();
        $optimizationScore = $this->calculateOptimizationScore();
        $modernFormatScore = $this->calculateModernFormatScore();
        
        $this->scoreComponents = [
            'alt_text_score' => $altTextScore,
            'dimensions_score' => $dimensionsScore,
            'lazy_loading_score' => $lazyLoadingScore,
            'responsive_image_score' => $responsiveImageScore,
            'optimization_score' => $optimizationScore,
            'modern_format_score' => $modernFormatScore
        ];
        
        // Calculate weighted average
        $this->totalScore = round(
            ($altTextScore * 0.25) +
            ($dimensionsScore * 0.15) +
            ($lazyLoadingScore * 0.15) +
            ($responsiveImageScore * 0.15) +
            ($optimizationScore * 0.20) +
            ($modernFormatScore * 0.10)
        );
    }
    
    /**
     * Calculate alt text score
     * 
     * @return int Score (0-100)
     */
    private function calculateAltTextScore() {
        $totalImages = $this->getTotalImageCount();
        if ($totalImages === 0) {
            return 100; // No images to evaluate
        }
        
        $imagesWithAlt = $this->getImagesWithAltCount();
        $imagesWithGoodAlt = $this->getImagesWithGoodAltCount();
        
        $score = round(($imagesWithAlt / $totalImages) * 80);
        $score += round(($imagesWithGoodAlt / $totalImages) * 20);
        
        return min(100, $score);
    }
    
    /**
     * Calculate dimensions score
     * 
     * @return int Score (0-100)
     */
    private function calculateDimensionsScore() {
        $totalImages = $this->getTotalImageCount();
        if ($totalImages === 0) {
            return 100; // No images to evaluate
        }
        
        $imagesWithDimensions = $this->getImagesWithDimensionsCount();
        
        return round(($imagesWithDimensions / $totalImages) * 100);
    }
    
    /**
     * Calculate lazy loading score
     * 
     * @return int Score (0-100)
     */
    private function calculateLazyLoadingScore() {
        $totalImages = $this->getTotalImageCount();
        if ($totalImages === 0) {
            return 100; // No images to evaluate
        }
        
        $lazyLoadedImages = $this->getLazyLoadedImagesCount();
        
        return round(($lazyLoadedImages / $totalImages) * 100);
    }
    
    /**
     * Calculate responsive image score
     * 
     * @return int Score (0-100)
     */
    private function calculateResponsiveImageScore() {
        $totalImages = $this->getTotalImageCount();
        if ($totalImages === 0) {
            return 100; // No images to evaluate
        }
        
        $responsiveImages = $this->getResponsiveImagesCount();
        
        return round(($responsiveImages / $totalImages) * 100);
    }
    
    /**
     * Calculate optimization score
     * 
     * @return int Score (0-100)
     */
    private function calculateOptimizationScore() {
        $totalImages = $this->getTotalImageCount();
        if ($totalImages === 0) {
            return 100; // No images to evaluate
        }
        
        $oversizedImages = $this->getOversizedImagesCount();
        $nonOversizedPercent = ($totalImages - $oversizedImages) / $totalImages;
        
        return round($nonOversizedPercent * 100);
    }
    
    /**
     * Calculate modern format score
     * 
     * @return int Score (0-100)
     */
    private function calculateModernFormatScore() {
        $totalImages = $this->getTotalImageCount();
        if ($totalImages === 0) {
            return 100; // No images to evaluate
        }
        
        $modernFormatImages = $this->getModernFormatImagesCount();
        
        return round(($modernFormatImages / $totalImages) * 100);
    }
    
    /**
     * Generate recommendations
     * 
     * @return array List of recommendations
     */
    private function generateRecommendations() {
        $recommendations = [];
        
        // Alt text recommendations
        $missingAltPercent = $this->getImagesWithoutAltCount() / max(1, $this->getTotalImageCount()) * 100;
        if ($missingAltPercent > 10) {
            $priority = $missingAltPercent > 30 ? 'high' : 'medium';
            $recommendations[] = [
                'description' => sprintf('Add alt text to images (%.1f%% missing alt text)', $missingAltPercent),
                'priority' => $priority
            ];
        }
        
        // Image dimensions recommendations
        $missingDimensionsPercent = ($this->getTotalImageCount() - $this->getImagesWithDimensionsCount()) / max(1, $this->getTotalImageCount()) * 100;
        if ($missingDimensionsPercent > 10) {
            $recommendations[] = [
                'description' => 'Specify width and height attributes for images to prevent layout shifts',
                'priority' => 'medium'
            ];
        }
        
        // Lazy loading recommendations
        $nonLazyPercent = ($this->getTotalImageCount() - $this->getLazyLoadedImagesCount()) / max(1, $this->getTotalImageCount()) * 100;
        if ($nonLazyPercent > 20) {
            $recommendations[] = [
                'description' => 'Implement lazy loading for images to improve page load times',
                'priority' => 'medium'
            ];
        }
        
        // Responsive image recommendations
        $nonResponsivePercent = ($this->getTotalImageCount() - $this->getResponsiveImagesCount()) / max(1, $this->getTotalImageCount()) * 100;
        if ($nonResponsivePercent > 20) {
            $recommendations[] = [
                'description' => 'Use responsive image techniques (srcset/sizes attributes) for better mobile experience',
                'priority' => 'medium'
            ];
        }
        
        // Optimization recommendations
        $oversizedPercent = $this->getOversizedImagesCount() / max(1, $this->getTotalImageCount()) * 100;
        if ($oversizedPercent > 10) {
            $priority = $oversizedPercent > 30 ? 'high' : 'medium';
            $recommendations[] = [
                'description' => sprintf('Optimize oversized images (%.1f%% are over 100KB)', $oversizedPercent),
                'priority' => $priority
            ];
        }
        
        // Modern format recommendations
        $oldFormatPercent = ($this->getTotalImageCount() - $this->getModernFormatImagesCount()) / max(1, $this->getTotalImageCount()) * 100;
        if ($oldFormatPercent > 50) {
            $recommendations[] = [
                'description' => 'Convert images to modern formats (WebP/AVIF) for better compression',
                'priority' => 'medium'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Prepare summary statistics
     * 
     * @return array Summary statistics
     */
    private function prepareSummary() {
        $totalImages = $this->getTotalImageCount();
        
        return [
            'total_images' => $totalImages,
            'images_with_alt_percent' => $totalImages > 0 ? round($this->getImagesWithAltCount() / $totalImages * 100, 1) : 0,
            'images_with_dimensions_percent' => $totalImages > 0 ? round($this->getImagesWithDimensionsCount() / $totalImages * 100, 1) : 0,
            'lazy_loaded_images_percent' => $totalImages > 0 ? round($this->getLazyLoadedImagesCount() / $totalImages * 100, 1) : 0,
            'responsive_images_percent' => $totalImages > 0 ? round($this->getResponsiveImagesCount() / $totalImages * 100, 1) : 0,
            'oversized_images_percent' => $totalImages > 0 ? round($this->getOversizedImagesCount() / $totalImages * 100, 1) : 0,
            'modern_format_images_percent' => $totalImages > 0 ? round($this->getModernFormatImagesCount() / $totalImages * 100, 1) : 0
        ];
    }
    
    /**
     * Get total image count
     * 
     * @return int Total number of images analyzed
     */
    private function getTotalImageCount() {
        $count = 0;
        foreach ($this->imageData as $pageData) {
            $count += count($pageData['images']);
        }
        return $count;
    }
    
    /**
     * Get count of images with alt text
     * 
     * @return int Number of images with alt text
     */
    private function getImagesWithAltCount() {
        $count = 0;
        foreach ($this->imageData as $pageData) {
            foreach ($pageData['images'] as $image) {
                if ($image['has_alt']) {
                    $count++;
                }
            }
        }
        return $count;
    }
    
    /**
     * Get count of images without alt text
     * 
     * @return int Number of images without alt text
     */
    private function getImagesWithoutAltCount() {
        return $this->getTotalImageCount() - $this->getImagesWithAltCount();
    }
    
    /**
     * Get count of images with good alt text
     * 
     * @return int Number of images with good alt text
     */
    private function getImagesWithGoodAltCount() {
        $count = 0;
        foreach ($this->imageData as $pageData) {
            foreach ($pageData['images'] as $image) {
                if ($image['has_alt'] && $image['alt_quality'] === 'good') {
                    $count++;
                }
            }
        }
        return $count;
    }
    
    /**
     * Get count of images with width and height attributes
     * 
     * @return int Number of images with dimensions
     */
    private function getImagesWithDimensionsCount() {
        $count = 0;
        foreach ($this->imageData as $pageData) {
            foreach ($pageData['images'] as $image) {
                if ($image['has_dimensions']) {
                    $count++;
                }
            }
        }
        return $count;
    }
    
    /**
     * Get count of lazy loaded images
     * 
     * @return int Number of lazy loaded images
     */
    private function getLazyLoadedImagesCount() {
        $count = 0;
        foreach ($this->imageData as $pageData) {
            foreach ($pageData['images'] as $image) {
                if ($image['uses_lazy_loading']) {
                    $count++;
                }
            }
        }
        return $count;
    }
    
    /**
     * Get count of responsive images
     * 
     * @return int Number of responsive images
     */
    private function getResponsiveImagesCount() {
        $count = 0;
        foreach ($this->imageData as $pageData) {
            foreach ($pageData['images'] as $image) {
                if ($image['is_responsive']) {
                    $count++;
                }
            }
        }
        return $count;
    }
    
    /**
     * Get count of oversized images
     * 
     * @return int Number of oversized images
     */
    private function getOversizedImagesCount() {
        $count = 0;
        foreach ($this->imageData as $pageData) {
            foreach ($pageData['images'] as $image) {
                if ($image['is_oversized']) {
                    $count++;
                }
            }
        }
        return $count;
    }
    
    /**
     * Get count of images using modern formats
     * 
     * @return int Number of images using modern formats
     */
    private function getModernFormatImagesCount() {
        $count = 0;
        foreach ($this->imageData as $pageData) {
            foreach ($pageData['images'] as $image) {
                if ($image['is_modern_format']) {
                    $count++;
                }
            }
        }
        return $count;
    }
}