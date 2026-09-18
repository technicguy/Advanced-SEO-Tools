<?php
/**
 * schema-generator.php - Class to analyze schema markup implementation
 * File path: includes/schema-generator.php
 */

class SchemaMarkupAnalyzer {
    private $url;
    private $html;
    private $timeout = 30; // Timeout in seconds

    /**
     * Constructor
     * 
     * @param string $url URL to analyze
     */
    public function __construct($url) {
        $this->url = $url;
    }
    
    /**
     * Run schema markup analysis
     * 
     * @return array Analysis results
     */
    public function analyze() {
        // Fetch page content
        $this->html = $this->fetchPage();
        
        if (!$this->html) {
            throw new Exception('Failed to fetch page content');
        }
        
        // Extract schema markups
        $jsonldSchemas = $this->extractJsonLdSchema();
        $microdataSchemas = $this->extractMicrodataSchema();
        $rdfa = $this->hasRDFa();
        
        // Analyze schemas and generate recommendations
        $analysisResults = $this->analyzeSchemas($jsonldSchemas, $microdataSchemas, $rdfa);
        
        // Generate score based on results
        $score = $this->calculateScore($analysisResults);
        
        // Return final results
        return [
            'schemas' => $analysisResults['schemas'],
            'schema_types' => $analysisResults['schema_types'],
            'has_schema' => $analysisResults['has_schema'],
            'has_jsonld' => $analysisResults['has_jsonld'],
            'has_microdata' => $analysisResults['has_microdata'],
            'has_rdfa' => $rdfa,
            'issues' => $analysisResults['issues'],
            'recommendations' => $analysisResults['recommendations'],
            'overall_score' => $score,
            'url' => $this->url
        ];
    }
    
    /**
     * Fetch page content
     * 
     * @return string|bool Page content or false on failure
     */
    private function fetchPage() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
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
     * Extract JSON-LD schema from HTML
     * 
     * @return array Array of JSON-LD schemas
     */
    private function extractJsonLdSchema() {
        $schemas = [];
        
        // Match script tags with JSON-LD
        if (preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/s', $this->html, $matches)) {
            foreach ($matches[1] as $jsonStr) {
                $jsonStr = trim($jsonStr);
                if (empty($jsonStr)) continue;
                
                try {
                    $data = json_decode($jsonStr, true);
                    if ($data) {
                        $schemas[] = $data;
                    }
                } catch (Exception $e) {
                    // Invalid JSON, skip
                }
            }
        }
        
        return $schemas;
    }
    
    /**
     * Extract Microdata schema from HTML
     * 
     * @return array Array of Microdata schemas
     */
    private function extractMicrodataSchema() {
        $schemas = [];
        
        // Create a new DOMDocument
        $doc = new DOMDocument();
        @$doc->loadHTML($this->html);
        
        // Create XPath
        $xpath = new DOMXPath($doc);
        
        // Find elements with itemscope attribute
        $elements = $xpath->query('//*[@itemscope]');
        
        foreach ($elements as $element) {
            // Get the itemtype attribute
            $itemType = $element->getAttribute('itemtype');
            
            // Skip if no itemtype
            if (empty($itemType)) continue;
            
            // Extract schema.org type from URL
            $type = null;
            if (strpos($itemType, 'schema.org/') !== false) {
                $type = substr($itemType, strrpos($itemType, '/') + 1);
            } else {
                continue; // Skip non-schema.org types
            }
            
            // Find all itemprop elements
            $properties = [];
            $itemprops = $xpath->query('.//*[@itemprop]', $element);
            
            foreach ($itemprops as $itemprop) {
                $propName = $itemprop->getAttribute('itemprop');
                $propValue = $this->getElementValue($itemprop);
                
                if ($propName && $propValue) {
                    $properties[$propName] = $propValue;
                }
            }
            
            // Add to schemas
            $schemas[] = [
                '@type' => $type,
                'properties' => $properties
            ];
        }
        
        return $schemas;
    }
    
    /**
     * Check if page has RDFa markup
     * 
     * @return bool True if RDFa markup found
     */
    private function hasRDFa() {
        return strpos($this->html, 'vocab="http://schema.org/"') !== false || 
               strpos($this->html, 'typeof="') !== false ||
               strpos($this->html, 'property="') !== false;
    }
    
    /**
     * Get element value based on tag type
     * 
     * @param DOMElement $element The DOM element
     * @return mixed Element value
     */
    private function getElementValue($element) {
        // Get tag name
        $tagName = strtolower($element->tagName);
        
        // Check for meta tag
        if ($tagName == 'meta') {
            return $element->getAttribute('content');
        }
        
        // Check for link tag
        if ($tagName == 'link') {
            return $element->getAttribute('href');
        }
        
        // Check for img tag
        if ($tagName == 'img') {
            return $element->getAttribute('src');
        }
        
        // Check for time tag
        if ($tagName == 'time') {
            return $element->getAttribute('datetime') ?: $element->textContent;
        }
        
        // Check for a tag
        if ($tagName == 'a') {
            return $element->getAttribute('href');
        }
        
        // Check for input tag
        if ($tagName == 'input') {
            return $element->getAttribute('value');
        }
        
        // Default to text content
        return trim($element->textContent);
    }
    
    /**
     * Analyze schemas and generate recommendations
     * 
     * @param array $jsonldSchemas JSON-LD schemas
     * @param array $microdataSchemas Microdata schemas
     * @param bool $hasRdfa Whether page has RDFa
     * @return array Analysis results
     */
    private function analyzeSchemas($jsonldSchemas, $microdataSchemas, $hasRdfa) {
        $results = [
            'schemas' => [],
            'schema_types' => [],
            'has_schema' => false,
            'has_jsonld' => !empty($jsonldSchemas),
            'has_microdata' => !empty($microdataSchemas),
            'issues' => [],
            'recommendations' => []
        ];
        
        // Process JSON-LD schemas
        if (!empty($jsonldSchemas)) {
            $results['has_schema'] = true;
            
            foreach ($jsonldSchemas as $schema) {
                $schemaInfo = $this->processSchema($schema, 'JSON-LD');
                if ($schemaInfo) {
                    $results['schemas'][] = $schemaInfo;
                    
                    // Add to schema types
                    if (!in_array($schemaInfo['type'], $results['schema_types'])) {
                        $results['schema_types'][] = $schemaInfo['type'];
                    }
                }
            }
        }
        
        // Process Microdata schemas
        if (!empty($microdataSchemas)) {
            $results['has_schema'] = true;
            
            foreach ($microdataSchemas as $schema) {
                $schemaInfo = [
                    'type' => $schema['@type'],
                    'format' => 'Microdata',
                    'properties' => $schema['properties'] ? count($schema['properties']) : 0,
                    'missing_required' => [],
                    'missing_recommended' => [],
                    'validity' => 'valid'
                ];
                
                // Check for required and recommended properties
                $requiredProps = $this->getRequiredProperties($schema['@type']);
                $recommendedProps = $this->getRecommendedProperties($schema['@type']);
                
                foreach ($requiredProps as $prop) {
                    if (!isset($schema['properties'][$prop])) {
                        $schemaInfo['missing_required'][] = $prop;
                        $schemaInfo['validity'] = 'invalid'; // Mark as invalid if missing required props
                    }
                }
                
                foreach ($recommendedProps as $prop) {
                    if (!isset($schema['properties'][$prop])) {
                        $schemaInfo['missing_recommended'][] = $prop;
                    }
                }
                
                $results['schemas'][] = $schemaInfo;
                
                // Add to schema types
                if (!in_array($schemaInfo['type'], $results['schema_types'])) {
                    $results['schema_types'][] = $schemaInfo['type'];
                }
            }
        }
        
        // Identify issues
        if (!$results['has_schema']) {
            $results['issues'][] = [
                'severity' => 'high',
                'description' => 'No schema markup found on the page'
            ];
        } else {
            if (count($results['schema_types']) == 0) {
                $results['issues'][] = [
                    'severity' => 'high',
                    'description' => 'No valid schema types found on the page'
                ];
            }
            
            // Check for mixing formats
            if ($results['has_jsonld'] && $results['has_microdata']) {
                $results['issues'][] = [
                    'severity' => 'low',
                    'description' => 'Multiple schema markup formats (JSON-LD and Microdata) found on the page'
                ];
            }
            
            // Check for missing required properties
            $missingRequiredCount = 0;
            foreach ($results['schemas'] as $schema) {
                $missingRequiredCount += count($schema['missing_required']);
            }
            
            if ($missingRequiredCount > 0) {
                $results['issues'][] = [
                    'severity' => 'medium',
                    'description' => $missingRequiredCount . ' required properties missing from schema markup'
                ];
            }
        }
        
        // Generate recommendations
        if (!$results['has_schema']) {
            $results['recommendations'][] = [
                'title' => 'Add schema markup to your page',
                'description' => 'Schema markup helps search engines understand your content better and can enable rich snippets in search results.',
                'priority' => 'high',
                'implementation' => $this->getSchemaImplementationGuide()
            ];
        } else {
            // If using Microdata, recommend JSON-LD
            if ($results['has_microdata'] && !$results['has_jsonld']) {
                $results['recommendations'][] = [
                    'title' => 'Use JSON-LD instead of Microdata',
                    'description' => 'JSON-LD is the recommended format for schema markup by Google and is easier to implement and maintain.',
                    'priority' => 'medium',
                    'implementation' => $this->getJsonLdExample($results['schema_types'])
                ];
            }
            
            // Check for common schema types that are missing
            $websiteUrl = parse_url($this->url, PHP_URL_HOST);
            $isHomepage = parse_url($this->url, PHP_URL_PATH) == '/' || parse_url($this->url, PHP_URL_PATH) == '';
            
            if ($isHomepage && !in_array('WebSite', $results['schema_types']) && !in_array('Organization', $results['schema_types'])) {
                $results['recommendations'][] = [
                    'title' => 'Add WebSite and Organization schema to your homepage',
                    'description' => 'These schema types help search engines understand your website identity and can enable site links search box.',
                    'priority' => 'medium',
                    'implementation' => $this->getWebsiteSchemaExample($websiteUrl)
                ];
            }
            
            if (!in_array('BreadcrumbList', $results['schema_types'])) {
                $results['recommendations'][] = [
                    'title' => 'Add BreadcrumbList schema',
                    'description' => 'Breadcrumb schema helps users understand the page hierarchy and can appear in search results.',
                    'priority' => 'low',
                    'implementation' => $this->getBreadcrumbExample()
                ];
            }
            
            // Fix missing required properties
            if ($missingRequiredCount > 0) {
                $results['recommendations'][] = [
                    'title' => 'Add missing required properties to your schema markup',
                    'description' => 'Some required properties are missing from your schema markup, which could prevent rich snippets from appearing.',
                    'priority' => 'high',
                    'implementation' => ''
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Process schema data
     * 
     * @param array $schema Schema data
     * @param string $format Schema format (JSON-LD or Microdata)
     * @return array Processed schema info
     */
    private function processSchema($schema, $format) {
        // Extract type
        $type = isset($schema['@type']) ? $schema['@type'] : '';
        
        // Handle array of types
        if (is_array($type)) {
            $type = reset($type); // Use the first type
        }
        
        if (empty($type)) {
            return null;
        }
        
        // Get properties count
        $propCount = count($schema) - 1; // Exclude @type
        
        // Check for nested schemas
        $nestedCount = 0;
        foreach ($schema as $key => $value) {
            if (is_array($value) && isset($value['@type'])) {
                $nestedCount++;
            }
        }
        
        // Check for required and recommended properties
        $requiredProps = $this->getRequiredProperties($type);
        $recommendedProps = $this->getRecommendedProperties($type);
        
        $missingRequired = [];
        $missingRecommended = [];
        
        foreach ($requiredProps as $prop) {
            if (!isset($schema[$prop])) {
                $missingRequired[] = $prop;
            }
        }
        
        foreach ($recommendedProps as $prop) {
            if (!isset($schema[$prop])) {
                $missingRecommended[] = $prop;
            }
        }
        
        // Determine validity
        $validity = 'valid';
        if (!empty($missingRequired)) {
            $validity = 'invalid';
        } else if (!empty($missingRecommended)) {
            $validity = 'warning';
        }
        
        return [
            'type' => $type,
            'format' => $format,
            'properties' => $propCount,
            'nested_schemas' => $nestedCount,
            'missing_required' => $missingRequired,
            'missing_recommended' => $missingRecommended,
            'validity' => $validity
        ];
    }
    
    /**
     * Get required properties for schema type
     * 
     * @param string $type Schema type
     * @return array Required properties
     */
    private function getRequiredProperties($type) {
        // Define required properties for common schema types
        $requiredProps = [
            'Article' => ['headline', 'author', 'datePublished'],
            'BlogPosting' => ['headline', 'author', 'datePublished'],
            'NewsArticle' => ['headline', 'datePublished'],
            'Product' => ['name'],
            'LocalBusiness' => ['name', 'address'],
            'Organization' => ['name'],
            'Person' => ['name'],
            'BreadcrumbList' => ['itemListElement'],
            'Recipe' => ['name', 'recipeIngredient', 'recipeInstructions'],
            'Review' => ['itemReviewed', 'reviewRating'],
            'WebSite' => ['name'],
            'Event' => ['name', 'startDate', 'location'],
            'VideoObject' => ['name', 'description', 'thumbnailUrl', 'uploadDate'],
            'FAQPage' => ['mainEntity'],
            'HowTo' => ['name', 'step'],
            'JobPosting' => ['title', 'datePosted', 'hiringOrganization', 'jobLocation'],
            'Course' => ['name', 'description'],
            'SoftwareApplication' => ['name', 'operatingSystem'],
            'CreativeWork' => ['name']
        ];
        
        // Return required properties for type or empty array if type not found
        return isset($requiredProps[$type]) ? $requiredProps[$type] : [];
    }
    
    /**
     * Get recommended properties for schema type
     * 
     * @param string $type Schema type
     * @return array Recommended properties
     */
    private function getRecommendedProperties($type) {
        // Define recommended properties for common schema types
        $recommendedProps = [
            'Article' => ['image', 'description', 'publisher', 'dateModified', 'mainEntityOfPage'],
            'BlogPosting' => ['image', 'description', 'publisher', 'dateModified', 'mainEntityOfPage'],
            'NewsArticle' => ['image', 'author', 'description', 'publisher', 'dateModified'],
            'Product' => ['offers', 'description', 'image', 'brand', 'aggregateRating', 'review'],
            'LocalBusiness' => ['address', 'telephone', 'priceRange', 'image', 'geo', 'openingHours'],
            'Organization' => ['logo', 'url', 'contactPoint', 'address', 'sameAs'],
            'Person' => ['jobTitle', 'address', 'image', 'telephone', 'email', 'sameAs'],
            'BreadcrumbList' => [],
            'Recipe' => ['image', 'author', 'datePublished', 'description', 'prepTime', 'cookTime', 'totalTime', 'nutrition'],
            'Review' => ['author', 'datePublished', 'reviewBody'],
            'WebSite' => ['url', 'potentialAction'],
            'Event' => ['description', 'image', 'endDate', 'performer', 'offers'],
            'VideoObject' => ['contentUrl', 'embedUrl', 'duration', 'interactionCount'],
            'FAQPage' => [],
            'HowTo' => ['description', 'image', 'totalTime', 'supply', 'tool'],
            'JobPosting' => ['description', 'employmentType', 'baseSalary', 'validThrough'],
            'Course' => ['provider', 'courseCode', 'hasCourseInstance'],
            'SoftwareApplication' => ['applicationCategory', 'downloadUrl', 'offers', 'aggregateRating'],
            'CreativeWork' => ['author', 'datePublished', 'description', 'image']
        ];
        
        // Return recommended properties for type or empty array if type not found
        return isset($recommendedProps[$type]) ? $recommendedProps[$type] : [];
    }
    
    /**
     * Calculate overall score based on analysis results
     * 
     * @param array $results Analysis results
     * @return int Score (0-100)
     */
    private function calculateScore($results) {
        // Initialize score
        $score = 0;
        
        // Score for having schema markup (50 points)
        if ($results['has_schema']) {
            $score += 50;
            
            // Bonus for using JSON-LD (10 points)
            if ($results['has_jsonld']) {
                $score += 10;
            }
            
            // Score for schema types (up to 15 points)
            $score += min(15, count($results['schema_types']) * 5);
            
            // Penalize for issues
            foreach ($results['issues'] as $issue) {
                if ($issue['severity'] == 'high') {
                    $score -= 15;
                } else if ($issue['severity'] == 'medium') {
                    $score -= 8;
                } else {
                    $score -= 3;
                }
            }
            
            // Penalize for missing required properties
            $missingRequiredCount = 0;
            foreach ($results['schemas'] as $schema) {
                $missingRequiredCount += count($schema['missing_required']);
            }
            
            $score -= min(25, $missingRequiredCount * 5); // Maximum penalty 25 points
        }
        
        // Ensure score is between 0 and 100
        return max(0, min(100, $score));
    }
    
    /**
     * Get general schema implementation guide
     * 
     * @return string Implementation guide
     */
    private function getSchemaImplementationGuide() {
        return "
<h5>How to Implement Schema Markup</h5>
<p>Schema markup can be implemented using JSON-LD, which is Google's recommended format. Here's how:</p>

<ol>
    <li>Determine the appropriate schema type for your content (e.g., Article, Product, LocalBusiness)</li>
    <li>Add a script tag in the &lt;head&gt; section of your HTML</li>
    <li>Include the required and recommended properties for your schema type</li>
</ol>

<h5>JSON-LD Example (Article)</h5>
<pre><code>
&lt;script type=\"application/ld+json\"&gt;
{
  \"@context\": \"https://schema.org\",
  \"@type\": \"Article\",
  \"headline\": \"Your Article Title\",
  \"author\": {
    \"@type\": \"Person\",
    \"name\": \"Author Name\"
  },
  \"datePublished\": \"2023-01-01T08:00:00+08:00\",
  \"dateModified\": \"2023-01-15T09:20:00+08:00\",
  \"image\": \"https://example.com/article-image.jpg\",
  \"description\": \"Your article description goes here.\",
  \"publisher\": {
    \"@type\": \"Organization\",
    \"name\": \"Your Organization\",
    \"logo\": {
      \"@type\": \"ImageObject\",
      \"url\": \"https://example.com/logo.png\"
    }
  }
}
&lt;/script&gt;
</code></pre>

<p>You can use the <a href=\"https://validator.schema.org/\" target=\"_blank\">Schema Markup Validator</a> to test your implementation.</p>
";
    }
    
    /**
     * Get JSON-LD example for specific schema types
     * 
     * @param array $types Schema types
     * @return string JSON-LD example
     */
    private function getJsonLdExample($types) {
        // If no types or multiple types, return a general example
        if (empty($types) || count($types) > 1) {
            return $this->getSchemaImplementationGuide();
        }
        
        $type = $types[0];
        
        // Article example
        if ($type == 'Article' || $type == 'BlogPosting' || $type == 'NewsArticle') {
            return "
<h5>JSON-LD Example for {$type}</h5>
<pre><code>
&lt;script type=\"application/ld+json\"&gt;
{
  \"@context\": \"https://schema.org\",
  \"@type\": \"{$type}\",
  \"headline\": \"Your Article Title\",
  \"author\": {
    \"@type\": \"Person\",
    \"name\": \"Author Name\"
  },
  \"datePublished\": \"2023-01-01T08:00:00+08:00\",
  \"dateModified\": \"2023-01-15T09:20:00+08:00\",
  \"image\": \"https://example.com/article-image.jpg\",
  \"description\": \"Your article description goes here.\",
  \"publisher\": {
    \"@type\": \"Organization\",
    \"name\": \"Your Organization\",
    \"logo\": {
      \"@type\": \"ImageObject\",
      \"url\": \"https://example.com/logo.png\"
    }
  }
}
&lt;/script&gt;
</code></pre>
";
        }
        
        // Product example
        if ($type == 'Product') {
            return "
<h5>JSON-LD Example for Product</h5>
<pre><code>
&lt;script type=\"application/ld+json\"&gt;
{
  \"@context\": \"https://schema.org\",
  \"@type\": \"Product\",
  \"name\": \"Product Name\",
  \"image\": \"https://example.com/product-image.jpg\",
  \"description\": \"Product description goes here.\",
  \"brand\": {
    \"@type\": \"Brand\",
    \"name\": \"Brand Name\"
  },
  \"offers\": {
    \"@type\": \"Offer\",
    \"price\": \"19.99\",
    \"priceCurrency\": \"USD\",
    \"availability\": \"https://schema.org/InStock\"
  }
}
&lt;/script&gt;
</code></pre>
";
        }
        
        // LocalBusiness example
        if ($type == 'LocalBusiness') {
            return "
<h5>JSON-LD Example for LocalBusiness</h5>
<pre><code>
&lt;script type=\"application/ld+json\"&gt;
{
  \"@context\": \"https://schema.org\",
  \"@type\": \"LocalBusiness\",
  \"name\": \"Business Name\",
  \"image\": \"https://example.com/business-image.jpg\",
  \"address\": {
    \"@type\": \"PostalAddress\",
    \"streetAddress\": \"123 Main St\",
    \"addressLocality\": \"City\",
    \"addressRegion\": \"State\",
    \"postalCode\": \"12345\",
    \"addressCountry\": \"US\"
  },
  \"telephone\": \"+1-123-456-7890\",
  \"priceRange\": \"$$$\",
  \"openingHours\": \"Mo-Fr 09:00-17:00\"
}
&lt;/script&gt;
</code></pre>
";
        }
        
        // Return general example for other types
        return $this->getSchemaImplementationGuide();
    }
    
    /**
     * Get WebSite schema example
     * 
     * @param string $domain Website domain
     * @return string JSON-LD example
     */
    private function getWebsiteSchemaExample($domain) {
        return "
<h5>JSON-LD Example for WebSite and Organization</h5>
<pre><code>
&lt;script type=\"application/ld+json\"&gt;
{
  \"@context\": \"https://schema.org\",
  \"@type\": \"WebSite\",
  \"name\": \"Your Website Name\",
  \"url\": \"https://{$domain}/\",
  \"potentialAction\": {
    \"@type\": \"SearchAction\",
    \"target\": \"https://{$domain}/search?q={search_term_string}\",
    \"query-input\": \"required name=search_term_string\"
  }
}
&lt;/script&gt;
</code></pre>

<h5>Organization Schema Example</h5>
<pre><code>
&lt;script type=\"application/ld+json\"&gt;
{
  \"@context\": \"https://schema.org\",
  \"@type\": \"Organization\",
  \"name\": \"Your Organization Name\",
  \"url\": \"https://{$domain}/\",
  \"logo\": \"https://{$domain}/logo.png\",
  \"contactPoint\": {
    \"@type\": \"ContactPoint\",
    \"telephone\": \"+1-123-456-7890\",
    \"contactType\": \"customer service\"
  },
  \"sameAs\": [
    \"https://www.facebook.com/your-profile\",
    \"https://www.twitter.com/your-profile\",
    \"https://www.linkedin.com/company/your-profile\"
  ]
}
&lt;/script&gt;
</code></pre>
";
    }
    
    /**
     * Get BreadcrumbList schema example
     * 
     * @return string JSON-LD example
     */
    private function getBreadcrumbExample() {
        return "
<h5>JSON-LD Example for BreadcrumbList</h5>
<pre><code>
&lt;script type=\"application/ld+json\"&gt;
{
  \"@context\": \"https://schema.org\",
  \"@type\": \"BreadcrumbList\",
  \"itemListElement\": [
    {
      \"@type\": \"ListItem\",
      \"position\": 1,
      \"name\": \"Home\",
      \"item\": \"https://example.com/\"
    },
    {
      \"@type\": \"ListItem\",
      \"position\": 2,
      \"name\": \"Category\",
      \"item\": \"https://example.com/category/\"
    },
    {
      \"@type\": \"ListItem\",
      \"position\": 3,
      \"name\": \"Current Page\",
      \"item\": \"https://example.com/category/current-page/\"
    }
  ]
}
&lt;/script&gt;
</code></pre>
";
    }
}
