/**
 * technical-seo.js - Technical SEO analysis functionality
 * File path: js/technical-seo.js
 */

const TechnicalSEO = {
    displayResults: function(data) {
        // Check if data is null/undefined or empty
        if (!data) {
            $('#technicalContent').html(`
                <div class="alert alert-warning mb-4">
                    <strong><i class="fas fa-exclamation-triangle"></i> Warning:</strong> 
                    Technical SEO analysis failed. No data received.
                </div>
            `);
            return;
        }
        
        // Create overall score card
        const overallScore = data.overall_score || this.calculateOverallScore(data);
        
        let content = `
            <div class="row">
                <div class="col-md-7">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Technical SEO Analysis</h5>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-4 text-center">
                                    <div class="score-circle ${this.getScoreClass(overallScore)}">
                                        <div class="score-number">${overallScore}</div>
                                    </div>
                                    <div class="score-label mt-2">Overall Score</div>
                                </div>
                                <div class="col-md-8">
                                    <h6>Key Metrics</h6>
                                    <div class="progress-item">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Crawlability</span>
                                            <span>${data.crawlability_score}/100</span>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar ${this.getProgressBarClass(data.crawlability_score)}" 
                                                role="progressbar" style="width: ${data.crawlability_score}%" 
                                                aria-valuenow="${data.crawlability_score}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    <div class="progress-item mt-2">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Indexability</span>
                                            <span>${data.indexability_score}/100</span>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar ${this.getProgressBarClass(data.indexability_score)}" 
                                                role="progressbar" style="width: ${data.indexability_score}%" 
                                                aria-valuenow="${data.indexability_score}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    <div class="progress-item mt-2">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Site Speed</span>
                                            <span>${data.site_speed_score}/100</span>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar ${this.getProgressBarClass(data.site_speed_score)}" 
                                                role="progressbar" style="width: ${data.site_speed_score}%" 
                                                aria-valuenow="${data.site_speed_score}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    <div class="progress-item mt-2">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Mobile Friendly</span>
                                            <span>${data.mobile_friendly_score}/100</span>
                                        </div>
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar ${this.getProgressBarClass(data.mobile_friendly_score)}" 
                                                role="progressbar" style="width: ${data.mobile_friendly_score}%" 
                                                aria-valuenow="${data.mobile_friendly_score}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Technical Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="detail-item d-flex justify-content-between">
                                <span>HTTPS Enabled</span>
                                <span>${data.has_ssl ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>'}</span>
                            </div>
                            <div class="detail-item d-flex justify-content-between mt-2">
                                <span>Valid Schema Markup</span>
                                <span>${data.schema_markup_valid ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>'}</span>
                            </div>
                            <div class="detail-item d-flex justify-content-between mt-2">
                                <span>Mobile Responsive</span>
                                <span>${data.mobile_friendly_score >= 70 ? '<span class="badge bg-success">Yes</span>' : 
                                        (data.mobile_friendly_score >= 50 ? '<span class="badge bg-warning">Partial</span>' : 
                                        '<span class="badge bg-danger">No</span>')}</span>
                            </div>
                            <div class="detail-item d-flex justify-content-between mt-2">
                                <span>Page Load Time</span>
                                <span>${this.getPageLoadTime(data.site_speed_score)}</span>
                            </div>
                            <div class="detail-item d-flex justify-content-between mt-2">
                                <span>XML Sitemap</span>
                                <span>${data.has_sitemap ? '<span class="badge bg-success">Found</span>' : '<span class="badge bg-danger">Not Found</span>'}</span>
                            </div>
                            <div class="detail-item d-flex justify-content-between mt-2">
                                <span>Robots.txt</span>
                                <span>${data.has_robots_txt ? '<span class="badge bg-success">Found</span>' : '<span class="badge bg-danger">Not Found</span>'}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Check if there are issues to display
        if (data.issues && data.issues.length > 0) {
            content += `
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Technical Issues</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Issue</th>
                                        <th>Severity</th>
                                        <th>Impact</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.issues.map(issue => `
                                        <tr>
                                            <td>${issue.description}</td>
                                            <td><span class="badge ${this.getSeverityClass(issue.severity)}">${issue.severity}</span></td>
                                            <td>${this.getIssueImpact(issue.severity)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Add recommendations section
        content += `
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Recommendations</h5>
                </div>
                <div class="card-body">
                    <div class="row">
        `;
        
        // Add recommendations based on scores and issues
        // 1. HTTPS recommendation
        if (!data.has_ssl) {
            content += this.getRecommendationItem(
                'Enable HTTPS',
                'Implementing HTTPS is essential for security and SEO. Google uses HTTPS as a ranking signal.',
                'high'
            );
        }
        
        // 2. Schema markup recommendation
        if (!data.schema_markup_valid) {
            content += this.getRecommendationItem(
                'Implement Schema Markup',
                'Adding structured data helps search engines understand your content and can result in rich snippets in search results.',
                'medium'
            );
        }
        
        // 3. Mobile optimization recommendation
        if (data.mobile_friendly_score < 70) {
            content += this.getRecommendationItem(
                'Improve Mobile Responsiveness',
                'With mobile-first indexing, having a mobile-friendly website is crucial for SEO performance.',
                data.mobile_friendly_score < 50 ? 'high' : 'medium'
            );
        }
        
        // 4. Page speed recommendation
        if (data.site_speed_score < 70) {
            content += this.getRecommendationItem(
                'Optimize Page Speed',
                'Slow-loading pages lead to higher bounce rates and lower rankings. Optimize images, use browser caching, and minimize JavaScript.',
                data.site_speed_score < 50 ? 'high' : 'medium'
            );
        }
        
        // 5. XML Sitemap recommendation
        if (!data.has_sitemap) {
            content += this.getRecommendationItem(
                'Create XML Sitemap',
                'An XML sitemap helps search engines discover and index all the pages on your website.',
                'medium',
                `<pre><code>&lt;?xml version="1.0" encoding="UTF-8"?&gt;
&lt;urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"&gt;
  &lt;url&gt;
    &lt;loc&gt;https://example.com/&lt;/loc&gt;
    &lt;lastmod&gt;2023-01-01&lt;/lastmod&gt;
    &lt;changefreq&gt;monthly&lt;/changefreq&gt;
    &lt;priority&gt;1.0&lt;/priority&gt;
  &lt;/url&gt;
&lt;/urlset&gt;</code></pre>`
            );
        }
        
        // 6. Robots.txt recommendation
        if (!data.has_robots_txt) {
            content += this.getRecommendationItem(
                'Create Robots.txt File',
                'A robots.txt file tells search engines which pages they can and cannot crawl on your website.',
                'medium',
                `<pre><code>User-agent: *
Allow: /
Disallow: /admin/
Disallow: /private/
Sitemap: https://example.com/sitemap.xml</code></pre>`
            );
        }
        
        // Add other recommendations based on specific scores
        if (data.crawlability_score < 70) {
            content += this.getRecommendationItem(
                'Improve Site Crawlability',
                'Make sure your website can be easily crawled by search engines. Fix broken links, improve site structure, and optimize your robots.txt file.',
                'high'
            );
        }
        
        if (data.indexability_score < 70) {
            content += this.getRecommendationItem(
                'Improve Indexability',
                'Ensure your pages can be indexed by search engines. Check for noindex tags, canonicals, and proper HTTP status codes.',
                'high'
            );
        }
        
        content += `
                    </div>
                </div>
            </div>
        `;
        
        // Add the content to the technicalContent div
        $('#technicalContent').html(content);
        
        // Apply enhancements to recommendations
        this.addStyles();
        this.processRecommendations(data);

        // Store data for later use
        $('#technicalContent').data('results', data);
    },
    
    /**
     * Calculates overall score based on individual metrics
     * @param {Object} data Technical SEO data
     * @returns {number} Overall score (0-100)
     */
    calculateOverallScore: function(data) {
        // Calculate weighted average of individual scores
        const weights = {
            crawlability_score: 0.25,
            indexability_score: 0.25,
            site_speed_score: 0.25,
            mobile_friendly_score: 0.25
        };
        
        let totalWeight = 0;
        let weightedSum = 0;
        
        for (const [key, weight] of Object.entries(weights)) {
            if (data[key] !== undefined) {
                weightedSum += data[key] * weight;
                totalWeight += weight;
            }
        }
        
        // Return rounded score, or 0 if no data available
        return totalWeight > 0 ? Math.round(weightedSum / totalWeight) : 0;
    },
    
    /**
     * Gets CSS class for score circle based on score value
     * @param {number} score Score value
     * @returns {string} CSS class
     */
    getScoreClass: function(score) {
        if (score >= 90) return 'score-excellent';
        if (score >= 70) return 'score-good';
        if (score >= 50) return 'score-average';
        return 'score-poor';
    },
    
    /**
     * Gets CSS class for progress bar based on score value
     * @param {number} score Score value
     * @returns {string} CSS class
     */
    getProgressBarClass: function(score) {
        if (score >= 90) return 'bg-success';
        if (score >= 70) return 'bg-info';
        if (score >= 50) return 'bg-warning';
        return 'bg-danger';
    },
    
    /**
     * Gets CSS class for issue severity badge
     * @param {string} severity Severity level (high, medium, low)
     * @returns {string} CSS class
     */
    getSeverityClass: function(severity) {
        switch (severity.toLowerCase()) {
            case 'high':
                return 'bg-danger';
            case 'medium':
                return 'bg-warning';
            case 'low':
                return 'bg-info';
            default:
                return 'bg-secondary';
        }
    },
    
    /**
     * Gets impact description based on issue severity
     * @param {string} severity Severity level (high, medium, low)
     * @returns {string} Impact description
     */
    getIssueImpact: function(severity) {
        switch (severity.toLowerCase()) {
            case 'high':
                return 'Significant negative impact on SEO performance';
            case 'medium':
                return 'Moderate impact on SEO performance';
            case 'low':
                return 'Minor impact on SEO performance';
            default:
                return 'Unknown impact';
        }
    },
    
    /**
     * Gets page load time description based on site speed score
     * @param {number} speedScore Site speed score
     * @returns {string} Page load time description
     */
    getPageLoadTime: function(speedScore) {
        if (speedScore >= 90) return '<span class="badge bg-success">< 2 seconds</span>';
        if (speedScore >= 70) return '<span class="badge bg-info">2-3 seconds</span>';
        if (speedScore >= 50) return '<span class="badge bg-warning">3-5 seconds</span>';
        return '<span class="badge bg-danger">> 5 seconds</span>';
    },
    
    /**
     * Generates HTML for a recommendation item
     * @param {string} title Recommendation title
     * @param {string} description Recommendation description
     * @param {string} priority Priority level (high, medium, low)
     * @param {string} example Optional example code
     * @returns {string} HTML for recommendation item
     */
    getRecommendationItem: function(title, description, priority, example = '') {
        const priorityClass = priority === 'high' ? 'bg-danger' : 
                             (priority === 'medium' ? 'bg-warning' : 'bg-info');
        
        let html = `
            <div class="col-md-6 mb-3 technic-seo">
                <div class="recommendation-item">
                    <h5>${title} <span class="badge ${priorityClass}">${priority} priority</span></h5>
                    <p>${description}</p>
        `;
        
        if (example) {
            html += `
                    <div class="code-example">
                        ${example}
                    </div>
            `;
        }
        
        html += `
                </div>
            </div>
        `;
        
        return html;
    },
    
    /**
     * Processes recommendations and replaces example sections with interactive generators
     * @param {Object} data The technical SEO analysis data
     */
    processRecommendations: function(data) {
        // Extract the URL from the data
        const url = data.url || window.location.origin;
        
        // Wait for the DOM to be ready
        setTimeout(() => {
            // Find all recommendation items
            const recommendations = document.querySelectorAll('#technicalContent .recommendation-item');
            
            recommendations.forEach(recommendation => {
                const title = recommendation.querySelector('h5').textContent.toLowerCase();
                
                // Process based on recommendation type
                if (title.includes('sitemap') || title.includes('xml sitemap')) {
                    this.enhanceSitemapRecommendation(recommendation, url);
                } else if (title.includes('robots.txt')) {
                    this.enhanceRobotsTxtRecommendation(recommendation, url);
                } else if (title.includes('htaccess') || title.includes('redirect')) {
                    this.enhanceHtaccessRecommendation(recommendation, url);
                }
            });
        }, 200); // Give time for the original content to render
    },

    /**
     * Enhances sitemap recommendation with interactive generator
     * @param {Element} recommendation The recommendation DOM element
     * @param {string} url The website URL
     */
    enhanceSitemapRecommendation: function(recommendation, url) {
        // Find or create a container for the generator
        let container = recommendation.querySelector('.code-example');
        if (!container) {
            container = document.createElement('div');
            container.className = 'code-example mt-3';
            recommendation.appendChild(container);
        } else {
            // Store original content for toggle functionality
            container.dataset.originalContent = container.innerHTML;
        }
        
        // Create the interactive UI
        container.innerHTML = `
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">XML Sitemap Generator</h6>
                    <button class="btn btn-sm btn-outline-secondary toggle-original-btn">
                        <i class="fas fa-code"></i> Toggle Example
                    </button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <textarea class="form-control code-textarea" rows="10" readonly></textarea>
                    </div>
                    <div class="d-flex">
                        <button class="btn btn-primary copy-btn me-2">
                            <i class="fas fa-copy"></i> Copy to Clipboard
                        </button>
                        <button class="btn btn-success download-btn">
                            <i class="fas fa-download"></i> Download sitemap.xml
                        </button>
                    </div>
                    <div class="options-container mt-3">
                        <button class="btn btn-link p-0" type="button" data-bs-toggle="collapse" data-bs-target="#sitemapOptions">
                            <i class="fas fa-cog"></i> Advanced Options
                        </button>
                        <div class="collapse mt-2" id="sitemapOptions">
                            <div class="card card-body bg-light">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="includeImages" checked>
                                            <label class="form-check-label" for="includeImages">
                                                Include Images
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="includeDates" checked>
                                            <label class="form-check-label" for="includeDates">
                                                Include Last Modified Dates
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-primary mt-2 regenerate-btn">
                                    <i class="fas fa-sync-alt"></i> Regenerate Sitemap
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Generate initial sitemap XML
        const generateSitemapXml = (baseUrl, includeImages = true, includeDates = true) => {
            const currentDate = new Date().toISOString().split('T')[0];
            
            let xml = '<?xml version="1.0" encoding="UTF-8"?>\n';
            xml += '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
            
            if (includeImages) {
                xml += ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"';
            }
            
            xml += '>\n';
            
            // Sample pages
            const pages = [
                { url: '/', priority: '1.0', changefreq: 'weekly' },
                { url: '/about', priority: '0.8', changefreq: 'monthly' },
                { url: '/services', priority: '0.8', changefreq: 'monthly' },
                { url: '/products', priority: '0.8', changefreq: 'monthly' },
                { url: '/contact', priority: '0.7', changefreq: 'monthly' },
                { url: '/blog', priority: '0.9', changefreq: 'weekly' },
                { url: '/blog/post-1', priority: '0.6', changefreq: 'monthly' },
                { url: '/blog/post-2', priority: '0.6', changefreq: 'monthly' }
            ];
            
            // Normalize base URL
            let baseUrlNormalized = baseUrl;
            if (baseUrlNormalized.endsWith('/')) {
                baseUrlNormalized = baseUrlNormalized.slice(0, -1);
            }
            
            // Add each page to the sitemap
            pages.forEach(page => {
                const fullUrl = baseUrlNormalized + page.url;
                
                xml += '  <url>\n';
                xml += `    <loc>${fullUrl}</loc>\n`;
                
                if (includeDates) {
                    xml += `    <lastmod>${currentDate}</lastmod>\n`;
                }
                
                xml += `    <changefreq>${page.changefreq}</changefreq>\n`;
                xml += `    <priority>${page.priority}</priority>\n`;
                
                if (includeImages && page.url.includes('/product')) {
                    xml += '    <image:image>\n';
                    xml += `      <image:loc>${baseUrlNormalized}/images/product1.jpg</image:loc>\n`;
                    xml += '      <image:title>Product Image</image:title>\n';
                    xml += '    </image:image>\n';
                }
                
                xml += '  </url>\n';
            });
            
            xml += '</urlset>';
            
            return xml;
        };
        
        // Initialize the XML and textarea
        const textarea = container.querySelector('textarea');
        const sitemap = generateSitemapXml(url);
        textarea.value = sitemap;
        
        // Set up event listeners
        const copyBtn = container.querySelector('.copy-btn');
        const downloadBtn = container.querySelector('.download-btn');
        const toggleBtn = container.querySelector('.toggle-original-btn');
        const regenerateBtn = container.querySelector('.regenerate-btn');
        const includeImagesCheckbox = container.querySelector('#includeImages');
        const includeDatesCheckbox = container.querySelector('#includeDates');
        
        copyBtn.addEventListener('click', () => {
            // Copy to clipboard
            textarea.select();
            document.execCommand('copy');
            
            // Show success message
            const originalText = copyBtn.innerHTML;
            copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            setTimeout(() => {
                copyBtn.innerHTML = originalText;
            }, 2000);
        });
        
        downloadBtn.addEventListener('click', () => {
            // Create and download file
            const blob = new Blob([textarea.value], { type: 'application/xml' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'sitemap.xml';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });
        
        toggleBtn.addEventListener('click', () => {
            // Toggle between generator and original example
            const isShowingGenerator = container.querySelector('.card') !== null;
            
            if (isShowingGenerator) {
                // Store generator content
                container.dataset.generatorContent = container.innerHTML;
                // Show original content
                container.innerHTML = container.dataset.originalContent || '<p>No original example available.</p>';
                toggleBtn.innerHTML = '<i class="fas fa-tools"></i> Show Generator';
            } else {
                // Restore generator content
                container.innerHTML = container.dataset.generatorContent;
                toggleBtn.innerHTML = '<i class="fas fa-code"></i> Toggle Example';
                
                // Re-bind event listeners (they're lost when innerHTML is changed)
                this.enhanceSitemapRecommendation(recommendation, url);
            }
        });
        
        regenerateBtn.addEventListener('click', () => {
            // Regenerate sitemap with selected options
            const includeImages = includeImagesCheckbox.checked;
            const includeDates = includeDatesCheckbox.checked;
            textarea.value = generateSitemapXml(url, includeImages, includeDates);
        });
    },

    /**
     * Enhances robots.txt recommendation with interactive generator
     * @param {Element} recommendation The recommendation DOM element
     * @param {string} url The website URL
     */
    enhanceRobotsTxtRecommendation: function(recommendation, url) {
        // Find or create a container for the generator
        let container = recommendation.querySelector('.code-example');
        if (!container) {
            container = document.createElement('div');
            container.className = 'code-example mt-3';
            recommendation.appendChild(container);
        } else {
            // Store original content for toggle functionality
            container.dataset.originalContent = container.innerHTML;
        }
        
        // Create the interactive UI
        container.innerHTML = `
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Robots.txt Generator</h6>
                    <button class="btn btn-sm btn-outline-secondary toggle-original-btn">
                        <i class="fas fa-code"></i> Toggle Example
                    </button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <textarea class="form-control code-textarea" rows="10" readonly></textarea>
                    </div>
                    <div class="d-flex">
                        <button class="btn btn-primary copy-btn me-2">
                            <i class="fas fa-copy"></i> Copy to Clipboard
                        </button>
                        <button class="btn btn-success download-btn">
                            <i class="fas fa-download"></i> Download robots.txt
                        </button>
                    </div>
                    <div class="options-container mt-3">
                        <button class="btn btn-link p-0" type="button" data-bs-toggle="collapse" data-bs-target="#robotsOptions">
                            <i class="fas fa-cog"></i> Advanced Options
                        </button>
                        <div class="collapse mt-2" id="robotsOptions">
                            <div class="card card-body bg-light">
                                <div class="row g-2">
                                    <div class="col-md-12">
                                        <label class="form-label">Disallow Directories:</label>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input disallow-check" type="checkbox" id="disallowAdmin" checked>
                                                    <label class="form-check-label" for="disallowAdmin">
                                                        /admin/
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input disallow-check" type="checkbox" id="disallowCgi" checked>
                                                    <label class="form-check-label" for="disallowCgi">
                                                        /cgi-bin/
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input disallow-check" type="checkbox" id="disallowTmp" checked>
                                                    <label class="form-check-label" for="disallowTmp">
                                                        /tmp/
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input disallow-check" type="checkbox" id="disallowWpAdmin" checked>
                                                    <label class="form-check-label" for="disallowWpAdmin">
                                                        /wp-admin/
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="includeSitemap" checked>
                                            <label class="form-check-label" for="includeSitemap">
                                                Include Sitemap Reference
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-primary mt-2 regenerate-btn">
                                    <i class="fas fa-sync-alt"></i> Regenerate Robots.txt
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Generate initial robots.txt
        const generateRobotsTxt = (baseUrl, disallowPaths = [], includeSitemap = true) => {
            // Normalize base URL
            let baseUrlNormalized = baseUrl;
            if (baseUrlNormalized.endsWith('/')) {
                baseUrlNormalized = baseUrlNormalized.slice(0, -1);
            }
            
            let robotsTxt = `# robots.txt for ${baseUrlNormalized}\n`;
            robotsTxt += `# Generated on ${new Date().toISOString().split('T')[0]}\n\n`;
            
            robotsTxt += "User-agent: *\n";
            robotsTxt += "Allow: /\n\n";
            
            // Add disallow paths
            if (disallowPaths.length > 0) {
                disallowPaths.forEach(path => {
                    robotsTxt += `Disallow: ${path}\n`;
                });
                robotsTxt += "\n";
            }
            
            // Add sitemap
            if (includeSitemap) {
                robotsTxt += `Sitemap: ${baseUrlNormalized}/sitemap.xml\n`;
            }
            
            return robotsTxt;
        };
        
        // Initialize the textarea
        const textarea = container.querySelector('textarea');
        const robotsTxt = generateRobotsTxt(url, ['/admin/', '/cgi-bin/', '/tmp/', '/wp-admin/'], true);
        textarea.value = robotsTxt;
        
        // Set up event listeners
        const copyBtn = container.querySelector('.copy-btn');
        const downloadBtn = container.querySelector('.download-btn');
        const toggleBtn = container.querySelector('.toggle-original-btn');
        const regenerateBtn = container.querySelector('.regenerate-btn');
        const includeSitemapCheckbox = container.querySelector('#includeSitemap');
        const disallowCheckboxes = container.querySelectorAll('.disallow-check');
        
        copyBtn.addEventListener('click', () => {
            // Copy to clipboard
            textarea.select();
            document.execCommand('copy');
            
            // Show success message
            const originalText = copyBtn.innerHTML;
            copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            setTimeout(() => {
                copyBtn.innerHTML = originalText;
            }, 2000);
        });
        
        downloadBtn.addEventListener('click', () => {
            // Create and download file
            const blob = new Blob([textarea.value], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'robots.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });
        
        toggleBtn.addEventListener('click', () => {
            // Toggle between generator and original example
            const isShowingGenerator = container.querySelector('.card') !== null;
            
            if (isShowingGenerator) {
                // Store generator content
                container.dataset.generatorContent = container.innerHTML;
                // Show original content
                container.innerHTML = container.dataset.originalContent || '<p>No original example available.</p>';
                toggleBtn.innerHTML = '<i class="fas fa-tools"></i> Show Generator';
            } else {
                // Restore generator content
                container.innerHTML = container.dataset.generatorContent;
                toggleBtn.innerHTML = '<i class="fas fa-code"></i> Toggle Example';
                
                // Re-bind event listeners (they're lost when innerHTML is changed)
                this.enhanceRobotsTxtRecommendation(recommendation, url);
            }
        });
        
        regenerateBtn.addEventListener('click', () => {
            // Get selected disallow paths
            const disallowPaths = [];
            if (container.querySelector('#disallowAdmin').checked) disallowPaths.push('/admin/');
            if (container.querySelector('#disallowCgi').checked) disallowPaths.push('/cgi-bin/');
            if (container.querySelector('#disallowTmp').checked) disallowPaths.push('/tmp/');
            if (container.querySelector('#disallowWpAdmin').checked) disallowPaths.push('/wp-admin/');
            
            // Regenerate robots.txt
            const includeSitemap = includeSitemapCheckbox.checked;
            textarea.value = generateRobotsTxt(url, disallowPaths, includeSitemap);
        });
    },

    /**
     * Enhances .htaccess recommendation with interactive generator
     * @param {Element} recommendation The recommendation DOM element
     * @param {string} url The website URL
     */
    enhanceHtaccessRecommendation: function(recommendation, url) {
        // Find or create a container for the generator
        let container = recommendation.querySelector('.code-example');
        if (!container) {
            container = document.createElement('div');
            container.className = 'code-example mt-3';
            recommendation.appendChild(container);
        } else {
            // Store original content for toggle functionality
            container.dataset.originalContent = container.innerHTML;
        }
        
        // Create the interactive UI
        container.innerHTML = `
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">.htaccess Generator</h6>
                    <button class="btn btn-sm btn-outline-secondary toggle-original-btn">
                        <i class="fas fa-code"></i> Toggle Example
                    </button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <textarea class="form-control code-textarea" rows="10" readonly></textarea>
                    </div>
                    <div class="d-flex">
                        <button class="btn btn-primary copy-btn me-2">
                            <i class="fas fa-copy"></i> Copy to Clipboard
                        </button>
                        <button class="btn btn-success download-btn">
                            <i class="fas fa-download"></i> Download .htaccess
                        </button>
                    </div>
                    <div class="options-container mt-3">
                        <button class="btn btn-link p-0" type="button" data-bs-toggle="collapse" data-bs-target="#htaccessOptions">
                            <i class="fas fa-cog"></i> Advanced Options
                        </button>
                        <div class="collapse mt-2" id="htaccessOptions">
                            <div class="card card-body bg-light">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="includeHttps" checked>
                                            <label class="form-check-label" for="includeHttps">
                                                HTTP to HTTPS Redirect
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="includeWww">
                                            <label class="form-check-label" for="includeWww">
                                                Non-WWW to WWW Redirect
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="includeGzip" checked>
                                            <label class="form-check-label" for="includeGzip">
                                                Enable Gzip Compression
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="includeCaching" checked>
                                            <label class="form-check-label" for="includeCaching">
                                                Browser Caching
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-primary mt-2 regenerate-btn">
                                    <i class="fas fa-sync-alt"></i> Regenerate .htaccess
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Generate initial .htaccess file
        const generateHtaccess = (includeHttps = true, includeWww = false, includeGzip = true, includeCaching = true) => {
            let htaccess = "# .htaccess file with SEO optimizations\n\n";
            
            // Include HTTPS redirect
            if (includeHttps) {
                htaccess += "# Redirect HTTP to HTTPS\n";
                htaccess += "<IfModule mod_rewrite.c>\n";
                htaccess += "    RewriteEngine On\n";
                htaccess += "    RewriteCond %{HTTPS} off\n";
                htaccess += "    RewriteRule (.*) https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]\n";
                htaccess += "</IfModule>\n\n";
            }
            
            // Include WWW redirect
            if (includeWww) {
                htaccess += "# Redirect non-www to www\n";
                htaccess += "<IfModule mod_rewrite.c>\n";
                htaccess += "    RewriteEngine On\n";
                htaccess += "    RewriteCond %{HTTP_HOST} !^www\\. [NC]\n";
                htaccess += "    RewriteRule (.*) https://www.%{HTTP_HOST}%{REQUEST_URI} [R=301,L]\n";
                htaccess += "</IfModule>\n\n";
            }
            
            // Include Gzip compression
            if (includeGzip) {
                htaccess += "# Enable Gzip compression\n";
                htaccess += "<IfModule mod_deflate.c>\n";
                htaccess += "    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css application/javascript application/json\n";
                htaccess += "    BrowserMatch ^Mozilla/4 gzip-only-text/html\n";
                htaccess += "    BrowserMatch ^Mozilla/4\\.0[678] no-gzip\n";
                htaccess += "    BrowserMatch \\bMSIE !no-gzip !gzip-only-text/html\n";
                htaccess += "</IfModule>\n\n";
            }
            
            // Include browser caching
            if (includeCaching) {
                htaccess += "# Set browser caching\n";
                htaccess += "<IfModule mod_expires.c>\n";
                htaccess += "    ExpiresActive On\n";
                htaccess += "    ExpiresByType image/jpg \"access 1 year\"\n";
                htaccess += "    ExpiresByType image/jpeg \"access 1 year\"\n";
                htaccess += "    ExpiresByType image/gif \"access 1 year\"\n";
                htaccess += "    ExpiresByType image/png \"access 1 year\"\n";
                htaccess += "    ExpiresByType image/svg+xml \"access 1 year\"\n";
                htaccess += "    ExpiresByType text/css \"access 1 month\"\n";
                htaccess += "    ExpiresByType application/pdf \"access 1 month\"\n";
                htaccess += "    ExpiresByType application/javascript \"access 1 month\"\n";
                htaccess += "    ExpiresByType text/x-javascript \"access 1 month\"\n";
                htaccess += "    ExpiresByType text/html \"access 1 month\"\n";
                htaccess += "</IfModule>\n\n";
            }
            
            // Add trailing slash rule
            htaccess += "# Add trailing slash to urls\n";
            htaccess += "<IfModule mod_rewrite.c>\n";
            htaccess += "    RewriteEngine On\n";
            htaccess += "    RewriteCond %{REQUEST_FILENAME} !-f\n";
            htaccess += "    RewriteCond %{REQUEST_URI} !\\..+$\n";
            htaccess += "    RewriteCond %{REQUEST_URI} !(.*)/$\n";
            htaccess += "    RewriteRule ^(.*[^/])$ $1/ [R=301,L]\n";
            htaccess += "</IfModule>\n";
            
            return htaccess;
        };
        
        // Initialize the textarea
        const textarea = container.querySelector('textarea');
        const htaccess = generateHtaccess(true, false, true, true);
        textarea.value = htaccess;
        
        // Set up event listeners
        const copyBtn = container.querySelector('.copy-btn');
        const downloadBtn = container.querySelector('.download-btn');
        const toggleBtn = container.querySelector('.toggle-original-btn');
        const regenerateBtn = container.querySelector('.regenerate-btn');
        const httpsCheckbox = container.querySelector('#includeHttps');
        const wwwCheckbox = container.querySelector('#includeWww');
        const gzipCheckbox = container.querySelector('#includeGzip');
        const cachingCheckbox = container.querySelector('#includeCaching');
        
        copyBtn.addEventListener('click', () => {
            // Copy to clipboard
            textarea.select();
            document.execCommand('copy');
            
            // Show success message
            const originalText = copyBtn.innerHTML;
            copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            setTimeout(() => {
                copyBtn.innerHTML = originalText;
            }, 2000);
        });
        
        downloadBtn.addEventListener('click', () => {
            // Create and download file
            const blob = new Blob([textarea.value], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = '.htaccess';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });
        
        toggleBtn.addEventListener('click', () => {
            // Toggle between generator and original example
            const isShowingGenerator = container.querySelector('.card') !== null;
            
            if (isShowingGenerator) {
                // Store generator content
                container.dataset.generatorContent = container.innerHTML;
                // Show original content
                container.innerHTML = container.dataset.originalContent || '<p>No original example available.</p>';
                toggleBtn.innerHTML = '<i class="fas fa-tools"></i> Show Generator';
            } else {
                // Restore generator content
                container.innerHTML = container.dataset.generatorContent;
                toggleBtn.innerHTML = '<i class="fas fa-code"></i> Toggle Example';
                
                // Re-bind event listeners (they're lost when innerHTML is changed)
                this.enhanceHtaccessRecommendation(recommendation, url);
            }
        });
        
        regenerateBtn.addEventListener('click', () => {
            // Regenerate .htaccess with selected options
            const includeHttps = httpsCheckbox.checked;
            const includeWww = wwwCheckbox.checked;
            const includeGzip = gzipCheckbox.checked;
            const includeCaching = cachingCheckbox.checked;
            
            textarea.value = generateHtaccess(includeHttps, includeWww, includeGzip, includeCaching);
        });
    },

    /**
     * Add CSS styles for code textareas and controls
     */
    addStyles: function() {
        // Check if styles are already added
        if (document.getElementById('technical-seo-styles')) {
            return;
        }
        
        // Create style element
        const style = document.createElement('style');
        style.id = 'technical-seo-styles';
        style.textContent = `
            .code-textarea {
                font-family: 'Courier New', monospace;
                font-size: 13px;
                line-height: 1.4;
                white-space: pre;
                overflow-x: auto;
                background-color: #f8f9fa;
                color: #212529;
            }
            
            .copy-btn, .download-btn {
                transition: all 0.2s ease;
            }
            
            .code-example {
                margin-top: 15px;
                margin-bottom: 15px;
            }
            
            .options-container {
                border-top: 1px solid #e9ecef;
                padding-top: 10px;
            }
        `;
        
        // Add to head
        document.head.appendChild(style);
    }
};

                                