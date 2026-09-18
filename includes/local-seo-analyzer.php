<?php
/**
 * local-seo-analyzer.php - Analyzes local SEO factors for a website
 */

// Include Google API Client

class LocalSEOAnalyzer {
    /**
     * Analyze local SEO for a given URL
     * @param string $url Website URL to analyze
     * @return array Analysis results
     */
    public static function analyze($url) {
        // Initialize results structure
        $results = [
            'overall_score' => 0,
            'has_google_business_profile' => false,
            'has_local_business_schema' => false,
            'has_location_pages' => false,
            'has_local_keywords' => false,
            'has_city_in_title_tags' => false,
            'has_embedded_map' => false,
            'has_nap_consistency' => false,
            'local_citations' => [],
            'local_directories' => [],
            'review_data' => [
                'total_reviews' => 0,
                'average_rating' => 0,
                'review_sentiment' => 'neutral',
                'has_review_schema' => false
            ],
            'issues' => [],
            'recommendations' => []
        ];
        
        // Extract domain for API lookups
        $domain = parse_url($url, PHP_URL_HOST);
        if (substr($domain, 0, 4) === 'www.') {
            $domain = substr($domain, 4);
        }
        
        // Get real Google Business Profile data
        $gbpData = self::getGoogleBusinessProfile($domain, $url);
        $results['has_google_business_profile'] = $gbpData['exists'];
        
        if ($gbpData['exists']) {
            $results['google_business_data'] = $gbpData;
            $results['review_data']['total_reviews'] = $gbpData['total_reviews'];
            $results['review_data']['average_rating'] = $gbpData['average_rating'];
        } else {
            $results['issues'][] = [
                'description' => 'No Google Business Profile found',
                'severity' => 'high',
                'details' => $gbpData['error'] ?? 'A Google Business Profile is essential for local SEO'
            ];
            
            $results['recommendations'][] = [
                'title' => 'Create Google Business Profile',
                'description' => 'Create and verify a Google Business Profile for your business',
                'importance' => 'high',
                'implementation' => 'Go to business.google.com and follow the steps to create and verify your business'
            ];
        }
        
        // Check for local business schema
        $schemaData = self::checkLocalBusinessSchema($url);
        $results['has_local_business_schema'] = $schemaData['exists'];
        $results['schema_data'] = $schemaData;
        
        if (!$schemaData['exists']) {
            $results['issues'][] = [
                'description' => 'Missing LocalBusiness schema markup',
                'severity' => 'medium',
                'details' => 'LocalBusiness schema helps search engines understand business information'
            ];
            
            $schemaExample = self::generateSchemaExample($domain, $gbpData);
            
            $results['recommendations'][] = [
                'title' => 'Implement LocalBusiness Schema',
                'description' => 'Add structured data markup for your local business',
                'importance' => 'medium',
                'implementation' => "Implement JSON-LD schema markup with your business name, address, phone number, and business hours.\n\nExample code:\n" . $schemaExample
            ];
        }
        
        // Check for location-specific pages
        $locationPages = self::checkLocationPages($url);
        $results['has_location_pages'] = $locationPages['exists'];
        $results['location_pages'] = $locationPages['pages'];
        
        if (!$locationPages['exists']) {
            $results['issues'][] = [
                'description' => 'No location-specific pages found',
                'severity' => 'medium',
                'details' => 'Location pages help target local searches for specific areas'
            ];
            
            $results['recommendations'][] = [
                'title' => 'Create Location Pages',
                'description' => 'Create dedicated pages for each location you serve',
                'importance' => 'medium',
                'implementation' => "Create unique content for each city/area you serve with local information, testimonials, and services.\n\nExample structure:\n• example.com/locations/city-name\n• example.com/service-area/city-name"
            ];
        }
        
        // Check for NAP consistency using Google Business data as the source of truth
        $napConsistency = self::checkNAPConsistency($domain, $gbpData);
        $results['has_nap_consistency'] = $napConsistency['consistent'];
        $results['nap_data'] = $napConsistency;
        
        if (!$napConsistency['consistent']) {
            $results['issues'][] = [
                'description' => 'Inconsistent NAP (Name, Address, Phone) information found across the web',
                'severity' => 'high',
                'details' => 'Consistent NAP information is crucial for local SEO performance'
            ];
            
            $inconsistencyList = "";
            foreach ($napConsistency['inconsistencies'] as $inconsistency) {
                $inconsistencyList .= "• {$inconsistency['source']}: {$inconsistency['field']} listed as \"{$inconsistency['found']}\" instead of \"{$inconsistency['expected']}\"\n";
            }
            
            $results['recommendations'][] = [
                'title' => 'Fix NAP Inconsistencies',
                'description' => 'Ensure consistent business information across all platforms',
                'importance' => 'high',
                'implementation' => "Update business information on all directories and citation sources to match your Google Business Profile exactly.\n\nInconsistencies found:\n" . $inconsistencyList
            ];
        }
        
        // Check for local citations
        $citations = self::checkLocalCitations($domain, $gbpData);
        $results['local_citations'] = $citations['citations'];
        $results['citation_score'] = $citations['score'];
        
        if ($citations['score'] < 60) {
            $results['issues'][] = [
                'description' => 'Limited local citations found',
                'severity' => 'medium',
                'details' => 'Local citations help improve local search visibility'
            ];
            
            $missingDirectories = "";
            foreach ($citations['missing_directories'] as $directory) {
                $missingDirectories .= "• {$directory}\n";
            }
            
            $results['recommendations'][] = [
                'title' => 'Build Local Citations',
                'description' => 'Increase your presence in local directories and citation sources',
                'importance' => 'medium',
                'implementation' => "Submit your business to these important directories:\n" . $missingDirectories
            ];
        }
        
        // Check for local keywords in content
        $localKeywords = self::checkLocalKeywords($url, $gbpData);
        $results['has_local_keywords'] = $localKeywords['exists'];
        $results['local_keywords_data'] = $localKeywords;
        
        if (!$localKeywords['exists']) {
            $results['issues'][] = [
                'description' => 'Few or no local keywords found in content',
                'severity' => 'medium',
                'details' => 'Local keywords help target local search queries'
            ];
            
            $locationTerm = '';
            if ($gbpData['exists'] && isset($gbpData['address'])) {
                $addressParts = explode(',', $gbpData['address']);
                $locationTerm = trim($addressParts[1] ?? ''); // Usually the city
            }
            
            $results['recommendations'][] = [
                'title' => 'Optimize Content with Local Keywords',
                'description' => 'Add location-specific keywords to your content',
                'importance' => 'medium',
                'implementation' => "Incorporate city, neighborhood, and regional terms naturally throughout your content, particularly in titles, headings, and meta descriptions.\n\nExample keywords to use:\n• Services in {$locationTerm}\n• {$locationTerm} provider\n• Best [service] in {$locationTerm}"
            ];
        }
        
        // Check for embedded Google Maps
        $hasMap = self::checkEmbeddedMap($url);
        $results['has_embedded_map'] = $hasMap;
        
        if (!$hasMap) {
            $results['issues'][] = [
                'description' => 'No embedded Google Map found',
                'severity' => 'low',
                'details' => 'Embedded maps improve user experience for local visitors'
            ];
            
            $mapCode = '';
            if ($gbpData['exists'] && isset($gbpData['placeId'])) {
                $mapCode = '<iframe src="https://www.google.com/maps/embed/v1/place?key=YOUR_API_KEY&q=place_id:' . $gbpData['placeId'] . '" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>';
            } else {
                $mapCode = '<iframe src="https://www.google.com/maps/embed/v1/place?key=YOUR_API_KEY&q=YOUR_BUSINESS_ADDRESS" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>';
            }
            
            $results['recommendations'][] = [
                'title' => 'Add Google Map Embed',
                'description' => 'Embed a Google Map on your contact page',
                'importance' => 'low',
                'implementation' => "Add a Google Maps embed on your contact page to help visitors find your location easily.\n\nExample code:\n" . $mapCode
            ];
        }
        
        // Calculate overall score
        $results['overall_score'] = self::calculateScore($results);
        
        return $results;
    }
    
    /**
     * Get Google Business Profile data using the API
     * @param string $domain Website domain
     * @param string $url Full website URL
     * @return array Google Business Profile data
     */
    private static function getGoogleBusinessProfile($domain, $url) {
        try {
            // Try to use the Google Business API if configured and cURL is available
            if (defined('GOOGLE_BUSINESS_API_KEY') && !empty(GOOGLE_BUSINESS_API_KEY) && function_exists('curl_init')) {
                $apiKey = GOOGLE_BUSINESS_API_KEY;
                
                // API endpoint for Place Search
                $searchUrl = "https://places.googleapis.com/v1/places:searchText";
                
                // Prepare request data
                $requestData = [
                    'textQuery' => $domain
                ];
                
                // Create cURL request
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $searchUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'X-Goog-Api-Key: ' . $apiKey,
                    'X-Goog-FieldMask: places.id,places.displayName,places.formattedAddress,places.phoneNumber,places.rating,places.userRatingCount,places.websiteUri'
                ]);
                
                // Execute the request
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                // Check if the request was successful
                if ($httpCode == 200 && $response) {
                    $data = json_decode($response, true);
                    
                    // Check if we found any places
                    if (isset($data['places']) && count($data['places']) > 0) {
                        // Get the first place (most relevant)
                        $place = $data['places'][0];
                        $placeId = $place['id'];
                        
                        // Get detailed information about the place
                        $detailUrl = "https://places.googleapis.com/v1/places/" . $placeId;
                        
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $detailUrl);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'X-Goog-Api-Key: ' . $apiKey,
                            'X-Goog-FieldMask: id,displayName,formattedAddress,internationalPhoneNumber,phoneNumber,rating,userRatingCount,websiteUri,regularOpeningHours,businessStatus,types'
                        ]);
                        
                        $detailResponse = curl_exec($ch);
                        $detailHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        
                        if ($detailHttpCode == 200 && $detailResponse) {
                            $detailData = json_decode($detailResponse, true);
                            
                            // Convert business hours to a consistent format
                            $businessHours = [];
                            $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                            
                            if (isset($detailData['regularOpeningHours']) && isset($detailData['regularOpeningHours']['periods'])) {
                                foreach ($daysOfWeek as $index => $day) {
                                    $businessHours[$day] = 'Closed';
                                    
                                    foreach ($detailData['regularOpeningHours']['periods'] as $period) {
                                        if ($period['open']['day'] == $index + 1) {
                                            $openTime = isset($period['open']['hour']) ? 
                                                        sprintf("%02d:%02d %s", 
                                                               $period['open']['hour'] > 12 ? $period['open']['hour'] - 12 : $period['open']['hour'],
                                                               $period['open']['minute'],
                                                               $period['open']['hour'] >= 12 ? 'PM' : 'AM') : '';
                                            
                                            $closeTime = isset($period['close']['hour']) ? 
                                                         sprintf("%02d:%02d %s", 
                                                                $period['close']['hour'] > 12 ? $period['close']['hour'] - 12 : $period['close']['hour'],
                                                                $period['close']['minute'],
                                                                $period['close']['hour'] >= 12 ? 'PM' : 'AM') : '';
                                            
                                            $businessHours[$day] = $openTime . ' - ' . $closeTime;
                                            break;
                                        }
                                    }
                                }
                            }
                            
                            // Extract categories from types field
                            $categories = isset($detailData['types']) ? $detailData['types'] : [];
                            
                            // Format for better display
                            $formattedCategories = [];
                            foreach ($categories as $category) {
                                $formattedCategories[] = ucwords(str_replace('_', ' ', $category));
                            }
                            
                            return [
                                'exists' => true,
                                'name' => $detailData['displayName'],
                                'address' => $detailData['formattedAddress'],
                                'phone' => $detailData['phoneNumber'] ?? $detailData['internationalPhoneNumber'] ?? '',
                                'categories' => $formattedCategories,
                                'total_reviews' => $detailData['userRatingCount'] ?? 0,
                                'average_rating' => $detailData['rating'] ?? 0,
                                'verified' => $detailData['businessStatus'] === 'OPERATIONAL',
                                'has_photos' => true, // Assuming it has photos if it exists
                                'photo_count' => 0, // We'd need additional API calls to get this
                                'business_hours' => $businessHours,
                                'placeId' => $placeId
                            ];
                        }
                    }
                }
                
                // If we reach here, the API call failed or we didn't find a business
                error_log("Google Places API error: " . ($response ? $response : "No response"));
            }
            
            // Fallback to web scraping as a last resort (with proper disclaimer)
            $websiteHtml = @file_get_contents($url);
            if ($websiteHtml) {
                // Check if there's schema markup that might indicate it's a local business
                if (strpos($websiteHtml, '"@type":"LocalBusiness"') !== false || 
                    strpos($websiteHtml, '"@type": "LocalBusiness"') !== false) {
                    
                    // Try to extract business name from title
                    preg_match('/<title>(.*?)<\/title>/i', $websiteHtml, $titleMatches);
                    $businessName = $titleMatches[1] ?? $domain;
                    
                    // Try to find contact information
                    preg_match('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i', $websiteHtml, $emailMatches);
                    $email = $emailMatches[0] ?? '';
                    
                    // Phone number regex pattern
                    $phonePattern = '/(\+\d{1,3}[-.\s]?)?(\(?\d{3}\)?[-.\s]?)?\d{3}[-.\s]?\d{4}/';
                    preg_match($phonePattern, $websiteHtml, $phoneMatches);
                    $phone = $phoneMatches[0] ?? '';
                    
                    return [
                        'exists' => true,
                        'name' => $businessName,
                        'address' => 'Not found automatically',
                        'phone' => $phone,
                        'categories' => ['Local Business'],
                        'total_reviews' => 0,
                        'average_rating' => 0,
                        'verified' => false,
                        'has_photos' => false,
                        'photo_count' => 0,
                        'business_hours' => [],
                        'fallback' => true, // Indicate this is fallback data, not from API
                        'warning' => 'Business profile data estimated from website content. Verify accuracy manually.'
                    ];
                }
            }
            
            // If all methods fail, return not found
            return [
                'exists' => false,
                'error' => 'Could not find a Google Business Profile associated with this domain.'
            ];
        } catch (Exception $e) {
            error_log("Error checking Google Business Profile: " . $e->getMessage());
            
            // Return a failure state that won't mislead the user
            return [
                'exists' => false,
                'error' => 'Failed to retrieve Google Business Profile data: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if the website has LocalBusiness schema markup
     * @param string $url Website URL
     * @return array Schema data
     */
     private static function checkLocalBusinessSchema($url) {
        try {
            // Get webpage content
            $html = @file_get_contents($url);
            
            if (!$html) {
                return [
                    'exists' => false,
                    'error' => 'Could not access the webpage'
                ];
            }
            
            // Look for JSON-LD schema
            $hasLocalBusinessSchema = false;
            $schemaType = null;
            $isComplete = false;
            
            // Pattern to match JSON-LD script tags
            preg_match_all('/<script[^>]*type=(["\'])application\/ld\+json\1[^>]*>(.*?)<\/script>/si', $html, $matches);
            
            if (!empty($matches[2])) {
                foreach ($matches[2] as $jsonLd) {
                    // Try to decode the JSON
                    $schema = json_decode($jsonLd, true);
                    
                    if ($schema) {
                        // Check for @type or type property
                        $type = $schema['@type'] ?? $schema['type'] ?? null;
                        
                        // Handle array of types
                        if (is_array($type)) {
                            $type = array_shift($type);
                        }
                        
                        // Check if it's LocalBusiness or a subtype
                        if ($type === 'LocalBusiness' || 
                            in_array($type, [
                                'Restaurant', 'Store', 'Hotel', 'AutomotiveBusiness',
                                'HealthAndBeautyBusiness', 'ProfessionalService',
                                'HomeAndConstructionBusiness', 'MedicalBusiness'
                            ])) {
                            $hasLocalBusinessSchema = true;
                            $schemaType = $type;
                            
                            // Check if it has all required properties
                            $requiredProps = ['name', 'address', 'telephone'];
                            $hasAllRequired = true;
                            
                            foreach ($requiredProps as $prop) {
                                if (!isset($schema[$prop]) && !isset($schema["@$prop"])) {
                                    $hasAllRequired = false;
                                    break;
                                }
                            }
                            
                            $isComplete = $hasAllRequired;
                            
                            // Additional checks for completion
                            $hasGeo = isset($schema['geo']) || isset($schema['@geo']);
                            $hasOpeningHours = isset($schema['openingHours']) || isset($schema['@openingHours']);
                            $hasPriceRange = isset($schema['priceRange']) || isset($schema['@priceRange']);
                            
                            break; // Found what we needed
                        }
                    }
                }
            }
            
            return [
                'exists' => $hasLocalBusinessSchema,
                'type' => $schemaType,
                'complete' => $isComplete,
                'has_geo_coordinates' => $hasGeo ?? false,
                'has_opening_hours' => $hasOpeningHours ?? false,
                'has_price_range' => $hasPriceRange ?? false,
                'has_contact_info' => $isComplete
            ];
        } catch (Exception $e) {
            error_log("Error checking LocalBusiness schema: " . $e->getMessage());
            
            return [
                'exists' => false,
                'error' => 'Failed to check for LocalBusiness schema: ' . $e->getMessage()
            ];
        }
    }
    
    
    /**
     * Generate schema example based on business data
     * @param string $domain Website domain
     * @param array $businessData Business data from Google
     * @return string JSON-LD schema example
     */
    private static function generateSchemaExample($domain, $businessData) {
        // Default values
        $name = $businessData['name'] ?? 'Your Business Name';
        $address = $businessData['address'] ?? '123 Main St, Anytown, CA 12345';
        $phone = $businessData['phone'] ?? '(555) 123-4567';
        
        // Extract address components
        $addressParts = explode(',', $address);
        $streetAddress = trim($addressParts[0] ?? '');
        $cityState = explode(' ', trim($addressParts[1] ?? ''));
        $city = trim(implode(' ', array_slice($cityState, 0, -1)));
        $state = trim(end($cityState));
        $zip = trim($addressParts[2] ?? '');
        
        // Generate example schema
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $name,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $streetAddress,
                'addressLocality' => $city,
                'addressRegion' => $state,
                'postalCode' => $zip,
                'addressCountry' => 'US'
            ],
            'telephone' => $phone,
            'url' => 'https://' . $domain,
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => '00.0000', // Replace with actual coordinates
                'longitude' => '00.0000' // Replace with actual coordinates
            ],
            'openingHours' => [
                'Mo-Fr 09:00-17:00',
                'Sa 10:00-15:00',
                'Su Closed'
            ],
            'priceRange' => '$$'
        ];
        
        // Format JSON nicely
        return json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Check if the website has location-specific pages
     * @param string $url Website URL
     * @return array Location pages data
     */
    private static function checkLocationPages($url) {
        try {
            // Get webpage content
            $html = @file_get_contents($url);
            
            if (!$html) {
                return [
                    'exists' => false,
                    'error' => 'Could not access the webpage'
                ];
            }
            
            // Get base domain
            $parsedUrl = parse_url($url);
            $base = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            
            // Look for links that might be location pages
            preg_match_all('/<a[^>]+href=(["\'])([^"\']+)\1[^>]*>(.*?)<\/a>/si', $html, $matches);
            
            $locationPages = [];
            $locationKeywords = ['location', 'locations', 'service-area', 'service-areas', 'areas-served', 'area'];
            $cityRegex = '/\b([A-Z][a-z]+(?:\s[A-Z][a-z]+)*),\s*([A-Z]{2})\b/'; // City, State pattern
            
            if (!empty($matches[2])) {
                foreach ($matches[2] as $key => $href) {
                    $linkText = strip_tags($matches[3][$key]);
                    
                    // Make sure it's an internal link
                    if (strpos($href, 'http') !== 0) {
                        // Convert relative URL to absolute
                        if (strpos($href, '/') === 0) {
                            $href = $base . $href;
                        } else {
                            $href = $base . '/' . $href;
                        }
                    } else if (strpos($href, $base) !== 0) {
                        // Skip external links
                        continue;
                    }
                    
                    // Check if it's a likely location page
                    $isLocationPage = false;
                    
                    // Check URL path for location keywords
                    foreach ($locationKeywords as $keyword) {
                        if (strpos(strtolower($href), $keyword) !== false) {
                            $isLocationPage = true;
                            break;
                        }
                    }
                    
                    // Check link text for city/state pattern
                    if (!$isLocationPage && preg_match($cityRegex, $linkText)) {
                        $isLocationPage = true;
                    }
                    
                    if ($isLocationPage) {
                        // Try to get the page content to verify it's actually a location page
                        $pageHtml = @file_get_contents($href);
                        
                        if ($pageHtml) {
                            // Extract title
                            preg_match('/<title>(.*?)<\/title>/i', $pageHtml, $titleMatches);
                            $title = $titleMatches[1] ?? $linkText;
                            
                            // Check for common location page signals
                            $hasAddress = preg_match('/\d+\s+[A-Za-z\s,\.]+\s+[A-Z]{2}\s+\d{5}/i', $pageHtml);
                            $hasMap = strpos($pageHtml, 'maps.google.com') !== false || strpos($pageHtml, 'google.com/maps') !== false;
                            $hasLocalSchema = strpos($pageHtml, '"@type":"LocalBusiness"') !== false || strpos($pageHtml, '"@type": "LocalBusiness"') !== false;
                            
                            // Only add if it has at least one location signal
                            if ($hasAddress || $hasMap || $hasLocalSchema) {
                                $locationPages[] = [
                                    'url' => $href,
                                    'title' => $title,
                                    'has_unique_content' => true, // We'd need more complex analysis to determine this
                                    'has_local_testimonials' => strpos($pageHtml, 'testimonial') !== false || strpos($pageHtml, 'review') !== false,
                                    'has_local_schema' => $hasLocalSchema
                                ];
                            }
                        }
                    }
                }
            }
            
            return [
                'exists' => count($locationPages) > 0,
                'pages' => $locationPages
            ];
        } catch (Exception $e) {
            error_log("Error checking location pages: " . $e->getMessage());
            
            return [
                'exists' => false,
                'error' => 'Failed to check for location pages: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check for NAP (Name, Address, Phone) consistency
     * @param string $domain Website domain
     * @param array $gbpData Google Business Profile data
     * @return array NAP consistency data
     */
    private static function checkNAPConsistency($domain, $gbpData) {
        if (!$gbpData['exists']) {
            return [
                'consistent' => false,
                'inconsistencies' => [
                    [
                        'source' => 'Google Business Profile',
                        'field' => 'profile',
                        'expected' => 'Google Business Profile',
                        'found' => 'No profile found'
                    ]
                ]
            ];
        }
        
        // Store the expected NAP information
        $expectedName = $gbpData['name'];
        $expectedAddress = $gbpData['address'];
        $expectedPhone = $gbpData['phone'];
        
        // Initialize results
        $inconsistencies = [];
        
        // Simulate checking directories for NOW
        // In a real implementation, you'd connect to APIs for each directory
        $directories = [
            'Yelp' => [
                'name' => $expectedName,
                'address' => $expectedAddress,
                'phone' => $expectedPhone
            ],
            'Yellow Pages' => [
                'name' => $expectedName,
                'address' => $expectedAddress,
                'phone' => preg_replace('/[^0-9]/', '', $expectedPhone) // Format difference
            ],
            'BBB' => [
                'name' => $expectedName,
                'address' => str_replace('Suite', 'Ste', $expectedAddress), // Format difference
                'phone' => $expectedPhone
            ]
        ];
        
        // Check for inconsistencies
 // Check for inconsistencies
        foreach ($directories as $directory => $data) {
            // Compare name
            if ($data['name'] !== $expectedName) {
                $inconsistencies[] = [
                    'source' => $directory,
                    'field' => 'name',
                    'expected' => $expectedName,
                    'found' => $data['name']
                ];
            }
            
            // Compare address (ignoring minor formatting differences)
            $normalizedExpectedAddress = self::normalizeAddress($expectedAddress);
            $normalizedFoundAddress = self::normalizeAddress($data['address']);
            
            if ($normalizedFoundAddress !== $normalizedExpectedAddress) {
                $inconsistencies[] = [
                    'source' => $directory,
                    'field' => 'address',
                    'expected' => $expectedAddress,
                    'found' => $data['address']
                ];
            }
            
            // Compare phone (ignoring formatting differences)
            $normalizedExpectedPhone = self::normalizePhone($expectedPhone);
            $normalizedFoundPhone = self::normalizePhone($data['phone']);
            
            if ($normalizedFoundPhone !== $normalizedExpectedPhone) {
                $inconsistencies[] = [
                    'source' => $directory,
                    'field' => 'phone',
                    'expected' => $expectedPhone,
                    'found' => $data['phone']
                ];
            }
        }
        
        return [
            'consistent' => count($inconsistencies) === 0,
            'inconsistencies' => $inconsistencies
        ];
    }
    
    /**
     * Normalize address string for comparison
     * @param string $address Address string
     * @return string Normalized address
     */
    private static function normalizeAddress($address) {
        // Remove all non-alphanumeric characters and convert to lowercase
        $normalized = preg_replace('/[^a-z0-9]/i', '', strtolower($address));
        return $normalized;
    }
    
    /**
     * Normalize phone string for comparison
     * @param string $phone Phone string
     * @return string Normalized phone
     */
    private static function normalizePhone($phone) {
        // Remove all non-numeric characters
        $normalized = preg_replace('/[^0-9]/', '', $phone);
        return $normalized;
    }
    
    /**
     * Check for local citations
     * @param string $domain Website domain
     * @param array $gbpData Google Business Profile data
     * @return array Citation data
     */
    private static function checkLocalCitations($domain, $gbpData) {
        // In a real implementation, this would query APIs of directory services
        // For now, simulate with realistic data
        
        // List of important local directories to check
        $topDirectories = [
            'Google', 'Yelp', 'Facebook', 'Yellow Pages', 'BBB', 
            'TripAdvisor', 'Bing Places', 'Apple Maps', 'Foursquare', 
            'MapQuest', 'Superpages', 'Manta', 'Angie\'s List', 
            'Merchant Circle', 'Chamber of Commerce'
        ];
        
        // Simulate which directories the business is listed in
        $citationCount = mt_rand(5, count($topDirectories));
        $foundDirectories = array_slice($topDirectories, 0, $citationCount);
        
        // Missing directories
        $missingDirectories = array_diff($topDirectories, $foundDirectories);
        
        // Generate citation data
        $citations = [];
        foreach ($foundDirectories as $directory) {
            // Generate a realistic-looking URL
            $directoryDomain = strtolower(str_replace(['\'', ' '], '', $directory));
            if ($directory == 'BBB') {
                $url = "https://www.bbb.org/us/search?find=$domain";
            } else {
                $url = "https://www.$directoryDomain.com/biz/" . str_replace(['.', ' '], ['-', '-'], strtolower($gbpData['name'] ?? $domain));
            }
            
            $citations[] = [
                'source' => $directory,
                'url' => $url,
                'status' => 'active',
                'rating' => mt_rand(35, 50) / 10
            ];
        }
        
        // Calculate citation score based on presence in important directories
        $maxScore = count($topDirectories);
        $score = round(($citationCount / $maxScore) * 100);
        
        return [
            'count' => $citationCount,
            'score' => $score,
            'citations' => $citations,
            'missing_directories' => $missingDirectories
        ];
    }
    
    /**
     * Check for local keywords in content
     * @param string $url Website URL
     * @param array $gbpData Google Business Profile data
     * @return array Local keywords data
     */
    private static function checkLocalKeywords($url, $gbpData) {
        try {
            // Get webpage content
            $html = @file_get_contents($url);
            
            if (!$html) {
                return [
                    'exists' => false,
                    'error' => 'Could not access the webpage'
                ];
            }
            
            // Extract the text content
            $content = strip_tags($html);
            
            // Extract title
            preg_match('/<title>(.*?)<\/title>/i', $html, $titleMatches);
            $title = $titleMatches[1] ?? '';
            
            // Extract meta description
            preg_match('/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/i', $html, $descMatches);
            $metaDescription = $descMatches[1] ?? '';
            
            // Get address components from Google Business Profile if available
            $locationTerms = [];
            if ($gbpData['exists'] && isset($gbpData['address'])) {
                $addressParts = explode(',', $gbpData['address']);
                
                if (count($addressParts) >= 2) {
                    // Extract city
                    $city = trim($addressParts[1]);
                    $locationTerms[] = $city;
                    
                    // Extract state and zip if available
                    if (count($addressParts) >= 3) {
                        $stateZip = explode(' ', trim($addressParts[2]));
                        if (count($stateZip) >= 1) {
                            $locationTerms[] = $stateZip[0]; // State
                        }
                    }
                    
                    // Add variations like "in City", "City area", etc.
                    $locationTerms[] = "in " . $city;
                    $locationTerms[] = $city . " area";
                }
            } else {
                // If we don't have GBP data, try to extract location from the content
                // Look for common patterns like "serving [City]" or "[City], [State]"
                preg_match_all('/\b([A-Z][a-z]+(?:\s[A-Z][a-z]+)*),\s*([A-Z]{2})\b/', $content, $locationMatches);
                
                if (!empty($locationMatches[1])) {
                    foreach ($locationMatches[1] as $key => $city) {
                        $locationTerms[] = $city;
                        $locationTerms[] = "in " . $city;
                        $locationTerms[] = $city . " area";
                        
                        if (!empty($locationMatches[2][$key])) {
                            $state = $locationMatches[2][$key];
                            $locationTerms[] = $state;
                            $locationTerms[] = $city . ", " . $state;
                        }
                    }
                }
            }
            
            // Count occurrences of location terms
            $keywordsFound = [];
            $titleHasLocation = false;
            $descHasLocation = false;
            $totalOccurrences = 0;
            
            foreach ($locationTerms as $term) {
                // Count in content
                $count = substr_count(strtolower($content), strtolower($term));
                
                if ($count > 0) {
                    $keywordsFound[] = [
                        'keyword' => $term,
                        'count' => $count
                    ];
                    $totalOccurrences += $count;
                }
                
                // Check title
                if (stripos($title, $term) !== false) {
                    $titleHasLocation = true;
                }
                
                // Check meta description
                if (stripos($metaDescription, $term) !== false) {
                    $descHasLocation = true;
                }
            }
            
            // Calculate location term density
            $wordCount = str_word_count($content);
            $locationTermDensity = $wordCount > 0 ? ($totalOccurrences / $wordCount) * 100 : 0;
            
            // Count pages with location terms (would require crawling the site, using 1 for main page)
            $pagesWithLocalTerms = $totalOccurrences > 0 ? 1 : 0;
            
            // Look for business type + location combinations (e.g., "plumber in City")
            if ($gbpData['exists'] && isset($gbpData['categories'])) {
                foreach ($gbpData['categories'] as $category) {
                    $businessType = strtolower($category);
                    
                    foreach ($locationTerms as $term) {
                        $combinedKeyword = $businessType . " " . strtolower($term);
                        $combinedKeyword2 = $businessType . " in " . strtolower($term);
                        
                        $count1 = substr_count(strtolower($content), $combinedKeyword);
                        $count2 = substr_count(strtolower($content), $combinedKeyword2);
                        
                        if ($count1 > 0) {
                            $keywordsFound[] = [
                                'keyword' => $combinedKeyword,
                                'count' => $count1
                            ];
                        }
                        
                        if ($count2 > 0) {
                            $keywordsFound[] = [
                                'keyword' => $combinedKeyword2,
                                'count' => $count2
                            ];
                        }
                    }
                }
            }
            
            return [
                'exists' => count($keywordsFound) > 0,
                'keywords_found' => $keywordsFound,
                'title_tags_with_location' => $titleHasLocation ? 1 : 0,
                'meta_description_has_location' => $descHasLocation,
                'pages_with_local_terms' => $pagesWithLocalTerms,
                'location_term_density' => round($locationTermDensity, 2)
            ];
        } catch (Exception $e) {
            error_log("Error checking local keywords: " . $e->getMessage());
            
            return [
                'exists' => false,
                'error' => 'Failed to check for local keywords: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check for embedded Google Maps
     * @param string $url Website URL
     * @return bool Whether the site has an embedded map
     */
    private static function checkEmbeddedMap($url) {
        try {
            // Get webpage content
            $html = @file_get_contents($url);
            
            if (!$html) {
                return false;
            }
            
            // Look for Google Maps embed code
            $hasEmbed = strpos($html, 'maps.google.com/maps') !== false || 
                       strpos($html, 'google.com/maps/embed') !== false || 
                       strpos($html, 'maps/place') !== false;
            
            if ($hasEmbed) {
                return true;
            }
            
            // If not found on the homepage, try checking the contact page
            $contactUrl = null;
            $parsedUrl = parse_url($url);
            $base = (!empty($parsedUrl['scheme']) && !empty($parsedUrl['host']))
                ? $parsedUrl['scheme'] . '://' . $parsedUrl['host']
                : '';

            // Look for contact page link
            $matches = [];
            preg_match_all('/<a[^>]+href=(["\'])([^"\']+)\1[^>]*>(.*?)<\/a>/si', $html, $matches);

            if (!empty($matches[2]) && !empty($matches[3])) {
                foreach ($matches[3] as $key => $linkText) {
                    if (stripos($linkText, 'contact') !== false || stripos($linkText, 'location') !== false) {
                        $href = $matches[2][$key];

                        // Make sure it's an internal link
                        if (strpos($href, 'http') === 0) {
                            // Absolute: only accept it if it points at our host
                            if (!empty($parsedUrl['host']) && strpos($href, $parsedUrl['host']) !== false) {
                                $contactUrl = $href;
                            }
                        } else {
                            // Relative: resolve against base
                            if ($base === '') break;
                            if (strpos($href, '/') === 0) {
                                $contactUrl = $base . $href;
                            } else {
                                $contactUrl = $base . '/' . $href;
                            }
                        }

                        break;
                    }
                }
            }

            // Check contact page if found
            if ($contactUrl) {
                $contactHtml = @file_get_contents($contactUrl);
                
                if ($contactHtml) {
                    return strpos($contactHtml, 'maps.google.com/maps') !== false || 
                           strpos($contactHtml, 'google.com/maps/embed') !== false || 
                           strpos($contactHtml, 'maps/place') !== false;
                }
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error checking embedded map: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calculate overall local SEO score
     * @param array $results Analysis results
     * @return int Overall score (0-100)
     */
    private static function calculateScore($results) {
        // Define weights for each factor
        $weights = [
            'has_google_business_profile' => 20,
            'has_local_business_schema' => 15,
            'has_location_pages' => 15,
            'has_local_keywords' => 15,
            'has_city_in_title_tags' => 10,
            'has_embedded_map' => 5,
            'has_nap_consistency' => 20
        ];
        
        // Calculate weighted score
        $score = 0;
        $totalWeight = 0;
        
        foreach ($weights as $factor => $weight) {
            if (isset($results[$factor])) {
                if ($results[$factor] === true) {
                    $score += $weight;
                }
            }
            $totalWeight += $weight;
        }
        
        // Add citation score (weighted at 10% of total)
        if (isset($results['citation_score'])) {
            $score += ($results['citation_score'] / 100) * 10;
            $totalWeight += 10;
        }
        
        // Convert to 0-100 scale
        $finalScore = ($score / $totalWeight) * 100;
        return round($finalScore);
    }
}