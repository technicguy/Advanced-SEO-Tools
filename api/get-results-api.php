<?php
// Include database configuration
require_once '../config.php';

// Set header to return JSON
header('Content-Type: application/json');

// Check if website ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Website ID is required'
    ]);
    exit;
}

// Get website ID from GET data
$websiteId = intval($_GET['id']);

try {
    // Get website info
    $stmt = $conn->prepare("SELECT domain_name, url FROM websites WHERE id = ?");
    $stmt->bind_param("i", $websiteId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Website not found'
        ]);
        exit;
    }
    
    $website = $result->fetch_assoc();
    $url = $website['url'];
	
// Ensure all variables used in the response are defined to prevent undefined variable errors
if (!isset($linkAnalysisData)) {
    $linkAnalysisData = null;
}

if (!isset($imageAnalysisData)) {
    $imageAnalysisData = null;
}

if (!isset($pageStructureData)) {
    $pageStructureData = null;
}

if (!isset($competitorData)) {
    $competitorData = null;
}

if (!isset($schemaData)) {
    $schemaData = null;
}

if (!isset($coreWebVitalsData)) {
    $coreWebVitalsData = null;
}	
	
	
	
	
	
    
    // Get technical SEO data
    $stmt = $conn->prepare("SELECT * FROM technical_seo WHERE website_id = ? ORDER BY scan_date DESC LIMIT 1");
    $stmt->bind_param("i", $websiteId);
    $stmt->execute();
    $technicalResult = $stmt->get_result();
    $technicalSeo = $technicalResult->num_rows > 0 ? $technicalResult->fetch_assoc() : null;
    
    // Get on-page SEO data
    $stmt = $conn->prepare("SELECT * FROM onpage_seo WHERE website_id = ? ORDER BY scan_date DESC LIMIT 1");
    $stmt->bind_param("i", $websiteId);
    $stmt->execute();
    $onPageResult = $stmt->get_result();
    $onPageSeo = $onPageResult->num_rows > 0 ? $onPageResult->fetch_assoc() : null;
    
    // Get off-page SEO data
    $stmt = $conn->prepare("SELECT * FROM offpage_seo WHERE website_id = ? ORDER BY scan_date DESC LIMIT 1");
    $stmt->bind_param("i", $websiteId);
    $stmt->execute();
    $offPageResult = $stmt->get_result();
    $offPageSeo = $offPageResult->num_rows > 0 ? $offPageResult->fetch_assoc() : null;
    
    // Get keyword analysis data
    $stmt = $conn->prepare("SELECT * FROM keyword_analysis WHERE website_id = ? ORDER BY scan_date DESC");
    $stmt->bind_param("i", $websiteId);
    $stmt->execute();
    $keywordResult = $stmt->get_result();
    
    $keywords = [];
    if ($keywordResult->num_rows > 0) {
        while($row = $keywordResult->fetch_assoc()) {
            $keywords[] = [
                'keyword' => $row['keyword'],
                'search_volume' => $row['search_volume'],
                'difficulty' => $row['keyword_difficulty'],
                'current_ranking' => $row['current_ranking']
            ];
        }
    }
    
    // Get content analysis data
    $stmt = $conn->prepare("SELECT * FROM content_analysis WHERE website_id = ? ORDER BY scan_date DESC");
    $stmt->bind_param("i", $websiteId);
    $stmt->execute();
    $contentResult = $stmt->get_result();
    
    $contentPages = [];
    $totalWordCount = 0;
    $totalReadability = 0;
    $pageCount = 0;
    $thinContentCount = 0;
    
    if ($contentResult->num_rows > 0) {
        while($row = $contentResult->fetch_assoc()) {
            $contentPages[] = [
                'page_url' => $row['page_url'],
                'keyword_density' => $row['keyword_density'],
                'readability_score' => $row['readability_score'],
                'content_length' => $row['content_length']
            ];
            
            $totalWordCount += $row['content_length'];
            $totalReadability += $row['readability_score'];
            $pageCount++;
            
            if ($row['content_length'] < 300) {
                $thinContentCount++;
            }
        }
    }
    
    // Get image analysis data
    $stmt = $conn->prepare("SELECT page_url, total_images, images_with_alt, images_without_alt, 
                           oversized_images, lazy_loaded_images, image_details_json 
                           FROM image_analysis 
                           WHERE website_id = ? 
                           ORDER BY scan_date DESC");
    $stmt->bind_param("i", $websiteId);
    $stmt->execute();
    $imageResult = $stmt->get_result();
    
    $imageAnalysisData = null;
    if ($imageResult->num_rows > 0) {
        // Initialize data structure
        $imageAnalysisData = [
            'overall_score' => 0,
            'pages_analyzed' => 0,
            'total_images' => 0,
            'images_with_alt' => 0,
            'images_without_alt' => 0,
            'oversized_images' => 0,
            'lazy_loaded_images' => 0,
            'responsive_images' => 0,
            'modern_format_images' => 0,
            'image_details' => [],
            'score_components' => [
                'alt_text_score' => 0,
                'dimensions_score' => 0,
                'lazy_loading_score' => 0,
                'responsive_image_score' => 0,
                'optimization_score' => 0,
                'modern_format_score' => 0
            ],
            'summary' => [
                'total_images' => 0,
                'images_with_alt_percent' => 0,
                'images_with_dimensions_percent' => 0,
                'lazy_loaded_images_percent' => 0,
                'responsive_images_percent' => 0,
                'oversized_images_percent' => 0,
                'modern_format_images_percent' => 0
            ],
            'recommendations' => []
        ];
        
        // Gather basic metrics
        $totalImages = 0;
        $imagesWithAlt = 0;
        $imagesWithoutAlt = 0;
        $oversizedImages = 0;
        $lazyLoadedImages = 0;
        $responsiveImages = 0;
        $modernFormatImages = 0;
        $imagesWithDimensions = 0;
        $pagesAnalyzed = 0;
        
        while ($row = $imageResult->fetch_assoc()) {
            $pagesAnalyzed++;
            $totalImages += $row['total_images'];
            $imagesWithAlt += $row['images_with_alt'];
            $imagesWithoutAlt += $row['images_without_alt'];
            $oversizedImages += $row['oversized_images'];
            $lazyLoadedImages += $row['lazy_loaded_images'];
            
            // Add page details to image_details array
            if (!empty($row['image_details_json'])) {
                $imageDetails = json_decode($row['image_details_json'], true);
                if (is_array($imageDetails)) {
                    $imageAnalysisData['image_details'][] = [
                        'url' => $row['page_url'],
                        'images' => $imageDetails
                    ];
                    
                    // Count additional metrics from detailed data
                    foreach ($imageDetails as $image) {
                        if (isset($image['is_responsive']) && $image['is_responsive']) {
                            $responsiveImages++;
                        }
                        if (isset($image['is_modern_format']) && $image['is_modern_format']) {
                            $modernFormatImages++;
                        }
                        if (isset($image['has_dimensions']) && $image['has_dimensions']) {
                            $imagesWithDimensions++;
                        }
                    }
                }
            }
        }
        
        // Update basic metrics in data structure
        $imageAnalysisData['pages_analyzed'] = $pagesAnalyzed;
        $imageAnalysisData['total_images'] = $totalImages;
        $imageAnalysisData['images_with_alt'] = $imagesWithAlt;
        $imageAnalysisData['images_without_alt'] = $imagesWithoutAlt;
        $imageAnalysisData['oversized_images'] = $oversizedImages;
        $imageAnalysisData['lazy_loaded_images'] = $lazyLoadedImages;
        $imageAnalysisData['responsive_images'] = $responsiveImages;
        $imageAnalysisData['modern_format_images'] = $modernFormatImages;
        
        // Update summary with percentages
        if ($totalImages > 0) {
            $imageAnalysisData['summary']['total_images'] = $totalImages;
            $imageAnalysisData['summary']['images_with_alt_percent'] = round(($imagesWithAlt / $totalImages) * 100, 1);
            $imageAnalysisData['summary']['images_with_dimensions_percent'] = round(($imagesWithDimensions / $totalImages) * 100, 1);
            $imageAnalysisData['summary']['lazy_loaded_images_percent'] = round(($lazyLoadedImages / $totalImages) * 100, 1);
            $imageAnalysisData['summary']['responsive_images_percent'] = round(($responsiveImages / $totalImages) * 100, 1);
            $imageAnalysisData['summary']['oversized_images_percent'] = round(($oversizedImages / $totalImages) * 100, 1);
            $imageAnalysisData['summary']['modern_format_images_percent'] = round(($modernFormatImages / $totalImages) * 100, 1);
        }
        
        // Calculate scores
        if ($totalImages > 0) {
            $altTextScore = round(($imagesWithAlt / $totalImages) * 100);
            $dimensionsScore = round(($imagesWithDimensions / $totalImages) * 100);
            $lazyLoadingScore = round(($lazyLoadedImages / $totalImages) * 100);
            $responsiveScore = round(($responsiveImages / $totalImages) * 100);
            $optimizationScore = round((($totalImages - $oversizedImages) / $totalImages) * 100);
            $modernFormatScore = round(($modernFormatImages / $totalImages) * 100);
            
            $imageAnalysisData['score_components']['alt_text_score'] = $altTextScore;
            $imageAnalysisData['score_components']['dimensions_score'] = $dimensionsScore;
            $imageAnalysisData['score_components']['lazy_loading_score'] = $lazyLoadingScore;
            $imageAnalysisData['score_components']['responsive_image_score'] = $responsiveScore;
            $imageAnalysisData['score_components']['optimization_score'] = $optimizationScore;
            $imageAnalysisData['score_components']['modern_format_score'] = $modernFormatScore;
            
            // Calculate overall score (weighted)
            $imageAnalysisData['overall_score'] = round(
                ($altTextScore * 0.25) + 
                ($dimensionsScore * 0.15) + 
                ($lazyLoadingScore * 0.15) + 
                ($responsiveScore * 0.15) + 
                ($optimizationScore * 0.20) + 
                ($modernFormatScore * 0.10)
            );
        }
        
        // Add recommendations based on metrics
        if ($imageAnalysisData['summary']['images_with_alt_percent'] < 80) {
            $imageAnalysisData['recommendations'][] = [
                'description' => 'Add alt text to images for better accessibility and SEO',
                'priority' => $imageAnalysisData['summary']['images_with_alt_percent'] < 50 ? 'high' : 'medium'
            ];
        }
        
        if ($imageAnalysisData['summary']['lazy_loaded_images_percent'] < 70) {
            $imageAnalysisData['recommendations'][] = [
                'description' => 'Implement lazy loading for images to improve page load times',
                'priority' => $imageAnalysisData['summary']['lazy_loaded_images_percent'] < 30 ? 'high' : 'medium'
            ];
        }
        
        if ($imageAnalysisData['summary']['responsive_images_percent'] < 60) {
            $imageAnalysisData['recommendations'][] = [
                'description' => 'Use responsive image techniques (srcset/sizes attributes) for better mobile experience',
                'priority' => 'medium'
            ];
        }
        
        if ($imageAnalysisData['summary']['oversized_images_percent'] > 20) {
            $imageAnalysisData['recommendations'][] = [
                'description' => 'Optimize oversized images to improve page load speed',
                'priority' => $imageAnalysisData['summary']['oversized_images_percent'] > 50 ? 'high' : 'medium'
            ];
        }
        
        if ($imageAnalysisData['summary']['modern_format_images_percent'] < 40) {
            $imageAnalysisData['recommendations'][] = [
                'description' => 'Convert images to modern formats (WebP/AVIF) for better compression',
                'priority' => 'medium'
            ];
        }
    }
	
	
// Get page structure data
$stmt = $conn->prepare("SELECT page_url, has_h1, heading_structure_json, 
                       content_html_ratio, main_content_word_count, has_schema_markup, 
                       schema_types_json 
                       FROM page_structure 
                       WHERE website_id = ? 
                       ORDER BY scan_date DESC");
$stmt->bind_param("i", $websiteId);
$stmt->execute();
$pageStructureResult = $stmt->get_result();

$pageStructureData = null;
if ($pageStructureResult->num_rows > 0) {
    // Initialize base structure with default values
    $pageStructureData = [
        'overall_score' => 0,
        'heading_structure_score' => 0,
        'semantic_html_score' => 0,
        'content_html_ratio_score' => 0,
        'schema_markup_score' => 0,
        'analyzed_pages' => 0,
        'pages_data' => [],
        'issues' => [],
        'recommendations' => []
    ];
    
    // Build pages_data array
    $totalPages = 0;
    $pagesWithH1 = 0;
    $pagesWithSchema = 0;
    $totalContentRatio = 0;
    
    while ($row = $pageStructureResult->fetch_assoc()) {
        $totalPages++;
        
        // Parse JSON fields
        $headingStructure = json_decode($row['heading_structure_json'], true) ?: [];
        $schemaTypes = json_decode($row['schema_types_json'], true) ?: [];
        
        // Calculate if this page has proper structure
        $hasH1 = $row['has_h1'] == 1;
        $hasSchemaMarkup = $row['has_schema_markup'] == 1;
        
        if ($hasH1) $pagesWithH1++;
        if ($hasSchemaMarkup) $pagesWithSchema++;
        
        $contentHtmlRatio = floatval($row['content_html_ratio']);
        $totalContentRatio += $contentHtmlRatio;
        
        // Create a semantic elements placeholder (this data might not be in the database)
        $semanticElements = [
            'has_header' => false,
            'has_nav' => false,
            'has_main' => false,
            'has_footer' => false,
            'has_section' => false,
            'has_article' => false,
            'has_aside' => false,
            'semantic_element_count' => 0,
            'div_span_count' => 0,
            'semantic_ratio' => 0.3 // Default value
        ];
        
        // Add page data
        $pageStructureData['pages_data'][] = [
            'url' => $row['page_url'],
            'has_h1' => $hasH1,
            'heading_structure' => $headingStructure,
            'semantic_elements' => $semanticElements,
            'content_html_ratio' => $contentHtmlRatio,
            'main_content_word_count' => $row['main_content_word_count'],
            'has_schema_markup' => $hasSchemaMarkup,
            'schema_types' => $schemaTypes
        ];
    }
    
    // Calculate scores
    if ($totalPages > 0) {
        $pageStructureData['heading_structure_score'] = round(($pagesWithH1 / $totalPages) * 100);
        $pageStructureData['schema_markup_score'] = round(($pagesWithSchema / $totalPages) * 100);
        $pageStructureData['content_html_ratio_score'] = min(100, round(($totalContentRatio / $totalPages) * 300)); // Scale to 0-100
        $pageStructureData['semantic_html_score'] = 70; // Default placeholder
        
        // Calculate overall score (weighted average)
        $pageStructureData['overall_score'] = round(
            ($pageStructureData['heading_structure_score'] * 0.3) +
            ($pageStructureData['semantic_html_score'] * 0.3) +
            ($pageStructureData['content_html_ratio_score'] * 0.2) +
            ($pageStructureData['schema_markup_score'] * 0.2)
        );
        
        $pageStructureData['analyzed_pages'] = $totalPages;
        
        // Generate placeholder issues and recommendations based on data
        if ($pagesWithH1 < $totalPages) {
            $missingH1Count = $totalPages - $pagesWithH1;
            $pageStructureData['issues'][] = [
                'description' => "Missing H1 tag on {$missingH1Count} page(s)",
                'severity' => 'medium'
            ];
            
            $pageStructureData['recommendations'][] = [
                'description' => "Add H1 tags to pages missing them",
                'priority' => 'medium',
                'details' => "Every page should have exactly one H1 tag that clearly describes the page's main topic."
            ];
        }
        
        if ($pagesWithSchema < $totalPages) {
            $missingSchemaCount = $totalPages - $pagesWithSchema;
            $pageStructureData['issues'][] = [
                'description' => "Missing schema markup on {$missingSchemaCount} page(s)",
                'severity' => 'medium'
            ];
            
            $pageStructureData['recommendations'][] = [
                'description' => "Add schema markup to pages missing it",
                'priority' => 'medium',
                'details' => "Schema markup helps search engines understand your content better and potentially enable rich snippets in search results."
            ];
        }
    }
}	
	
	
	
	
    
    // Get link analysis data
    $stmt = $conn->prepare("SELECT page_url, total_links, internal_links, external_links, 
                           broken_links, nofollow_links, links_details_json 
                           FROM link_analysis 
                           WHERE website_id = ? 
                           ORDER BY scan_date DESC");
    $stmt->bind_param("i", $websiteId);
    $stmt->execute();
    $linkResult = $stmt->get_result();
    
    $linkAnalysisData = null;
    if ($linkResult->num_rows > 0) {
        // Initialize data structure
        $linkAnalysisData = [
            'overall_score' => 0,
            'pages_analyzed' => 0,
            'total_links' => 0,
            'internal_links' => 0,
            'external_links' => 0,
            'broken_links' => 0,
            'nofollow_links' => 0,
            'pages_data' => [],
            'link_distribution' => [],
            'score_components' => [
                'internal_linking_score' => 0,
                'external_linking_score' => 0,
                'broken_links_score' => 0,
                'link_quality_score' => 0
            ],
            'summary' => [
                'total_links' => 0,
                'internal_links_percent' => 0,
                'external_links_percent' => 0,
                'broken_links_percent' => 0,
                'nofollow_links_percent' => 0
            ],
            'recommendations' => []
        ];
        
        // Gather basic metrics
        $totalLinks = 0;
        $internalLinks = 0;
        $externalLinks = 0;
        $brokenLinks = 0;
        $nofollowLinks = 0;
        $pagesAnalyzed = 0;
        
        // Process link analysis data
        while ($row = $linkResult->fetch_assoc()) {
            $pagesAnalyzed++;
            $totalLinks += $row['total_links'];
            $internalLinks += $row['internal_links'];
            $externalLinks += $row['external_links'];
            $brokenLinks += $row['broken_links'];
            $nofollowLinks += $row['nofollow_links'];
            
            // Add page data
            $linkAnalysisData['pages_data'][] = [
                'url' => $row['page_url'],
                'total_links' => $row['total_links'],
                'internal_links' => $row['internal_links'],
                'external_links' => $row['external_links'],
                'broken_links' => $row['broken_links'],
                'nofollow_links' => $row['nofollow_links']
            ];
            
            // Process detailed link data if available
            if (!empty($row['links_details_json'])) {
                $linksDetails = json_decode($row['links_details_json'], true);
                if (is_array($linksDetails)) {
                    // You could process additional metrics from the detailed data here
                    // For example, counting links by type, destination, etc.
                }
            }
        }
        
        // Update basic metrics in data structure
        $linkAnalysisData['pages_analyzed'] = $pagesAnalyzed;
        $linkAnalysisData['total_links'] = $totalLinks;
        $linkAnalysisData['internal_links'] = $internalLinks;
        $linkAnalysisData['external_links'] = $externalLinks;
        $linkAnalysisData['broken_links'] = $brokenLinks;
        $linkAnalysisData['nofollow_links'] = $nofollowLinks;
        
        // Update summary with percentages
        if ($totalLinks > 0) {
            $linkAnalysisData['summary']['total_links'] = $totalLinks;
            $linkAnalysisData['summary']['internal_links_percent'] = round(($internalLinks / $totalLinks) * 100, 1);
            $linkAnalysisData['summary']['external_links_percent'] = round(($externalLinks / $totalLinks) * 100, 1);
            $linkAnalysisData['summary']['broken_links_percent'] = round(($brokenLinks / $totalLinks) * 100, 1);
            $linkAnalysisData['summary']['nofollow_links_percent'] = round(($nofollowLinks / $totalLinks) * 100, 1);
        }
        
        // Calculate scores
        if ($totalLinks > 0) {
            // Internal linking score - higher is better, but diminishes after optimal ratio
            $internalLinkingRatio = $internalLinks / $totalLinks;
            $internalLinkingScore = 0;
            if ($internalLinkingRatio <= 0.8) {
                // Linear score if ratio is <= 80%
                $internalLinkingScore = round($internalLinkingRatio * 100);
            } else {
                // Diminishing score for higher ratios (too many internal links)
                $internalLinkingScore = round(80 + (($internalLinkingRatio - 0.8) * 100));
            }
            
            // External linking score - some external links are good
            $externalLinkingRatio = $externalLinks / $totalLinks;
            $externalLinkingScore = 0;
            if ($externalLinkingRatio <= 0.3) {
                // Linear score if ratio is <= 30%
                $externalLinkingScore = round(($externalLinkingRatio / 0.3) * 100);
            } else {
                // Diminishing score for higher ratios (too many external links)
                $externalLinkingScore = round(100 - (($externalLinkingRatio - 0.3) * 150));
            }
            
            // Broken links score - fewer is better
            $brokenLinksRatio = $brokenLinks / $totalLinks;
            $brokenLinksScore = round(100 - ($brokenLinksRatio * 500)); // Heavy penalty for broken links
            
            // Link quality score - based on nofollow usage
            $nofollowRatio = $nofollowLinks / $totalLinks;
            $linkQualityScore = 0;
            if ($nofollowRatio <= 0.2) {
                // Good nofollow usage
                $linkQualityScore = 100;
            } else {
                // Too many nofollow links
                $linkQualityScore = round(100 - (($nofollowRatio - 0.2) * 200));
            }
            
            // Ensure scores are within bounds
            $internalLinkingScore = max(0, min(100, $internalLinkingScore));
            $externalLinkingScore = max(0, min(100, $externalLinkingScore));
            $brokenLinksScore = max(0, min(100, $brokenLinksScore));
            $linkQualityScore = max(0, min(100, $linkQualityScore));
            
            // Store score components
            $linkAnalysisData['score_components']['internal_linking_score'] = $internalLinkingScore;
            $linkAnalysisData['score_components']['external_linking_score'] = $externalLinkingScore;
            $linkAnalysisData['score_components']['broken_links_score'] = $brokenLinksScore;
            $linkAnalysisData['score_components']['link_quality_score'] = $linkQualityScore;
            
            // Calculate overall score (weighted)
            $linkAnalysisData['overall_score'] = round(
                ($internalLinkingScore * 0.4) + 
                ($externalLinkingScore * 0.2) + 
                ($brokenLinksScore * 0.3) + 
                ($linkQualityScore * 0.1)
            );
        }
        
        // Add recommendations based on metrics
        if ($brokenLinks > 0) {
            $linkAnalysisData['recommendations'][] = [
                'description' => 'Fix ' . $brokenLinks . ' broken links to improve user experience and SEO',
                'priority' => $linkAnalysisData['summary']['broken_links_percent'] > 5 ? 'high' : 'medium'
            ];
        }
        
        if ($linkAnalysisData['summary']['internal_links_percent'] < 60) {
            $linkAnalysisData['recommendations'][] = [
                'description' => 'Increase internal linking to improve site structure and user navigation',
                'priority' => 'medium'
            ];
        }
        
        if ($linkAnalysisData['summary']['external_links_percent'] < 5) {
            $linkAnalysisData['recommendations'][] = [
                'description' => 'Add more outbound links to authoritative sources to improve content quality signals',
                'priority' => 'low'
            ];
        } else if ($linkAnalysisData['summary']['external_links_percent'] > 40) {
            $linkAnalysisData['recommendations'][] = [
                'description' => 'Reduce excessive external linking to keep visitors on your site longer',
                'priority' => 'medium'
            ];
        }
        
        if ($pagesAnalyzed >= 5 && $internalLinks / $pagesAnalyzed < 3) {
            $linkAnalysisData['recommendations'][] = [
                'description' => 'Add more cross-links between pages to improve site structure',
                'priority' => 'medium'
            ];
        }
    }
    



// Get competitor analysis data
$competitorData = null;
$competitorStmt = $conn->prepare("SELECT * FROM competitor_analysis WHERE website_id = ? ORDER BY scan_date DESC LIMIT 1");
if ($competitorStmt) {
    $competitorStmt->bind_param("i", $websiteId);
    $competitorStmt->execute();
    $competitorResult = $competitorStmt->get_result();
    
    if ($competitorResult->num_rows > 0) {
        $competitorRow = $competitorResult->fetch_assoc();
        
        $competitorData = [
            'overall_score' => $competitorRow['overall_score'],
            'competitors' => json_decode($competitorRow['competitors_json'], true) ?: [],
            'keyword_gaps' => json_decode($competitorRow['keyword_gaps_json'], true) ?: [],
            'backlink_gaps' => json_decode($competitorRow['backlink_gaps_json'], true) ?: [],
            'content_gaps' => json_decode($competitorRow['content_gaps_json'], true) ?: [],
            'recommendations' => json_decode($competitorRow['recommendations_json'], true) ?: []
        ];
    }
}

// Get schema markup data
$schemaData = null;
$schemaStmt = $conn->prepare("SELECT * FROM schema_markup WHERE website_id = ? ORDER BY scan_date DESC LIMIT 1");
if ($schemaStmt) {
    $schemaStmt->bind_param("i", $websiteId);
    $schemaStmt->execute();
    $schemaResult = $schemaStmt->get_result();
    
    if ($schemaResult->num_rows > 0) {
        $schemaRow = $schemaResult->fetch_assoc();
        
        $schemaData = [
            'overall_score' => $schemaRow['overall_score'],
            'has_schema' => $schemaRow['has_schema'] == 1,
            'schema_types' => json_decode($schemaRow['schema_types_json'], true) ?: [],
            'validation_issues' => json_decode($schemaRow['validation_issues_json'], true) ?: [],
            'recommendations' => json_decode($schemaRow['recommendations_json'], true) ?: []
        ];
    }
}


// Get Core Web Vitals data
$coreWebVitalsData = null;
$coreWebVitalsStmt = $conn->prepare("SELECT * FROM performance_metrics WHERE website_id = ? ORDER BY scan_date DESC LIMIT 1");
if ($coreWebVitalsStmt) {
    $coreWebVitalsStmt->bind_param("i", $websiteId);
    $coreWebVitalsStmt->execute();
    $coreWebVitalsResult = $coreWebVitalsStmt->get_result();
    
    if ($coreWebVitalsResult->num_rows > 0) {
        $coreWebVitalsRow = $coreWebVitalsResult->fetch_assoc();
        
        $coreWebVitalsData = [
            'overall_score' => $coreWebVitalsRow['overall_score'],
            'metrics' => json_decode($coreWebVitalsRow['performance_json'], true) ?: [],
            'recommendations' => json_decode($coreWebVitalsRow['recommendations_json'], true) ?: []
        ];
        
        // Build metrics structure if not already in the right format
        if (!isset($coreWebVitalsData['metrics']['lcp'])) {
            $coreWebVitalsData['metrics'] = [
                'lcp' => [
                    'score' => $coreWebVitalsRow['lcp_score'],
                    'value' => null,
                    'status' => getWebVitalsStatus($coreWebVitalsRow['lcp_score'])
                ],
                'fid' => [
                    'score' => $coreWebVitalsRow['fid_score'],
                    'value' => null,
                    'status' => getWebVitalsStatus($coreWebVitalsRow['fid_score'])
                ],
                'cls' => [
                    'score' => $coreWebVitalsRow['cls_score'],
                    'value' => null,
                    'status' => getWebVitalsStatus($coreWebVitalsRow['cls_score'])
                ],
                'ttfb' => [
                    'score' => $coreWebVitalsRow['ttfb_score'],
                    'value' => null,
                    'status' => getWebVitalsStatus($coreWebVitalsRow['ttfb_score'])
                ]
            ];
        }
    }
}





    // NEW: Get AI recommendations
    $aiRecommendations = null;
    
    // Check if ai_recommendations table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'ai_recommendations'");
    if ($tableCheck->num_rows > 0) {
        $stmt = $conn->prepare("SELECT ar.*, am.name as model_name 
                            FROM ai_recommendations ar 
                            JOIN ai_models am ON ar.ai_model_id = am.id 
                            WHERE ar.website_id = ? 
                            ORDER BY ar.created_at DESC 
                            LIMIT 1");
        $stmt->bind_param("i", $websiteId);
        $stmt->execute();
        $aiResult = $stmt->get_result();
        
        if ($aiResult->num_rows > 0) {
            $aiRow = $aiResult->fetch_assoc();
            $aiRecommendations = [
                'model_id' => $aiRow['ai_model_id'],
                'model_name' => $aiRow['model_name'],
                'summary' => $aiRow['summary'],
                'recommendations' => json_decode($aiRow['recommendations_json'], true)
            ];
        }
    }
    
    // Calculate averages for content analysis
    $avgWordCount = $pageCount > 0 ? round($totalWordCount / $pageCount) : 0;
    $avgReadability = $pageCount > 0 ? round($totalReadability / $pageCount) : 0;
    
    // Add simulated issues for technical SEO 
    if ($technicalSeo) {
        $technicalSeo['issues'] = [];
        
        if ($technicalSeo['crawlability_score'] < 80) {
            $technicalSeo['issues'][] = [
                'description' => 'Some pages are not accessible to search engine crawlers',
                'severity' => 'medium'
            ];
        }
        
        if ($technicalSeo['site_speed_score'] < 70) {
            $technicalSeo['issues'][] = [
                'description' => 'Site load time is too slow (> 3 seconds)',
                'severity' => 'high'
            ];
        }
    }
    
    // Add simulated recommendations for on-page SEO
    if ($onPageSeo) {
        $onPageSeo['recommendations'] = [];
        
        if ($onPageSeo['title_tags_score'] < 80) {
            $onPageSeo['recommendations'][] = [
                'description' => 'Optimize title tags to include target keywords',
                'priority' => 'high'
            ];
        }
        
        if ($onPageSeo['meta_desc_score'] < 70) {
            $onPageSeo['recommendations'][] = [
                'description' => 'Improve meta descriptions for better click-through rates',
                'priority' => 'high'
            ];
        }
    }
    
    // Add simulated backlinks for off-page SEO
    if ($offPageSeo) {
        $domain = parse_url($url, PHP_URL_HOST);
        if (substr($domain, 0, 4) === 'www.') {
            $domain = substr($domain, 4);
        }
        
        $offPageSeo['top_backlinks'] = [
            [
                'domain' => 'example-blog.com',
                'url' => 'https://example-blog.com/post/123',
                'domain_authority' => rand(20, 70),
                'type' => 'Guest Post'
            ],
            [
                'domain' => 'industry-directory.com',
                'url' => 'https://industry-directory.com/listing/' . str_replace('.', '-', $domain),
                'domain_authority' => rand(40, 80),
                'type' => 'Directory'
            ],
            [
                'domain' => 'news-site.com',
                'url' => 'https://news-site.com/article/456',
                'domain_authority' => rand(60, 90),
                'type' => 'Editorial'
            ]
        ];
    }
    
    // Add simulated content suggestions
    $contentSuggestions = [
        [
            'description' => 'Increase content length for key pages (aim for 1000+ words)',
            'priority' => $avgWordCount < 700 ? 'high' : 'low'
        ],
        [
            'description' => 'Improve content readability by using shorter sentences and simpler language',
            'priority' => $avgReadability < 70 ? 'medium' : 'low'
        ],
        [
            'description' => 'Add more multimedia content (images, videos, infographics)',
            'priority' => 'medium'
        ],
        [
            'description' => 'Add FAQ sections to address common user questions',
            'priority' => 'low'
        ]
    ];
    
if (!isset($localSeoData)) {
    $localSeoData = null;
}

if (!isset($contentGapData)) {
    $contentGapData = null;
}

// Get Local SEO data
$localSeoStmt = $conn->prepare("SELECT * FROM local_seo WHERE website_id = ? ORDER BY scan_date DESC LIMIT 1");
if ($localSeoStmt) {
    $localSeoStmt->bind_param("i", $websiteId);
    $localSeoStmt->execute();
    $localSeoResult = $localSeoStmt->get_result();
    
    if ($localSeoResult->num_rows > 0) {
        $localSeoRow = $localSeoResult->fetch_assoc();
        
        $localSeoData = [
            'overall_score' => $localSeoRow['overall_score'],
            'has_google_business_profile' => $localSeoRow['has_google_business_profile'] == 1,
            'has_local_business_schema' => $localSeoRow['has_local_business_schema'] == 1,
            'has_location_pages' => $localSeoRow['has_location_pages'] == 1,
            'has_nap_consistency' => $localSeoRow['has_nap_consistency'] == 1,
            'has_local_keywords' => $localSeoRow['has_local_keywords'] == 1,
            'has_embedded_map' => $localSeoRow['has_embedded_map'] == 1,
            'citation_score' => $localSeoRow['citation_score'],
            'google_business_data' => json_decode($localSeoRow['google_business_data_json'], true) ?: [],
            'local_citations' => json_decode($localSeoRow['citations_json'], true) ?: [],
            'nap_data' => json_decode($localSeoRow['nap_data_json'], true) ?: [],
            'issues' => json_decode($localSeoRow['issues_json'], true) ?: [],
            'recommendations' => json_decode($localSeoRow['recommendations_json'], true) ?: []
        ];
    }
}

// Get Content Gap data
$contentGapStmt = $conn->prepare("SELECT * FROM content_gap WHERE website_id = ? ORDER BY scan_date DESC LIMIT 1");
if ($contentGapStmt) {
    $contentGapStmt->bind_param("i", $websiteId);
    $contentGapStmt->execute();
    $contentGapResult = $contentGapStmt->get_result();
    
    if ($contentGapResult->num_rows > 0) {
        $contentGapRow = $contentGapResult->fetch_assoc();
        
        $contentGapData = [
            'overall_score' => $contentGapRow['overall_score'],
            'opportunity_score' => $contentGapRow['opportunity_score'],
            'topics_covered' => json_decode($contentGapRow['topics_covered_json'], true) ?: [],
            'missing_topics' => json_decode($contentGapRow['missing_topics_json'], true) ?: [],
            'keyword_gaps' => json_decode($contentGapRow['keyword_gaps_json'], true) ?: [],
            'competitor_data' => json_decode($contentGapRow['competitor_data_json'], true) ?: [],
            'content_length_comparison' => json_decode($contentGapRow['content_length_comparison_json'], true) ?: [],
            'recommendations' => json_decode($contentGapRow['recommendations_json'], true) ?: []
        ];
    }
}

// Update response data with the new sections
$responseData = [
    'success' => true,
    'url' => $url,
    'technicalSeo' => $technicalSeo,
    'metaTagsAnalysis' => null, // You may need to add this data if available
    'onPageSeo' => $onPageSeo,
    'offPageSeo' => $offPageSeo,
    'linkAnalysis' => $linkAnalysisData,
    'imageAnalysis' => $imageAnalysisData,
    'pageStructure' => $pageStructureData,
    'keywordAnalysis' => [
        'keywords' => $keywords
    ],
    'contentAnalysis' => [
        'avg_word_count' => $avgWordCount,
        'avg_readability' => $avgReadability,
        'total_pages' => $pageCount,
        'thin_content_pages' => $thinContentCount,
        'suggestions' => $contentSuggestions
    ],
    'competitor' => $competitorData,
    'schema' => $schemaData,
    'coreWebVitals' => $coreWebVitalsData,
    'localSeo' => $localSeoData,
    'contentGap' => $contentGapData,
    'aiRecommendations' => $aiRecommendations
];

	
		
    // Return success response
    echo json_encode($responseData);
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Failed to get results: ' . $e->getMessage()
    ]);
}



/**
 * Helper function to determine Core Web Vitals status based on score
 * 
 * @param int $score Score value (0-100)
 * @return string Status ('good', 'needs-improvement', or 'poor')
 */
function getWebVitalsStatus($score) {
    if ($score >= 90) {
        return 'good';
    } else if ($score >= 50) {
        return 'needs-improvement';
    } else {
        return 'poor';
    }
}