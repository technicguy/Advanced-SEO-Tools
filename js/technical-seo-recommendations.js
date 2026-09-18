/**
 * technical-seo-recommendations.js - Enhances Technical SEO recommendations with AI-generated content
 * File path: js/technical-seo-recommendations.js
 */

const TechnicalSEORecommendations = {
    /**
     * Initialize the enhancements by attaching event listeners
     */
    initialize: function() {
        // Add event listener for when Technical SEO results are displayed
        $(document).on('analysisCompleted', () => {
            setTimeout(() => {
                this.enhanceTechnicalRecommendations();
            }, 500);
        });
        
        // Also check if Technical SEO content is already displayed
        setTimeout(() => {
            if ($('#technicalContent').children().length > 0) {
                this.enhanceTechnicalRecommendations();
            }
        }, 1000);
    },
    
    /**
     * Enhance all Technical SEO recommendations
     */
    enhanceTechnicalRecommendations: function() {
        // Get website URL
        const websiteUrl = $('#websiteUrl').val() || 'https://example.com';
        
        // Find all recommendation items
        const recommendations = $('#technicalContent .recommendation-item');
        
        recommendations.each((index, recommendation) => {
            const recommendationText = $(recommendation).text().toLowerCase();
            const recommendationTitle = $(recommendation).find('h5').text().toLowerCase();
            
            // Find or create a container for the enhanced content
            let codeContainer = $(recommendation).find('.code-example');
            
            // Process based on recommendation type - first check titles for better accuracy
            if (recommendationTitle.includes('xml sitemap') || recommendationTitle.includes('create sitemap')) {
                this.enhanceSitemapRecommendation(recommendation, websiteUrl);
            } 
            else if (recommendationTitle.includes('robots.txt') || recommendationTitle.includes('create robots')) {
                this.enhanceRobotsTxtRecommendation(recommendation, websiteUrl);
            } 
            else if (recommendationTitle.includes('https') || recommendationTitle.includes('ssl') || recommendationTitle.includes('enable https')) {
                this.enhanceHttpsRecommendation(recommendation, websiteUrl);
            } 
            else if (recommendationTitle.includes('schema markup') || recommendationTitle.includes('structured data') || recommendationTitle.includes('implement schema')) {
                this.enhanceSchemaMarkupRecommendation(recommendation, websiteUrl);
            }
            // If title checks don't match, try full text as fallback
            else if (recommendationText.includes('xml sitemap') || recommendationText.includes('create sitemap')) {
                this.enhanceSitemapRecommendation(recommendation, websiteUrl);
            }
            else if (recommendationText.includes('robots.txt') || recommendationText.includes('create robots')) {
                this.enhanceRobotsTxtRecommendation(recommendation, websiteUrl);
            }
            else if (recommendationText.includes('https') || recommendationText.includes('ssl')) {
                this.enhanceHttpsRecommendation(recommendation, websiteUrl);
            }
            else if (recommendationText.includes('schema markup') || recommendationText.includes('structured data')) {
                this.enhanceSchemaMarkupRecommendation(recommendation, websiteUrl);
            }
            
            // Add copy functionality to all code examples if not already enhanced
            this.addCopyFunctionality(recommendation);
        });
    },
    
    /**
     * Add copy functionality to code blocks
     * @param {Element} recommendation The recommendation DOM element
     */
    addCopyFunctionality: function(recommendation) {
        const codeBlocks = $(recommendation).find('pre code').not('.enhanced');
        
        codeBlocks.each((index, block) => {
            const parent = $(block).parent();
            parent.css('position', 'relative');
            
            // Add copy button
            const copyBtn = $('<button class="copy-code-btn btn btn-sm btn-outline-primary position-absolute" style="top: 5px; right: 5px;"><i class="fas fa-copy"></i></button>');
            parent.append(copyBtn);
            
            // Mark as enhanced
            $(block).addClass('enhanced');
            
            // Add click event
            copyBtn.on('click', () => {
                const content = $(block).text();
                this.copyToClipboard(content)
                    .then(() => {
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
     * Enhance XML sitemap recommendation with a real sitemap for the website
     * @param {Element} recommendation The recommendation DOM element
     * @param {string} websiteUrl The website URL
     */
    enhanceSitemapRecommendation: function(recommendation, websiteUrl) {
        // Find existing code example or create container for new one
        let codeContainer = $(recommendation).find('.code-example, pre, code').parent();
        if (codeContainer.length === 0) {
            codeContainer = $('<div class="mt-3 position-relative"></div>');
            $(recommendation).append(codeContainer);
        } else {
            // Store original content
            codeContainer.data('original-content', codeContainer.html());
        }
        
        // Generate proper XML sitemap based on the website URL
        const currentDate = new Date().toISOString().split('T')[0];
        const normalizedUrl = websiteUrl.replace(/\/$/, '');
        
        // Create common pages based on the website URL
        const pages = [
            { url: normalizedUrl + '/', priority: '1.0', changefreq: 'weekly' },
            { url: normalizedUrl + '/about', priority: '0.8', changefreq: 'monthly' },
            { url: normalizedUrl + '/services', priority: '0.8', changefreq: 'monthly' },
            { url: normalizedUrl + '/products', priority: '0.8', changefreq: 'monthly' },
            { url: normalizedUrl + '/contact', priority: '0.7', changefreq: 'monthly' },
            { url: normalizedUrl + '/blog', priority: '0.9', changefreq: 'weekly' }
        ];
        
        // Create sitemap XML
        let sitemap = '<?xml version="1.0" encoding="UTF-8"?>\n';
        sitemap += '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">\n';
        
        // Add each page
        pages.forEach(page => {
            sitemap += '  <url>\n';
            sitemap += '    <loc>' + page.url + '</loc>\n';
            sitemap += '    <lastmod>' + currentDate + '</lastmod>\n';
            sitemap += '    <changefreq>' + page.changefreq + '</changefreq>\n';
            sitemap += '    <priority>' + page.priority + '</priority>\n';
            sitemap += '  </url>\n';
        });
        
        sitemap += '</urlset>';
        
        // Create enhanced UI
        const html = `
            <div class="card border-0 mt-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">XML Sitemap for ${websiteUrl}</h6>
                    <div class="btn-group">
                        <button class="copy-btn btn btn-sm btn-outline-primary">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                        <button class="download-btn btn btn-sm btn-outline-success">
                            <i class="fas fa-download"></i> Download
                        </button>
                    </div>
                </div>
                <div class="card-body py-2">
                    <div class="code-block p-2 bg-light border rounded">
                        <code>${this.escapeHtml(sitemap)}</code>
                    </div>
                    <div class="mt-2 small text-muted">
                        <p>Save this file as <code>sitemap.xml</code> in your website's root directory and submit it to Google Search Console.</p>
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
        
        codeContainer.find('.download-btn').on('click', () => {
            // Create and download file
            const blob = new Blob([sitemap], { type: 'application/xml' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'sitemap.xml';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });
    },
    
    /**
     * Enhance robots.txt recommendation with a real robots.txt for the website
     * @param {Element} recommendation The recommendation DOM element
     * @param {string} websiteUrl The website URL
     */
    enhanceRobotsTxtRecommendation: function(recommendation, websiteUrl) {
        // Find existing code example or create container for new one
        let codeContainer = $(recommendation).find('.code-example, pre, code').parent();
        if (codeContainer.length === 0) {
            codeContainer = $('<div class="mt-3 position-relative"></div>');
            $(recommendation).append(codeContainer);
        } else {
            // Store original content
            codeContainer.data('original-content', codeContainer.html());
        }
        
        // Extract domain from URL
        const domain = new URL(websiteUrl).hostname;
        
        // Generate proper robots.txt based on the website URL
        const robotsTxt = `# robots.txt for ${domain}
# Generated on ${new Date().toISOString().split('T')[0]}

User-agent: *
Allow: /

# Disallow admin and private areas
Disallow: /admin/
Disallow: /wp-admin/
Disallow: /private/
Disallow: /includes/
Disallow: /cgi-bin/
Disallow: /tmp/

# Sitemap location
Sitemap: ${websiteUrl.replace(/\/$/, '')}/sitemap.xml`;
        
        // Create enhanced UI
        const html = `
            <div class="card border-0 mt-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">robots.txt for ${domain}</h6>
                    <div class="btn-group">
                        <button class="copy-btn btn btn-sm btn-outline-primary">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                        <button class="download-btn btn btn-sm btn-outline-success">
                            <i class="fas fa-download"></i> Download
                        </button>
                    </div>
                </div>
                <div class="card-body py-2">
                    <div class="code-block p-2 bg-light border rounded">
                        <code>${this.escapeHtml(robotsTxt)}</code>
                    </div>
                    <div class="mt-2 small text-muted">
                        <p>Save this file as <code>robots.txt</code> in your website's root directory to control search engine crawling.</p>
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
        
        codeContainer.find('.download-btn').on('click', () => {
            // Create and download file
            const blob = new Blob([robotsTxt], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'robots.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });
    },
    
    /**
     * Enhance HTTPS recommendation with .htaccess redirect code
     * @param {Element} recommendation The recommendation DOM element
     * @param {string} websiteUrl The website URL
     */
    enhanceHttpsRecommendation: function(recommendation, websiteUrl) {
        // Find existing code example or create container for new one
        let codeContainer = $(recommendation).find('.code-example, pre, code').parent();
        if (codeContainer.length === 0) {
            codeContainer = $('<div class="mt-3 position-relative"></div>');
            $(recommendation).append(codeContainer);
        } else {
            // Store original content
            codeContainer.data('original-content', codeContainer.html());
        }
        
        // Generate .htaccess code for HTTPS redirect
        const htaccessCode = `# Redirect from HTTP to HTTPS
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule (.*) https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
</IfModule>`;
        
        // Create enhanced UI
        const html = `
            <div class="card border-0 mt-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">.htaccess Code for HTTPS Redirect</h6>
                    <button class="copy-btn btn btn-sm btn-outline-primary">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
                <div class="card-body py-2">
                    <div class="code-block p-2 bg-light border rounded">
                        <code>${this.escapeHtml(htaccessCode)}</code>
                    </div>
                    <div class="mt-2 small text-muted">
                        <p>Add this code to your <code>.htaccess</code> file in your website's root directory to redirect HTTP traffic to HTTPS.</p>
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
    },
    
    /**
     * Enhance Schema Markup recommendation with JSON-LD code
     * @param {Element} recommendation The recommendation DOM element
     * @param {string} websiteUrl The website URL
     */
    enhanceSchemaMarkupRecommendation: function(recommendation, websiteUrl) {
        // Find existing code example or create container for new one
        let codeContainer = $(recommendation).find('.code-example, pre, code').parent();
        if (codeContainer.length === 0) {
            codeContainer = $('<div class="mt-3 position-relative"></div>');
            $(recommendation).append(codeContainer);
        } else {
            // Store original content
            codeContainer.data('original-content', codeContainer.html());
        }
        
        // Extract domain name for company name
        const domain = new URL(websiteUrl).hostname;
        const companyName = this.extractCompanyName(domain);
        
        // Generate Schema Markup JSON-LD
        const schemaMarkup = `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "${companyName}",
  "url": "${websiteUrl}",
  "logo": "${websiteUrl.replace(/\/$/, '')}/images/logo.png",
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "+1-555-555-5555",
    "contactType": "customer service"
  },
  "sameAs": [
    "https://www.facebook.com/${companyName.toLowerCase().replace(/\s+/g, '')}",
    "https://www.twitter.com/${companyName.toLowerCase().replace(/\s+/g, '')}",
    "https://www.linkedin.com/company/${companyName.toLowerCase().replace(/\s+/g, '')}"
  ]
}
</script>`;
        
        // Create enhanced UI
        const html = `
            <div class="card border-0 mt-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Schema Markup for ${companyName}</h6>
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
                    <div class="code-block p-2 bg-light border rounded">
                        <code>${this.escapeHtml(schemaMarkup)}</code>
                    </div>
                    <div class="mt-2 small text-muted">
                        <p>Add this script to the <code>&lt;head&gt;</code> section of your HTML. Replace placeholder values with your actual information.</p>
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
  "name": "${companyName}",
  "url": "${websiteUrl}",
  "image": "${websiteUrl.replace(/\/$/, '')}/images/storefront.jpg",
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
  "name": "${companyName}",
  "url": "${websiteUrl}",
  "potentialAction": {
    "@type": "SearchAction",
    "target": "${websiteUrl.replace(/\/$/, '')}/search?q={search_term_string}",
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
     * Extract company name from domain
     * @param {string} domain The domain name
     * @returns {string} Company name
     */
    extractCompanyName: function(domain) {
        try {
            // Remove www. prefix and TLD
            domain = domain.replace(/^www\./, '');
            const domainParts = domain.split('.');
            if (domainParts.length > 1) {
                domainParts.pop(); // Remove TLD
            }
            
            const name = domainParts.join('.');
            
            // Replace hyphens and underscores with spaces
            const formattedName = name.replace(/[-_]/g, ' ');
            
            // Convert to title case
            return formattedName.replace(/\b\w/g, l => l.toUpperCase());
        } catch (error) {
            console.error('Error extracting company name:', error);
            return 'Your Company';
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

// Initialize Technical SEO Recommendations when document is ready
$(document).ready(function() {
    TechnicalSEORecommendations.initialize();
});
