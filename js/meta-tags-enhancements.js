/**
 * meta-tags-enhancements.js - Enhances Meta Tags analysis with AI-generated recommendations
 * File path: js/meta-tags-enhancements.js
 */

const MetaTagsEnhancements = {
    /**
     * Initialize the enhancements by adding copy functionality to existing code blocks
     * and improving examples with AI-generated suggestions
     */
    initialize: function() {
        // Add event listener to apply enhancements after Meta Tags analysis is displayed
        $(document).on('metaTagsDisplayed', () => {
            this.enhanceExistingCodeBlocks();
            this.enhanceMetaTagsRecommendations();
        });
        
        // Also check if the meta tags tab is already populated on page load
        setTimeout(() => {
            if ($('#metaTagsContent').children().length > 0) {
                this.enhanceExistingCodeBlocks();
                this.enhanceMetaTagsRecommendations();
            }
        }, 500);
    },
    
    /**
     * Add copy functionality to all existing code blocks
     */
    enhanceExistingCodeBlocks: function() {
        const codeBlocks = $('#metaTagsContent pre code, #metaTagsContent .code-example');
        
        codeBlocks.each((index, block) => {
            // Skip if already enhanced
            if ($(block).parent().find('.copy-code-btn').length > 0) {
                return;
            }
            
            // Add copy button
            const copyBtn = $('<button class="copy-code-btn btn btn-sm btn-outline-primary position-absolute" style="top: 5px; right: 5px;"><i class="fas fa-copy"></i></button>');
            
            // Make sure the parent has position relative
            const parent = $(block).parent();
            if (parent.css('position') !== 'relative') {
                parent.css('position', 'relative');
            }
            
            // Add button to parent
            parent.append(copyBtn);
            
            // Add click event to copy code
            copyBtn.on('click', () => {
                const content = $(block).text();
                this.copyToClipboard(content)
                    .then(() => {
                        // Show success feedback
                        const originalHtml = copyBtn.html();
                        copyBtn.html('<i class="fas fa-check"></i>');
                        setTimeout(() => {
                            copyBtn.html(originalHtml);
                        }, 2000);
                    })
                    .catch(err => {
                        console.error('Failed to copy:', err);
                    });
            });
        });
    },
    
    /**
     * Enhance Meta Tags recommendations with AI-generated suggestions
     */
    enhanceMetaTagsRecommendations: function() {
        // Find all recommendation items
        const recommendations = $('#metaTagsContent .recommendation-item, #metaTagsContent .meta-tag-recommendation');
        
        recommendations.each((index, recommendation) => {
            const recommendationText = $(recommendation).text().toLowerCase();
            
            // Process based on recommendation type
            if (recommendationText.includes('title tag')) {
                this.enhanceTitleTagRecommendation(recommendation);
            } else if (recommendationText.includes('meta description')) {
                this.enhanceMetaDescriptionRecommendation(recommendation);
            } else if (recommendationText.includes('canonical')) {
                this.enhanceCanonicalTagRecommendation(recommendation);
            } else if (recommendationText.includes('open graph') || recommendationText.includes('og:')) {
                this.enhanceOpenGraphRecommendation(recommendation);
            } else if (recommendationText.includes('schema') || recommendationText.includes('structured data')) {
                this.enhanceSchemaMarkupRecommendation(recommendation);
            }
        });
    },
    
    /**
     * Enhance title tag recommendation with AI-generated title
     * @param {Element} recommendation The recommendation DOM element
     */
    enhanceTitleTagRecommendation: function(recommendation) {
        // Get website URL or name
        const websiteUrl = $('#websiteUrl').val() || 'example.com';
        const websiteName = this.extractWebsiteName(websiteUrl);
        const pageType = 'Home'; // Default to Home, could be more dynamic
        
        // Find existing code example or create container for new one
        let codeContainer = $(recommendation).find('.code-example, pre, code').parent();
        if (codeContainer.length === 0) {
            codeContainer = $('<div class="mt-3 position-relative"></div>');
            $(recommendation).append(codeContainer);
        } else {
            // Store original content
            codeContainer.data('original-content', codeContainer.html());
            codeContainer.empty();
        }
        
        // Generate optimized title tag based on website name
        const optimizedTitle = this.generateOptimizedTitleTag(websiteName, pageType);
        
        // Create the HTML structure with enhanced code example
        const html = `
            <div class="card border-0 bg-light">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">AI-Generated Title Tag</h6>
                    <div class="btn-group">
                        <button class="copy-btn btn btn-sm btn-outline-primary">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                        <button class="alternate-btn btn btn-sm btn-outline-secondary">
                            <i class="fas fa-random"></i> Generate Alternative
                        </button>
                    </div>
                </div>
                <div class="card-body py-2">
                    <div class="code-block p-2 bg-white border rounded">
                        <code>${this.escapeHtml(optimizedTitle)}</code>
                    </div>
                    <div class="mt-2 small text-muted">
                        <strong>Length:</strong> ${optimizedTitle.length} characters (Recommended: 30-60)
                    </div>
                </div>
            </div>
        `;
        
        // Add the HTML to the container
        codeContainer.html(html);
        
        // Setup event handlers
        codeContainer.find('.copy-btn').on('click', () => {
            const content = codeContainer.find('code').text();
            this.copyToClipboard(content)
                .then(() => {
                    // Show success feedback
                    const copyBtn = codeContainer.find('.copy-btn');
                    const originalHtml = copyBtn.html();
                    copyBtn.html('<i class="fas fa-check"></i> Copied!');
                    setTimeout(() => {
                        copyBtn.html(originalHtml);
                    }, 2000);
                })
                .catch(err => {
                    console.error('Failed to copy:', err);
                });
        });
        
        codeContainer.find('.alternate-btn').on('click', () => {
            // Generate an alternative title
            const alternativeTitle = this.generateOptimizedTitleTag(websiteName, pageType, true);
            codeContainer.find('code').text(alternativeTitle);
            codeContainer.find('.text-muted strong').next().text(
                `${alternativeTitle.length} characters (Recommended: 30-60)`
            );
        });
    },
    
    /**
     * Enhance meta description recommendation with AI-generated description
     * @param {Element} recommendation The recommendation DOM element
     */
    enhanceMetaDescriptionRecommendation: function(recommendation) {
        // Get website URL or name
        const websiteUrl = $('#websiteUrl').val() || 'example.com';
        const websiteName = this.extractWebsiteName(websiteUrl);
        const pageType = 'Home'; // Default to Home, could be more dynamic
        
        // Find existing code example or create container for new one
        let codeContainer = $(recommendation).find('.code-example, pre, code').parent();
        if (codeContainer.length ===.0) {
            codeContainer = $('<div class="mt-3 position-relative"></div>');
            $(recommendation).append(codeContainer);
        } else {
            // Store original content
            codeContainer.data('original-content', codeContainer.html());
            codeContainer.empty();
        }
        
        // Generate optimized meta description based on website name
        const optimizedDescription = this.generateOptimizedMetaDescription(websiteName, pageType);
        
        // Create the HTML structure with enhanced code example
        const html = `
            <div class="card border-0 bg-light">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">AI-Generated Meta Description</h6>
                    <div class="btn-group">
                        <button class="copy-btn btn btn-sm btn-outline-primary">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                        <button class="alternate-btn btn btn-sm btn-outline-secondary">
                            <i class="fas fa-random"></i> Generate Alternative
                        </button>
                    </div>
                </div>
                <div class="card-body py-2">
                    <div class="code-block p-2 bg-white border rounded">
                        <code>${this.escapeHtml(optimizedDescription)}</code>
                    </div>
                    <div class="mt-2 small text-muted">
                        <strong>Length:</strong> ${optimizedDescription.length} characters (Recommended: 120-160)
                    </div>
                </div>
            </div>
        `;
        
        // Add the HTML to the container
        codeContainer.html(html);
        
        // Setup event handlers
        codeContainer.find('.copy-btn').on('click', () => {
            const content = codeContainer.find('code').text();
            this.copyToClipboard(content)
                .then(() => {
                    // Show success feedback
                    const copyBtn = codeContainer.find('.copy-btn');
                    const originalHtml = copyBtn.html();
                    copyBtn.html('<i class="fas fa-check"></i> Copied!');
                    setTimeout(() => {
                        copyBtn.html(originalHtml);
                    }, 2000);
                })
                .catch(err => {
                    console.error('Failed to copy:', err);
                });
        });
        
        codeContainer.find('.alternate-btn').on('click', () => {
            // Generate an alternative description
            const alternativeDescription = this.generateOptimizedMetaDescription(websiteName, pageType, true);
            codeContainer.find('code').text(alternativeDescription);
            codeContainer.find('.text-muted strong').next().text(
                `${alternativeDescription.length} characters (Recommended: 120-160)`
            );
        });
    },
    
    /**
     * Enhance canonical tag recommendation with proper URL
     * @param {Element} recommendation The recommendation DOM element
     */
    enhanceCanonicalTagRecommendation: function(recommendation) {
        // Get website URL
        const websiteUrl = $('#websiteUrl').val() || 'https://example.com';
        
        // Find existing code example or create container for new one
        let codeContainer = $(recommendation).find('.code-example, pre, code').parent();
        if (codeContainer.length === 0) {
            codeContainer = $('<div class="mt-3 position-relative"></div>');
            $(recommendation).append(codeContainer);
        } else {
            // Store original content
            codeContainer.data('original-content', codeContainer.html());
            codeContainer.empty();
        }
        
        // Create proper canonical tag
        const canonicalTag = `<link rel="canonical" href="${websiteUrl}" />`;
        
        // Create the HTML structure with enhanced code example
        const html = `
            <div class="card border-0 bg-light">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Canonical Tag for Your Website</h6>
                    <button class="copy-btn btn btn-sm btn-outline-primary">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
                <div class="card-body py-2">
                    <div class="code-block p-2 bg-white border rounded">
                        <code>${this.escapeHtml(canonicalTag)}</code>
                    </div>
                    <div class="mt-2 small text-muted">
                        <span>Add this tag to the <code>&lt;head&gt;</code> section of your HTML.</span>
                    </div>
                </div>
            </div>
        `;
        
        // Add the HTML to the container
        codeContainer.html(html);
        
        // Setup copy event handler
        codeContainer.find('.copy-btn').on('click', () => {
            const content = codeContainer.find('code').text();
            this.copyToClipboard(content)
                .then(() => {
                    // Show success feedback
                    const copyBtn = codeContainer.find('.copy-btn');
                    const originalHtml = copyBtn.html();
                    copyBtn.html('<i class="fas fa-check"></i> Copied!');
                    setTimeout(() => {
                        copyBtn.html(originalHtml);
                    }, 2000);
                })
                .catch(err => {
                    console.error('Failed to copy:', err);
                });
        });
    },
    
    /**
     * Enhance Open Graph recommendation with proper OG tags
     * @param {Element} recommendation The recommendation DOM element
     */
    enhanceOpenGraphRecommendation: function(recommendation) {
        // Get website URL and other data
        const websiteUrl = $('#websiteUrl').val() || 'https://example.com';
        const websiteName = this.extractWebsiteName(websiteUrl);
        
        // Generate title and description for OG tags
        const title = this.generateOptimizedTitleTag(websiteName, 'Home');
        const description = this.generateOptimizedMetaDescription(websiteName, 'Home');
        
        // Find existing code example or create container for new one
        let codeContainer = $(recommendation).find('.code-example, pre, code').parent();
        if (codeContainer.length === 0) {
            codeContainer = $('<div class="mt-3 position-relative"></div>');
            $(recommendation).append(codeContainer);
        } else {
            // Store original content
            codeContainer.data('original-content', codeContainer.html());
            codeContainer.empty();
        }
        
        // Create Open Graph tags
        const ogTags = `<meta property="og:title" content="${title}" />
<meta property="og:description" content="${description}" />
<meta property="og:url" content="${websiteUrl}" />
<meta property="og:type" content="website" />
<meta property="og:image" content="${websiteUrl}/images/og-image.jpg" />`;
        
        // Create the HTML structure with enhanced code example
        const html = `
            <div class="card border-0 bg-light">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Open Graph Tags for Your Website</h6>
                    <button class="copy-btn btn btn-sm btn-outline-primary">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
                <div class="card-body py-2">
                    <div class="code-block p-2 bg-white border rounded">
                        <code>${this.escapeHtml(ogTags)}</code>
                    </div>
                    <div class="mt-2 small text-muted">
                        <span>Add these tags to the <code>&lt;head&gt;</code> section of your HTML. Make sure to create and upload an og-image.jpg file (1200x630 pixels recommended).</span>
                    </div>
                </div>
            </div>
        `;
        
        // Add the HTML to the container
        codeContainer.html(html);
        
        // Setup copy event handler
        codeContainer.find('.copy-btn').on('click', () => {
            const content = codeContainer.find('code').text();
            this.copyToClipboard(content)
                .then(() => {
                    // Show success feedback
                    const copyBtn = codeContainer.find('.copy-btn');
                    const originalHtml = copyBtn.html();
                    copyBtn.html('<i class="fas fa-check"></i> Copied!');
                    setTimeout(() => {
                        copyBtn.html(originalHtml);
                    }, 2000);
                })
                .catch(err => {
                    console.error('Failed to copy:', err);
                });
        });
    },
    
    /**
     * Enhance Schema Markup recommendation with proper schema JSON-LD
     * @param {Element} recommendation The recommendation DOM element
     */
    enhanceSchemaMarkupRecommendation: function(recommendation) {
        // Get website URL and other data
        const websiteUrl = $('#websiteUrl').val() || 'https://example.com';
        const websiteName = this.extractWebsiteName(websiteUrl);
        
        // Find existing code example or create container for new one
        let codeContainer = $(recommendation).find('.code-example, pre, code').parent();
        if (codeContainer.length === 0) {
            codeContainer = $('<div class="mt-3 position-relative"></div>');
            $(recommendation).append(codeContainer);
        } else {
            // Store original content
            codeContainer.data('original-content', codeContainer.html());
            codeContainer.empty();
        }
        
        // Create Schema Markup JSON-LD
        const schemaMarkup = `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "${websiteName}",
  "url": "${websiteUrl}",
  "logo": "${websiteUrl}/images/logo.png",
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "+1-555-555-5555",
    "contactType": "customer service"
  },
  "sameAs": [
    "https://www.facebook.com/${websiteName.toLowerCase().replace(/\s+/g, '')}",
    "https://www.twitter.com/${websiteName.toLowerCase().replace(/\s+/g, '')}",
    "https://www.instagram.com/${websiteName.toLowerCase().replace(/\s+/g, '')}"
  ]
}
</script>`;
        
        // Create the HTML structure with enhanced code example
        const html = `
            <div class="card border-0 bg-light">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Organization Schema Markup</h6>
                    <div class="btn-group">
                        <button class="copy-btn btn btn-sm btn-outline-primary">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                        <button class="schema-type-btn btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            Organization <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item active" href="#" data-schema-type="Organization">Organization</a></li>
                            <li><a class="dropdown-item" href="#" data-schema-type="LocalBusiness">Local Business</a></li>
                            <li><a class="dropdown-item" href="#" data-schema-type="WebSite">Website</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body py-2">
                    <div class="code-block p-2 bg-white border rounded">
                        <code>${this.escapeHtml(schemaMarkup)}</code>
                    </div>
                    <div class="mt-2 small text-muted">
                        <span>Add this script to the <code>&lt;head&gt;</code> section of your HTML. Replace placeholder information with actual data.</span>
                    </div>
                </div>
            </div>
        `;
        
        // Add the HTML to the container
        codeContainer.html(html);
        
        // Setup copy event handler
        codeContainer.find('.copy-btn').on('click', () => {
            const content = codeContainer.find('code').text();
            this.copyToClipboard(content)
                .then(() => {
                    // Show success feedback
                    const copyBtn = codeContainer.find('.copy-btn');
                    const originalHtml = copyBtn.html();
                    copyBtn.html('<i class="fas fa-check"></i> Copied!');
                    setTimeout(() => {
                        copyBtn.html(originalHtml);
                    }, 2000);
                })
                .catch(err => {
                    console.error('Failed to copy:', err);
                });
        });
        
        // Setup schema type change handler
        codeContainer.find('.dropdown-item').on('click', function(e) {
            e.preventDefault();
            
            // Update active item
            codeContainer.find('.dropdown-item').removeClass('active');
            $(this).addClass('active');
            
            // Update button text
            const schemaType = $(this).data('schema-type');
            codeContainer.find('.schema-type-btn').html(schemaType + ' <span class="caret"></span>');
            
            // Generate new schema markup based on type
            let newSchemaMarkup = '';
            switch (schemaType) {
                case 'LocalBusiness':
                    newSchemaMarkup = `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "${websiteName}",
  "url": "${websiteUrl}",
  "image": "${websiteUrl}/images/storefront.jpg",
  "telephone": "+1-555-555-5555",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "123 Main St",
    "addressLocality": "Anytown",
    "addressRegion": "ST",
    "postalCode": "12345",
    "addressCountry": "US"
  },
  "openingHoursSpecification": [
    {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
      "opens": "09:00",
      "closes": "17:00"
    }
  ]
}
</script>`;
                    break;
                case 'WebSite':
                    newSchemaMarkup = `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "${websiteName}",
  "url": "${websiteUrl}",
  "potentialAction": {
    "@type": "SearchAction",
    "target": "${websiteUrl}/search?q={search_term_string}",
    "query-input": "required name=search_term_string"
  }
}
</script>`;
                    break;
                default: // Organization
                    newSchemaMarkup = schemaMarkup;
            }
            
            // Update code
            codeContainer.find('code').text(newSchemaMarkup);
        });
    },
    
    /**
     * Generate an optimized title tag based on website name and page type
     * @param {string} websiteName The website name
     * @param {string} pageType The page type (Home, About, etc.)
     * @param {boolean} alternate Generate an alternative version
     * @returns {string} Optimized title tag
     */
    generateOptimizedTitleTag: function(websiteName, pageType = 'Home', alternate = false) {
        // Collection of title patterns
        const titlePatterns = [
            `${websiteName} | Official Website`,
            `${websiteName} - Professional Services & Solutions`,
            `${websiteName}: Quality Products & Services`,
            `Welcome to ${websiteName} - Top-Rated Services`,
            `${websiteName} - Innovative Solutions for Your Needs`,
            `${websiteName} - Trusted by Thousands of Customers`,
            `${websiteName} | Expert Solutions for Every Challenge`,
            `${websiteName} - Where Quality Meets Excellence`,
            `${websiteName} - Your Partner for Success`,
            `${websiteName} | Leading Provider of Premium Services`
        ];
        
        // For non-home pages
        if (pageType !== 'Home') {
            const pageTypePatterns = [
                `${pageType} | ${websiteName}`,
                `${pageType} - ${websiteName}`,
                `${websiteName} - ${pageType} Services`,
                `${pageType} Services & Solutions | ${websiteName}`,
                `Learn About Our ${pageType} | ${websiteName}`
            ];
            
            const pattern = pageTypePatterns[Math.floor(Math.random() * pageTypePatterns.length)];
            
            // Ensure length is within recommended limits
            if (pattern.length > 60) {
                return pattern.substring(0, 57) + '...';
            }
            return pattern;
        }
        
        // Choose random pattern for home page
        let index = Math.floor(Math.random() * titlePatterns.length);
        
        // If generating an alternative, ensure it's different
        if (alternate) {
            const lastIndex = index;
            while (index === lastIndex) {
                index = Math.floor(Math.random() * titlePatterns.length);
            }
        }
        
        const title = titlePatterns[index];
        
        // Ensure length is within recommended limits
        if (title.length > 60) {
            return title.substring(0, 57) + '...';
        }
        return title;
    },
    
    /**
     * Generate an optimized meta description based on website name and page type
     * @param {string} websiteName The website name
     * @param {string} pageType The page type (Home, About, etc.)
     * @param {boolean} alternate Generate an alternative version
     * @returns {string} Optimized meta description
     */
    generateOptimizedMetaDescription: function(websiteName, pageType = 'Home', alternate = false) {
        // Collection of description patterns
        const descriptionPatterns = [
            `${websiteName} provides premium services tailored to your needs. Our expert team delivers innovative solutions for businesses of all sizes. Contact us today for a free consultation.`,
            `Discover how ${websiteName} can transform your business with our award-winning services and dedicated support. Join thousands of satisfied customers enjoying our top-rated solutions.`,
            `${websiteName} offers state-of-the-art solutions designed to boost your productivity and growth. Explore our range of services and see why clients choose us consistently.`,
            `Looking for reliable services? ${websiteName} delivers excellence in every project. Our team of experts is ready to help you achieve your goals with customized solutions.`,
            `At ${websiteName}, we're committed to your success. Our comprehensive range of services is designed to meet your specific needs and exceed your expectations.`,
            `${websiteName} combines innovation with expertise to deliver outstanding results. Discover our professional services and find the perfect solution for your requirements.`,
            `Experience the difference with ${websiteName}. We provide tailored solutions and exceptional customer service to ensure your complete satisfaction and business success.`,
            `${websiteName} is your trusted partner for quality services. Our professional team works diligently to deliver optimal solutions that help you stay ahead of the competition.`
        ];
        
        // For non-home pages
        if (pageType !== 'Home') {
            const pageTypeDescriptions = [
                `Learn about our ${pageType} services at ${websiteName}. We offer professional solutions tailored to your specific needs with guaranteed satisfaction and ongoing support.`,
                `Explore ${websiteName}'s ${pageType} offerings designed to help you achieve your goals. Our expert team delivers innovative solutions backed by years of industry experience.`,
                `Discover how our ${pageType} services can benefit your business. ${websiteName} provides comprehensive solutions with a focus on quality, reliability, and customer satisfaction.`
            ];
            
            const description = pageTypeDescriptions[Math.floor(Math.random() * pageTypeDescriptions.length)];
            
            // Ensure length is within recommended limits
            if (description.length > 160) {
                return description.substring(0, 157) + '...';
            }
            return description;
        }
        
        // Choose random pattern for home page
        let index = Math.floor(Math.random() * descriptionPatterns.length);
        
        // If generating an alternative, ensure it's different
        if (alternate) {
            const lastIndex = index;
            while (index === lastIndex) {
                index = Math.floor(Math.random() * descriptionPatterns.length);
            }
        }
        
        const description = descriptionPatterns[index];
        
        // Ensure length is within recommended limits
        if (description.length > 160) {
            return description.substring(0, 157) + '...';
        }
        return description;
    },
    
    /**
     * Extract website name from URL
     * @param {string} url Website URL
     * @returns {string} Website name
     */
    extractWebsiteName: function(url) {
        try {
            // Remove protocol and www
            let domain = url.replace(/^https?:\/\//, '').replace(/^www\./, '');
            
            // Remove everything after first slash
            domain = domain.split('/')[0];
            
            // Remove TLD
            const domainParts = domain.split('.');
            if (domainParts.length > 1) {
                domainParts.pop(); // Remove the TLD
                domain = domainParts.join('.');
            }
            
            // Convert to title case and replace hyphens and underscores with spaces
            domain = domain.replace(/[-_]/g, ' ');
            domain = domain.replace(/\b\w/g, letter => letter.toUpperCase());
            
            return domain;
        } catch (error) {
            // Return a default name if parsing fails
            console.error('Error extracting website name:', error);
            return 'My Website';
        }
    },
    
    /**
     * Copy text to clipboard
     * @param {string} text Text to copy
     * @returns {Promise} Promise that resolves when text is copied
     */
    copyToClipboard: function(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        } else {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                return new Promise((resolve, reject) => {
                    const successful = document.execCommand('copy');
                    if (successful) {
                        resolve();
                    } else {
                        reject(new Error('Unable to copy'));
                    }
                    document.body.removeChild(textArea);
                });
            } catch (err) {
                document.body.removeChild(textArea);
                throw err;
            }
        }
    },
    
    /**
     * Escape HTML special characters
     * @param {string} html HTML string to escape
     * @returns {string} Escaped HTML
     */
    escapeHtml: function(html) {
        const div = document.createElement('div');
        div.innerText = html;
        return div.innerHTML;
    }
};

// Initialize Meta Tags enhancements when document is ready
$(document).ready(function() {
    MetaTagsEnhancements.initialize();
});