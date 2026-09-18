<?php
// Include database configuration
require_once '../config.php';

require_once __DIR__ . '/../includes/security.php';
api_session_boot();

// Set header to return JSON
header('Content-Type: application/json');

// Check if results data is submitted
if (!isset($_POST['results']) || empty($_POST['results'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Results data is required'
    ]);
    exit;
}

// Get results from POST data
$resultsJson = $_POST['results'];
$results = json_decode($resultsJson, true);

// Validate results data
if (!$results || !isset($results['url']) || empty($results['url'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid results data'
    ]);
    exit;
}

// Extract data
$url = $results['url'];
$technicalSeo = $results['technicalSeo'] ?? null;
$metaTagsAnalysis = $results['metaTagsAnalysis'] ?? null;
$onPageSeo = $results['onPageSeo'] ?? null;
$offPageSeo = $results['offPageSeo'] ?? null;
$keywordAnalysis = $results['keywordAnalysis'] ?? null;
$contentAnalysis = $results['contentAnalysis'] ?? null;
$aiRecommendations = $results['aiRecommendations'] ?? null;
$linkAnalysis = $results['linkAnalysis'] ?? null;
$imageAnalysis = $results['imageAnalysis'] ?? null;
$pageStructure = $results['pageStructure'] ?? null;
$coreWebVitals = $results['coreWebVitals'] ?? null;
$competitor = $results['competitor'] ?? null;
$schema = $results['schema'] ?? null;
$localSeo = $results['localSeo'] ?? null;
$contentGap = $results['contentGap'] ?? null;

// Extract domain name from URL
$domain = parse_url($url, PHP_URL_HOST);
if (substr($domain, 0, 4) === 'www.') {
    $domain = substr($domain, 4);
}

// Begin transaction
$conn->begin_transaction();

try {
    // Check if website already exists
    $stmt = $conn->prepare("SELECT id FROM websites WHERE url = ?");
    $stmt->bind_param("s", $url);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Website exists, get ID
        $row = $result->fetch_assoc();
        $websiteId = $row['id'];
        
        // Update last_checked timestamp
        $stmt = $conn->prepare("UPDATE websites SET last_checked = NOW() WHERE id = ?");
        $stmt->bind_param("i", $websiteId);
        $stmt->execute();
    } else {
        // Insert new website
        $stmt = $conn->prepare("INSERT INTO websites (domain_name, url) VALUES (?, ?)");
        $stmt->bind_param("ss", $domain, $url);
        $stmt->execute();
        $websiteId = $conn->insert_id;
    }
    
    // Save technical SEO data
    if ($technicalSeo) {
        $crawlabilityScore = $technicalSeo['crawlability_score'] ?? null;
        $indexabilityScore = $technicalSeo['indexability_score'] ?? null;
        $siteSpeedScore = $technicalSeo['site_speed_score'] ?? null;
        $mobileFriendlyScore = $technicalSeo['mobile_friendly_score'] ?? null;
        $schemaMarkupValid = isset($technicalSeo['schema_markup_valid']) ? ($technicalSeo['schema_markup_valid'] ? 1 : 0) : null;
        $hasSSL = isset($technicalSeo['has_ssl']) ? ($technicalSeo['has_ssl'] ? 1 : 0) : null;
        
        $stmt = $conn->prepare("INSERT INTO technical_seo (website_id, crawlability_score, indexability_score, site_speed_score, mobile_friendly_score, schema_markup_valid, has_ssl) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiiiii", $websiteId, $crawlabilityScore, $indexabilityScore, $siteSpeedScore, $mobileFriendlyScore, $schemaMarkupValid, $hasSSL);
        $stmt->execute();
    }
    
    // Save meta tags analysis data
    if ($metaTagsAnalysis) {
        $pageUrl = $url; // Default to main URL
        $title = $metaTagsAnalysis['title'] ?? null;
        $titleLength = $metaTagsAnalysis['title_length'] ?? null;
        $metaDescription = $metaTagsAnalysis['meta_description'] ?? null;
        $metaDescriptionLength = $metaTagsAnalysis['meta_description_length'] ?? null;
        $metaKeywords = $metaTagsAnalysis['meta_keywords'] ?? null;
        $hasViewport = isset($metaTagsAnalysis['has_viewport']) ? ($metaTagsAnalysis['has_viewport'] ? 1 : 0) : null;
        $hasRobots = isset($metaTagsAnalysis['has_robots']) ? ($metaTagsAnalysis['has_robots'] ? 1 : 0) : null;
        $hasCanonical = isset($metaTagsAnalysis['has_canonical']) ? ($metaTagsAnalysis['has_canonical'] ? 1 : 0) : null;
        $hasOgTags = isset($metaTagsAnalysis['has_og_tags']) ? ($metaTagsAnalysis['has_og_tags'] ? 1 : 0) : null;
        $hasTwitterCards = isset($metaTagsAnalysis['has_twitter_cards']) ? ($metaTagsAnalysis['has_twitter_cards'] ? 1 : 0) : null;
        
        $stmt = $conn->prepare("INSERT INTO meta_tags_analysis (website_id, page_url, title, title_length, meta_description, meta_description_length, meta_keywords, has_viewport, has_robots, has_canonical, has_og_tags, has_twitter_cards) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issisissiiis", $websiteId, $pageUrl, $title, $titleLength, $metaDescription, $metaDescriptionLength, $metaKeywords, $hasViewport, $hasRobots, $hasCanonical, $hasOgTags, $hasTwitterCards);
        $stmt->execute();
    }
    
    // Save on-page SEO data
    if ($onPageSeo) {
        $titleTagsScore = $onPageSeo['title_tags_score'] ?? null;
        $metaDescScore = $onPageSeo['meta_desc_score'] ?? null;
        $headerTagsScore = $onPageSeo['header_tags_score'] ?? null;
        $contentScore = $onPageSeo['content_score'] ?? null;
        $imageOptScore = $onPageSeo['image_optimization_score'] ?? null;
        $internalLinkingScore = $onPageSeo['internal_linking_score'] ?? null;
        $urlStructureScore = $onPageSeo['url_structure_score'] ?? null;
        
        $stmt = $conn->prepare("INSERT INTO onpage_seo (website_id, title_tags_score, meta_desc_score, header_tags_score, content_score, image_optimization_score, internal_linking_score, url_structure_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiiiiii", $websiteId, $titleTagsScore, $metaDescScore, $headerTagsScore, $contentScore, $imageOptScore, $internalLinkingScore, $urlStructureScore);
        $stmt->execute();
    }
    
    // Save off-page SEO data
    if ($offPageSeo) {
        $backlinkScore = $offPageSeo['backlink_score'] ?? null;
        $domainAuthority = $offPageSeo['domain_authority'] ?? null;
        $socialMediaScore = $offPageSeo['social_media_score'] ?? null;
        $toxicBacklinks = $offPageSeo['toxic_backlinks'] ?? null;
        
        $stmt = $conn->prepare("INSERT INTO offpage_seo (website_id, backlink_score, domain_authority, social_media_score, toxic_backlinks) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiii", $websiteId, $backlinkScore, $domainAuthority, $socialMediaScore, $toxicBacklinks);
        $stmt->execute();
    }
    
    // Save link analysis data
    if ($linkAnalysis) {
        // Check if we have page-specific data or just overall metrics
        if (isset($linkAnalysis['pages_data']) && is_array($linkAnalysis['pages_data'])) {
            // Process each page's data
            foreach ($linkAnalysis['pages_data'] as $pageData) {
                // Check if required fields exist
                if (!isset($pageData['url'])) {
                    continue;
                }
                
                $pageUrl = $pageData['url'];
                $totalLinks = 0;
                $internalLinks = 0;
                $externalLinks = 0;
                $brokenLinks = 0;
                $nofollowLinks = 0;
                
                // Count link types from links array if available
                if (isset($pageData['links']) && is_array($pageData['links'])) {
                    $totalLinks = count($pageData['links']);
                    
                    foreach ($pageData['links'] as $link) {
                        if (isset($link['type'])) {
                            if ($link['type'] === 'internal') {
                                $internalLinks++;
                            } else if ($link['type'] === 'external') {
                                $externalLinks++;
                            }
                        }
                        
                        if (isset($link['nofollow']) && $link['nofollow']) {
                            $nofollowLinks++;
                        }
                        
                        // Count broken links (we'd need status information)
                        if (isset($link['status']) && ($link['status'] >= 400 || $link['status'] === 'error')) {
                            $brokenLinks++;
                        }
                    }
                    
                    // Convert links to JSON for storage
                    $linksDetailsJson = json_encode($pageData['links']);
                } else {
                    // If links array isn't available, use the counts directly
                    $totalLinks = $pageData['total_links'] ?? 0;
                    $internalLinks = $pageData['internal_links'] ?? 0;
                    $externalLinks = $pageData['external_links'] ?? 0;
                    $brokenLinks = $pageData['broken_links'] ?? 0;
                    $nofollowLinks = $pageData['nofollow_links'] ?? 0;
                    $linksDetailsJson = null;
                }
                
                // Insert link analysis record
                $stmt = $conn->prepare("INSERT INTO link_analysis 
                                      (website_id, page_url, total_links, internal_links, 
                                       external_links, broken_links, nofollow_links, 
                                       links_details_json) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->bind_param(
                    "isiiiiis", 
                    $websiteId, 
                    $pageUrl, 
                    $totalLinks, 
                    $internalLinks, 
                    $externalLinks, 
                    $brokenLinks, 
                    $nofollowLinks, 
                    $linksDetailsJson
                );
                
                $stmt->execute();
            }
        } else if (isset($linkAnalysis['link_distribution']) && is_array($linkAnalysis['link_distribution'])) {
            // Alternative structure with link_distribution
            foreach ($linkAnalysis['link_distribution'] as $pageUrl => $distribution) {
                $totalLinks = ($distribution['internal'] ?? 0) + ($distribution['external'] ?? 0);
                $internalLinks = $distribution['internal'] ?? 0;
                $externalLinks = $distribution['external'] ?? 0;
                $nofollowLinks = $distribution['nofollow'] ?? 0;
                $brokenLinks = 0; // We don't have this information per page
                
                $stmt = $conn->prepare("INSERT INTO link_analysis 
                                      (website_id, page_url, total_links, internal_links, 
                                       external_links, broken_links, nofollow_links) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->bind_param(
                    "isiiiis", 
                    $websiteId, 
                    $pageUrl, 
                    $totalLinks, 
                    $internalLinks, 
                    $externalLinks, 
                    $brokenLinks,
                    $nofollowLinks
                );
                
                $stmt->execute();
            }
        } else {
            // Just save the overall metrics into a single record
            $pageUrl = $url; // Use the main URL
            $totalLinks = ($linkAnalysis['total_internal_links'] ?? 0) + ($linkAnalysis['total_external_links'] ?? 0);
            $internalLinks = $linkAnalysis['total_internal_links'] ?? 0;
            $externalLinks = $linkAnalysis['total_external_links'] ?? 0;
            $brokenLinks = $linkAnalysis['total_broken_links'] ?? 0;
            $nofollowLinks = $linkAnalysis['total_nofollow_links'] ?? 0;
            
            // Prepare broken links as JSON if available
            $linksDetailsJson = null;
            if (isset($linkAnalysis['broken_links']) && is_array($linkAnalysis['broken_links'])) {
                $linksDetailsJson = json_encode($linkAnalysis['broken_links']);
            }
            
            $stmt = $conn->prepare("INSERT INTO link_analysis 
                                  (website_id, page_url, total_links, internal_links, 
                                   external_links, broken_links, nofollow_links, 
                                   links_details_json) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param(
                "isiiiiis", 
                $websiteId, 
                $pageUrl, 
                $totalLinks, 
                $internalLinks, 
                $externalLinks, 
                $brokenLinks, 
                $nofollowLinks, 
                $linksDetailsJson
            );
            
            $stmt->execute();
        }
    }
    
    // Save image analysis data
    if ($imageAnalysis && isset($imageAnalysis['image_details']) && is_array($imageAnalysis['image_details'])) {
        foreach ($imageAnalysis['image_details'] as $pageData) {
            $pageUrl = $pageData['url'];
            $totalImages = count($pageData['images']);
            
            // Count various image types
            $imagesWithAlt = 0;
            $imagesWithoutAlt = 0;
            $oversizedImages = 0;
            $lazyLoadedImages = 0;
            
            foreach ($pageData['images'] as $image) {
                if ($image['has_alt']) {
                    $imagesWithAlt++;
                } else {
                    $imagesWithoutAlt++;
                }
                
                if ($image['is_oversized']) {
                    $oversizedImages++;
                }
                
                if ($image['uses_lazy_loading']) {
                    $lazyLoadedImages++;
                }
            }
            
            // Store the image details as JSON
            $imageDetailsJson = json_encode($pageData['images']);
            
            $stmt = $conn->prepare("INSERT INTO image_analysis 
                                   (website_id, page_url, total_images, images_with_alt, 
                                   images_without_alt, oversized_images, lazy_loaded_images, 
                                   image_details_json) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param(
                "isiiiiis", 
                $websiteId, 
                $pageUrl, 
                $totalImages, 
                $imagesWithAlt, 
                $imagesWithoutAlt, 
                $oversizedImages, 
                $lazyLoadedImages, 
                $imageDetailsJson
            );
            
            $stmt->execute();
        }
    }
    
    // Save keyword analysis data
    if ($keywordAnalysis && isset($keywordAnalysis['keywords'])) {
        $keywords = $keywordAnalysis['keywords'];
        
        $stmt = $conn->prepare("INSERT INTO keyword_analysis (website_id, keyword, search_volume, keyword_difficulty, current_ranking) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($keywords as $keyword) {
            $keywordText = $keyword['keyword'];
            $searchVolume = $keyword['search_volume'];
            $keywordDifficulty = $keyword['difficulty'];
            $currentRanking = $keyword['current_ranking'];
            
            $stmt->bind_param("isiii", $websiteId, $keywordText, $searchVolume, $keywordDifficulty, $currentRanking);
            $stmt->execute();
        }
    }
    
	
    // Save page structure data
    if ($pageStructure && isset($pageStructure['pages_data']) && is_array($pageStructure['pages_data'])) {
        foreach ($pageStructure['pages_data'] as $pageData) {
            $pageUrl = $pageData['url'];
            $hasH1 = isset($pageData['has_h1']) ? ($pageData['has_h1'] ? 1 : 0) : null;
            
            // Convert heading structure to JSON
            $headingStructureJson = null;
            if (isset($pageData['heading_structure'])) {
                $headingStructureJson = json_encode($pageData['heading_structure']);
            }
            
            // Calculate content to HTML ratio
            $contentHtmlRatio = $pageData['content_html_ratio'] ?? 0;
            
            // Get main content word count
            $mainContentWordCount = $pageData['main_content_word_count'] ?? 0;
            
            // Check for schema markup
            $hasSchemaMarkup = isset($pageData['has_schema_markup']) ? ($pageData['has_schema_markup'] ? 1 : 0) : 0;
            
            // Convert schema types to JSON
            $schemaTypesJson = null;
            if (isset($pageData['schema_types']) && is_array($pageData['schema_types'])) {
                $schemaTypesJson = json_encode($pageData['schema_types']);
            }
            
            // Insert page structure data
            $stmt = $conn->prepare("INSERT INTO page_structure 
                                  (website_id, page_url, has_h1, heading_structure_json, 
                                  content_html_ratio, main_content_word_count, has_schema_markup, 
                                  schema_types_json) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param(
                "isisdiis", 
                $websiteId, 
                $pageUrl, 
                $hasH1, 
                $headingStructureJson, 
                $contentHtmlRatio, 
                $mainContentWordCount, 
                $hasSchemaMarkup, 
                $schemaTypesJson
            );
            
            $stmt->execute();
        }
    }
	
	
    // Save Core Web Vitals (Performance) data
    if ($coreWebVitals && !empty($coreWebVitals)) {
        // Check if the performance_metrics table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'performance_metrics'");
        if ($tableCheck->num_rows === 0) {
            // Create the table if it doesn't exist
            $createTableSQL = "CREATE TABLE IF NOT EXISTS performance_metrics (
                id INT AUTO_INCREMENT PRIMARY KEY,
                website_id INT NOT NULL,
                scan_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                page_url VARCHAR(255),
                overall_score INT,
                lcp_score INT,
                fid_score INT,
                cls_score INT,
                ttfb_score INT,
                performance_json LONGTEXT,
                recommendations_json LONGTEXT,
                FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE
            )";
            $conn->query($createTableSQL);
        }
        
        // Extract and prepare performance data
        $overallScore = $coreWebVitals['overall_score'] ?? 0;
        $lcpScore = isset($coreWebVitals['metrics']['lcp']['score']) ? $coreWebVitals['metrics']['lcp']['score'] : 0;
        $fidScore = isset($coreWebVitals['metrics']['fid']['score']) ? $coreWebVitals['metrics']['fid']['score'] : 0;
        $clsScore = isset($coreWebVitals['metrics']['cls']['score']) ? $coreWebVitals['metrics']['cls']['score'] : 0;
        $ttfbScore = isset($coreWebVitals['metrics']['ttfb']['score']) ? $coreWebVitals['metrics']['ttfb']['score'] : 0;
        
        // Prepare JSON data
        $performanceJson = json_encode($coreWebVitals['metrics'] ?? []);
        $recommendationsJson = json_encode($coreWebVitals['recommendations'] ?? []);
        
        // Insert performance metrics data
        $stmt = $conn->prepare("INSERT INTO performance_metrics 
                              (website_id, page_url, overall_score, lcp_score, 
                              fid_score, cls_score, ttfb_score, performance_json, 
                              recommendations_json) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $pageUrl = $url; // Use the main URL
        
        $stmt->bind_param(
            "isiiiiiss", 
            $websiteId, 
            $pageUrl,
            $overallScore, 
            $lcpScore, 
            $fidScore, 
            $clsScore, 
            $ttfbScore, 
            $performanceJson, 
            $recommendationsJson
        );
        
        $stmt->execute();
    }

    // Save competitor analysis data
    if ($competitor && !empty($competitor)) {
        // First, check if the competitor_analysis table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'competitor_analysis'");
        if ($tableCheck->num_rows === 0) {
            // Create the table if it doesn't exist
            $createTableSQL = "CREATE TABLE IF NOT EXISTS competitor_analysis (
                id INT AUTO_INCREMENT PRIMARY KEY,
                website_id INT NOT NULL,
                scan_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                overall_score INT,
                competitors_json LONGTEXT,
                keyword_gaps_json LONGTEXT,
                backlink_gaps_json LONGTEXT,
                content_gaps_json LONGTEXT,
                recommendations_json LONGTEXT,
                FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE
            )";
            $conn->query($createTableSQL);
        }
        
        // Extract and prepare data
        $overallScore = $competitor['overall_score'] ?? 0;
        
        // Prepare JSON data
        $competitorsJson = null;
        if (isset($competitor['competitors']) && is_array($competitor['competitors'])) {
            $competitorsJson = json_encode($competitor['competitors']);
        }
        
        $keywordGapsJson = null;
        if (isset($competitor['keyword_gaps']) || isset($competitor['keyword_gap'])) {
            $keywordGapsJson = json_encode($competitor['keyword_gaps'] ?? $competitor['keyword_gap'] ?? []);
        }
        
        $backlinkGapsJson = null;
        if (isset($competitor['backlink_gaps']) || isset($competitor['backlink_opportunities'])) {
            $backlinkGapsJson = json_encode($competitor['backlink_gaps'] ?? $competitor['backlink_opportunities'] ?? []);
        }
        
        $contentGapsJson = null;
        if (isset($competitor['content_gaps']) || isset($competitor['content_gap'])) {
            $contentGapsJson = json_encode($competitor['content_gaps'] ?? $competitor['content_gap'] ?? []);
        }
        
        $recommendationsJson = null;
        if (isset($competitor['recommendations']) && is_array($competitor['recommendations'])) {
            $recommendationsJson = json_encode($competitor['recommendations']);
        } else if (isset($competitor['opportunities']) && is_array($competitor['opportunities'])) {
            $recommendationsJson = json_encode($competitor['opportunities']);
        } else if (isset($competitor['summary'])) {
            // If we only have summary data, convert it to a recommendations structure
            $recommendationsJson = json_encode([
                [
                    'description' => $competitor['summary'],
                    'priority' => 'medium'
                ]
            ]);
        }
        
        // Insert competitor analysis data
        $stmt = $conn->prepare("INSERT INTO competitor_analysis 
                              (website_id, overall_score, competitors_json, 
                              keyword_gaps_json, backlink_gaps_json, content_gaps_json, 
                              recommendations_json) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param(
            "iisssss", 
            $websiteId, 
            $overallScore, 
            $competitorsJson, 
            $keywordGapsJson, 
            $backlinkGapsJson, 
            $contentGapsJson, 
            $recommendationsJson
        );
        
        $stmt->execute();
    }

    // Save schema markup data
    if ($schema && !empty($schema)) {
        // Check if the schema_markup table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'schema_markup'");
        if ($tableCheck->num_rows === 0) {
            // Create the table if it doesn't exist
            $createTableSQL = "CREATE TABLE IF NOT EXISTS schema_markup (
                id INT AUTO_INCREMENT PRIMARY KEY,
                website_id INT NOT NULL,
                scan_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                page_url VARCHAR(255),
                overall_score INT,
                has_schema TINYINT(1),
                schema_types_json LONGTEXT,
                validation_issues_json LONGTEXT,
                recommendations_json LONGTEXT,
                FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE
            )";
            $conn->query($createTableSQL);
        }
        
        // Handle saving schema data to database
        $overallScore = $schema['overall_score'] ?? 0;
        $hasSchema = isset($schema['has_schema']) ? ($schema['has_schema'] ? 1 : 0) : 0;
        
        // Prepare JSON data
        $schemaTypesJson = null;
        if (isset($schema['schema_types']) && is_array($schema['schema_types'])) {
            $schemaTypesJson = json_encode($schema['schema_types']);
        }
        
        $validationIssuesJson = null;
        if (isset($schema['validation_issues']) && is_array($schema['validation_issues'])) {
            $validationIssuesJson = json_encode($schema['validation_issues']);
        }
        
        $recommendationsJson = null;
        if (isset($schema['recommendations']) && is_array($schema['recommendations'])) {
            $recommendationsJson = json_encode($schema['recommendations']);
        }
        
        // Insert schema markup data
        $stmt = $conn->prepare("INSERT INTO schema_markup 
                              (website_id, page_url, overall_score, has_schema, 
                              schema_types_json, validation_issues_json, recommendations_json) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $pageUrl = $url; // Use the main URL
        
        $stmt->bind_param(
            "isiisss", 
            $websiteId, 
            $pageUrl,
            $overallScore, 
            $hasSchema, 
            $schemaTypesJson, 
            $validationIssuesJson, 
            $recommendationsJson
        );
        
        $stmt->execute();
    }
	
	
// Save Local SEO data
if ($localSeo && !empty($localSeo)) {
    // Check if the local_seo table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'local_seo'");
    if ($tableCheck->num_rows === 0) {
        // Create the table if it doesn't exist
        $createTableSQL = "CREATE TABLE IF NOT EXISTS local_seo (
            id INT AUTO_INCREMENT PRIMARY KEY,
            website_id INT NOT NULL,
            scan_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            overall_score INT,
            has_google_business_profile TINYINT(1),
            has_local_business_schema TINYINT(1),
            has_location_pages TINYINT(1),
            has_nap_consistency TINYINT(1),
            has_local_keywords TINYINT(1),
            has_embedded_map TINYINT(1),
            citation_score INT,
            google_business_data_json LONGTEXT,
            citations_json LONGTEXT,
            nap_data_json LONGTEXT,
            issues_json LONGTEXT,
            recommendations_json LONGTEXT,
            FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE
        )";
        $conn->query($createTableSQL);
    }
    
    // Extract and prepare data
    $overallScore = $localSeo['overall_score'] ?? 0;
    $hasGbp = isset($localSeo['has_google_business_profile']) ? ($localSeo['has_google_business_profile'] ? 1 : 0) : 0;
    $hasLocalSchema = isset($localSeo['has_local_business_schema']) ? ($localSeo['has_local_business_schema'] ? 1 : 0) : 0;
    $hasLocationPages = isset($localSeo['has_location_pages']) ? ($localSeo['has_location_pages'] ? 1 : 0) : 0;
    $hasNapConsistency = isset($localSeo['has_nap_consistency']) ? ($localSeo['has_nap_consistency'] ? 1 : 0) : 0;
    $hasLocalKeywords = isset($localSeo['has_local_keywords']) ? ($localSeo['has_local_keywords'] ? 1 : 0) : 0;
    $hasEmbeddedMap = isset($localSeo['has_embedded_map']) ? ($localSeo['has_embedded_map'] ? 1 : 0) : 0;
    $citationScore = $localSeo['citation_score'] ?? 0;
    
    // Prepare JSON data
    $gbpJson = isset($localSeo['google_business_data']) ? json_encode($localSeo['google_business_data']) : null;
    $citationsJson = isset($localSeo['local_citations']) ? json_encode($localSeo['local_citations']) : null;
    $napJson = isset($localSeo['nap_data']) ? json_encode($localSeo['nap_data']) : null;
    $issuesJson = isset($localSeo['issues']) ? json_encode($localSeo['issues']) : null;
    $recommendationsJson = isset($localSeo['recommendations']) ? json_encode($localSeo['recommendations']) : null;
    
    // Insert local SEO data
    $stmt = $conn->prepare("INSERT INTO local_seo 
                          (website_id, overall_score, has_google_business_profile, 
                          has_local_business_schema, has_location_pages, has_nap_consistency, 
                          has_local_keywords, has_embedded_map, citation_score, 
                          google_business_data_json, citations_json, nap_data_json, 
                          issues_json, recommendations_json) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
		$types = "iiiiiiiiisssss"; // 9 'i's and 5 's's = 14 types
		$stmt->bind_param(
			$types,
			$websiteId, 
			$overallScore, 
			$hasGbp, 
			$hasLocalSchema, 
			$hasLocationPages, 
			$hasNapConsistency, 
			$hasLocalKeywords, 
			$hasEmbeddedMap, 
			$citationScore, 
			$gbpJson, 
			$citationsJson, 
			$napJson, 
			$issuesJson, 
			$recommendationsJson
		);
    
    $stmt->execute();
}

// Save Content Gap data
if ($contentGap && !empty($contentGap)) {
    // Check if the content_gap table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'content_gap'");
    if ($tableCheck->num_rows === 0) {
        // Create the table if it doesn't exist
        $createTableSQL = "CREATE TABLE IF NOT EXISTS content_gap (
            id INT AUTO_INCREMENT PRIMARY KEY,
            website_id INT NOT NULL,
            scan_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            overall_score INT,
            opportunity_score INT,
            content_length_gap_percentage FLOAT,
            topics_covered_json LONGTEXT,
            missing_topics_json LONGTEXT,
            keyword_gaps_json LONGTEXT,
            competitor_data_json LONGTEXT,
            content_length_comparison_json LONGTEXT,
            recommendations_json LONGTEXT,
            FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE
        )";
        $conn->query($createTableSQL);
    }
    
    // Extract and prepare data
    $overallScore = $contentGap['overall_score'] ?? 0;
    $opportunityScore = $contentGap['opportunity_score'] ?? 0;
    $contentLengthGapPercentage = 0;
    
    if (isset($contentGap['content_length_comparison']) && 
        isset($contentGap['content_length_comparison']['length_gap_percentage'])) {
        $contentLengthGapPercentage = $contentGap['content_length_comparison']['length_gap_percentage'];
    }
    
    // Prepare JSON data
    $topicsCoveredJson = isset($contentGap['topics_covered']) ? json_encode($contentGap['topics_covered']) : null;
    $missingTopicsJson = isset($contentGap['missing_topics']) ? json_encode($contentGap['missing_topics']) : null;
    $keywordGapsJson = isset($contentGap['keyword_gaps']) ? json_encode($contentGap['keyword_gaps']) : null;
    $competitorDataJson = isset($contentGap['competitor_data']) ? json_encode($contentGap['competitor_data']) : null;
    $contentLengthJson = isset($contentGap['content_length_comparison']) ? json_encode($contentGap['content_length_comparison']) : null;
    $recommendationsJson = isset($contentGap['recommendations']) ? json_encode($contentGap['recommendations']) : null;
    
    // Insert content gap data
    $stmt = $conn->prepare("INSERT INTO content_gap 
                          (website_id, overall_score, opportunity_score, 
                          content_length_gap_percentage, topics_covered_json, 
                          missing_topics_json, keyword_gaps_json, competitor_data_json, 
                          content_length_comparison_json, recommendations_json) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param(
        "iiidssssss", 
        $websiteId, 
        $overallScore, 
        $opportunityScore, 
        $contentLengthGapPercentage, 
        $topicsCoveredJson, 
        $missingTopicsJson, 
        $keywordGapsJson, 
        $competitorDataJson, 
        $contentLengthJson, 
        $recommendationsJson
    );
    
    $stmt->execute();
}	
	
	
	
	
	
	
    
    // Save content analysis data
    if ($contentAnalysis) {
        // Different possible data structures
        if (isset($contentAnalysis['main_page_data'])) {
            // Structure with main_page_data
            $stmt = $conn->prepare("INSERT INTO content_analysis (website_id, page_url, keyword_density, readability_score, content_length) VALUES (?, ?, ?, ?, ?)");
            
            // Insert main page data
            $pageUrl = $contentAnalysis['main_page_data']['url'] ?? $url;
            
            // Default values if not all data is present
            $keywordDensity = 0;
            $readabilityScore = $contentAnalysis['main_page_data']['readability_score'] ?? $contentAnalysis['avg_readability'] ?? 0;
            $contentLength = $contentAnalysis['main_page_data']['word_count'] ?? $contentAnalysis['avg_word_count'] ?? 0;
            
            // If top_keywords exists and has values, use the first one for keyword density
            if (isset($contentAnalysis['main_page_data']['top_keywords']) && 
                is_array($contentAnalysis['main_page_data']['top_keywords']) && 
                !empty($contentAnalysis['main_page_data']['top_keywords'])) {
                
                // Use the first keyword's density if available
                if (isset($contentAnalysis['main_page_data']['keyword_density'])) {
                    $keywordDensity = $contentAnalysis['main_page_data']['keyword_density'];
                }
            }
            
            $stmt->bind_param("isdii", $websiteId, $pageUrl, $keywordDensity, $readabilityScore, $contentLength);
            $stmt->execute();
            
            // If there are additional content page data, save those too
            if (isset($contentAnalysis['additional_pages']) && is_array($contentAnalysis['additional_pages'])) {
                foreach ($contentAnalysis['additional_pages'] as $page) {
                    if (isset($page['url'])) {
                        $pageUrl = $page['url'];
                        $keywordDensity = $page['keyword_density'] ?? 0;
                        $readabilityScore = $page['readability_score'] ?? 0;
                        $contentLength = $page['word_count'] ?? 0;
                        
                        $stmt->bind_param("isdii", $websiteId, $pageUrl, $keywordDensity, $readabilityScore, $contentLength);
                        $stmt->execute();
                    }
                }
            }
        } else {
            // Simpler structure with just averages
            $stmt = $conn->prepare("INSERT INTO content_analysis (website_id, page_url, readability_score, content_length) VALUES (?, ?, ?, ?)");
            
            $pageUrl = $url; // Use main URL
            $readabilityScore = $contentAnalysis['avg_readability'] ?? 0;
            $contentLength = $contentAnalysis['avg_word_count'] ?? 0;
            
            $stmt->bind_param("isii", $websiteId, $pageUrl, $readabilityScore, $contentLength);
            $stmt->execute();
        }
    }
    
    // Save AI recommendations if available
    if ($aiRecommendations) {
        // Check if table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'ai_recommendations'");
        if ($tableCheck->num_rows > 0) {
            $summary = $aiRecommendations['summary'] ?? null;
            $recommendationsJson = json_encode($aiRecommendations['recommendations'] ?? []);
            $modelId = $aiRecommendations['model_id'] ?? 1; // Default to ID 1 if not specified
            
            $stmt = $conn->prepare("INSERT INTO ai_recommendations (website_id, ai_model_id, summary, recommendations_json) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $websiteId, $modelId, $summary, $recommendationsJson);
            $stmt->execute();
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Results saved successfully',
        'websiteId' => $websiteId
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save results: ' . $e->getMessage()
    ]);
}