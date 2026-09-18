/**
 * page-structure.js - Frontend display logic for page structure analysis
 * File path: js/page-structure.js
 */

const PageStructure = {
    /**
     * Initialize the page structure analysis module
     */
    initialize: function() {
        console.log('Page Structure Analysis module initialized');
        
        // Set up event listeners
        this.setupEventListeners();
    },
    
    /**
     * Set up event listeners
     */
    setupEventListeners: function() {
        // Listen for when page structure analysis is requested
        $(document).on('requestPageStructureAnalysis', (event, url) => {
            this.performPageStructureAnalysis(url);
        });
        
        // Listen for re-analysis button clicks
        $(document).on('click', '#rerunPageStructureBtn', function() {
            const url = $('#websiteUrl').val();
            if (url) {
                PageStructure.performPageStructureAnalysis(url);
            }
        });
        
        // Listen for max pages change
        $(document).on('change', '#pageStructureMaxPages', function() {
            const url = $('#websiteUrl').val();
            if (url) {
                PageStructure.performPageStructureAnalysis(url);
            }
        });
    },
    
    /**
     * Perform page structure analysis
     * 
     * @param {string} url URL to analyze
     */
    performPageStructureAnalysis: function(url) {
		
		this.cleanupCharts();
        // Show loading indicator
        $('#pageStructureContent').html(`
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Analyzing page structure... This may take a moment.</p>
            </div>
        `);
        
        // Get max pages setting
        const maxPages = $('#pageStructureMaxPages').val() || 5;
        
        // Call API
        $.ajax({
            url: 'api/page-structure-api.php',
            type: 'POST',
            data: { 
                url: url,
                max_pages: maxPages
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.displayResults(response.data);
                } else {
                    this.displayError(response.message || 'Unknown error occurred during page structure analysis.');
                }
            },
            error: (xhr, status, error) => {
                this.displayError('Server error: ' + error);
            }
        });
    },
    
    /**
     * Display page structure analysis results
     * 
     * @param {Object} data Analysis results
     */
    displayResults: function(data) {
		
		this.cleanupCharts();
        // Handle null or undefined data
        if (!data) {
            $('#pageStructureContent').html(`
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> No page structure analysis data available.
                </div>
            `);
            return;
        }
        
        try {
            // Calculate scores for visual representation
            const headingScore = data.heading_structure_score || 0;
            const semanticScore = data.semantic_html_score || 0;
            const contentRatioScore = data.content_html_ratio_score || 0;
            const schemaScore = data.schema_markup_score || 0;
            const overallScore = data.overall_score || 0;
            
            // Create HTML content
            let content = `
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Page Structure Analysis Overview</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 text-center">
                                        <div class="score-circle ${this.getScoreClass(overallScore)}">
                                            <div class="score-number">${overallScore}</div>
                                        </div>
                                        <div class="mt-2">Overall Score</div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Heading Structure</label>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar ${this.getProgressBarClass(headingScore)}" 
                                                     style="width: ${headingScore}%;" 
                                                     aria-valuenow="${headingScore}" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    ${headingScore}%
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Semantic HTML</label>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar ${this.getProgressBarClass(semanticScore)}" 
                                                     style="width: ${semanticScore}%;" 
                                                     aria-valuenow="${semanticScore}" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    ${semanticScore}%
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Content/HTML Ratio</label>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar ${this.getProgressBarClass(contentRatioScore)}" 
                                                     style="width: ${contentRatioScore}%;" 
                                                     aria-valuenow="${contentRatioScore}" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    ${contentRatioScore}%
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Schema Markup</label>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar ${this.getProgressBarClass(schemaScore)}" 
                                                     style="width: ${schemaScore}%;" 
                                                     aria-valuenow="${schemaScore}" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    ${schemaScore}%
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Analysis Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="pageStructureMaxPages" class="form-label">Pages Analyzed:</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="pageStructureMaxPages" 
                                             min="1" max="10" value="${data.analyzed_pages || 5}">
                                        <button class="btn btn-outline-secondary" type="button" id="rerunPageStructureBtn">
                                            <i class="fas fa-sync-alt"></i> Rerun
                                        </button>
                                    </div>
                                    <div class="form-text">Adjust the number of pages to analyze (1-10)</div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                                    <span>Pages with H1 tags:</span>
                                    <span class="badge bg-${this.getH1Badge(data)}">${this.countPagesWithH1(data)}/${data.analyzed_pages}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                                    <span>Pages with schema markup:</span>
                                    <span class="badge bg-${this.getSchemaMarkupBadge(data)}">${this.countPagesWithSchema(data)}/${data.analyzed_pages}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Avg. content word count:</span>
                                    <span class="badge bg-${this.getWordCountBadge(data)}">${this.getAvgWordCount(data)}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Add heading structure section
            content += this.renderHeadingStructureSection(data);
            
            // Add semantic HTML section
            content += this.renderSemanticHTMLSection(data);
            
            // Issues and recommendations
            content += `
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Identified Issues</h5>
                            </div>
                            <div class="card-body">
                                ${this.renderIssuesList(data.issues)}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Recommendations</h5>
                            </div>
                            <div class="card-body">
                                ${this.renderRecommendationsList(data.recommendations)}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Add analyzed pages table
            content += this.renderAnalyzedPagesSection(data);
            
            // Update the content
            $('#pageStructureContent').html(content);
            UIManager.updateScoreBadge('pageStructure', data.overall_score);
            
            // Store the data for later use
            $('#pageStructureContent').data('results', data);
        } catch (error) {
            console.error("Error displaying page structure analysis results:", error);
            $('#pageStructureContent').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Error displaying page structure analysis: ${error.message}
                </div>
            `);
        }
    },
    
    /**
     * Display error message
     * 
     * @param {string} message Error message
     */
    displayError: function(message) {
        $('#pageStructureContent').html(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i> ${message}
            </div>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Page Structure Analysis Troubleshooting</h5>
                </div>
                <div class="card-body">
                    <p>The page structure analysis could not be completed. Here are some possible reasons:</p>
                    <ul>
                        <li>The website is blocking automated crawling</li>
                        <li>The website requires JavaScript to display content</li>
                        <li>The website has robots.txt restrictions</li>
                        <li>The server is responding slowly or is unreachable</li>
                    </ul>
                    <div class="mt-3">
                        <button id="rerunPageStructureBtn" class="btn btn-primary">
                            <i class="fas fa-sync-alt me-1"></i> Try Again
                        </button>
                    </div>
                </div>
            </div>
        `);
    },
    
    /**
     * Render heading structure section
     * 
     * @param {Object} data Analysis data
     * @return {string} HTML content
     */
    renderHeadingStructureSection: function(data) {
        let html = `
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Heading Structure Analysis</h5>
                    <span class="badge bg-${this.getProgressBarClass(data.heading_structure_score).replace('bg-', '')}">
                        Score: ${data.heading_structure_score}/100
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2">Heading Hierarchy</h6>
                            <canvas id="headingDistributionChart" height="250"></canvas>
                        </div>
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2">Common Issues</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Issue</th>
                                            <th>Affected Pages</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
        `;
        
        // Count common issues
        let missingH1Count = 0;
        let multipleH1Count = 0;
        let skippedLevelsCount = 0;
        
        if (data.pages_data) {
            data.pages_data.forEach(page => {
                if (!page.heading_structure.has_h1) {
                    missingH1Count++;
                }
                
                if (page.heading_structure.multiple_h1) {
                    multipleH1Count++;
                }
                
                if (page.heading_structure.skipped_levels) {
                    skippedLevelsCount++;
                }
            });
        }
        
        // Add rows for each issue
        html += `
            <tr>
                <td>Missing H1 tag</td>
                <td>${missingH1Count}/${data.analyzed_pages}</td>
                <td>${this.getStatusBadge(missingH1Count === 0)}</td>
            </tr>
            <tr>
                <td>Multiple H1 tags</td>
                <td>${multipleH1Count}/${data.analyzed_pages}</td>
                <td>${this.getStatusBadge(multipleH1Count === 0)}</td>
            </tr>
            <tr>
                <td>Skipped heading levels</td>
                <td>${skippedLevelsCount}/${data.analyzed_pages}</td>
                <td>${this.getStatusBadge(skippedLevelsCount === 0)}</td>
            </tr>
        `;
        
        html += `
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <h6 class="border-bottom pb-2">Best Practices</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check-circle text-success me-2"></i> Use exactly one H1 per page</li>
                                    <li><i class="fas fa-check-circle text-success me-2"></i> Follow proper hierarchy (H1 → H2 → H3)</li>
                                    <li><i class="fas fa-check-circle text-success me-2"></i> Use headings to structure content logically</li>
                                    <li><i class="fas fa-check-circle text-success me-2"></i> Include keywords in headings where natural</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Initialize chart after rendering
        setTimeout(() => {
            this.initHeadingDistributionChart(data);
        }, 100);
        
        return html;
    },
    
    /**
     * Render semantic HTML section
     * 
     * @param {Object} data Analysis data
     * @return {string} HTML content
     */
    renderSemanticHTMLSection: function(data) {
        let html = `
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Semantic HTML Usage</h5>
                    <span class="badge bg-${this.getProgressBarClass(data.semantic_html_score).replace('bg-', '')}">
                        Score: ${data.semantic_html_score}/100
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2">Semantic Elements</h6>
                            <canvas id="semanticElementsChart" height="250"></canvas>
                        </div>
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2">Semantic vs. Generic Elements</h6>
                            <p>Semantic elements provide meaning to both browsers and search engines about the content structure.</p>
                            
                            <div class="table-responsive mt-3">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Element</th>
                                            <th>Present on</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
        `;
        
        // Count semantic elements
        let headerCount = 0;
        let navCount = 0;
        let mainCount = 0;
        let footerCount = 0;
        let sectionCount = 0;
        let articleCount = 0;
        
        if (data.pages_data) {
            data.pages_data.forEach(page => {
                if (page.semantic_elements.has_header) headerCount++;
                if (page.semantic_elements.has_nav) navCount++;
                if (page.semantic_elements.has_main) mainCount++;
                if (page.semantic_elements.has_footer) footerCount++;
                if (page.semantic_elements.has_section) sectionCount++;
                if (page.semantic_elements.has_article) articleCount++;
            });
        }
        
        // Add rows for each semantic element
        html += `
            <tr>
                <td>&lt;header&gt;</td>
                <td>${headerCount}/${data.analyzed_pages}</td>
                <td>${this.getStatusBadge(headerCount > 0)}</td>
            </tr>
            <tr>
                <td>&lt;nav&gt;</td>
                <td>${navCount}/${data.analyzed_pages}</td>
                <td>${this.getStatusBadge(navCount > 0)}</td>
            </tr>
            <tr>
                <td>&lt;main&gt;</td>
                <td>${mainCount}/${data.analyzed_pages}</td>
                <td>${this.getStatusBadge(mainCount > 0)}</td>
            </tr>
            <tr>
                <td>&lt;footer&gt;</td>
                <td>${footerCount}/${data.analyzed_pages}</td>
                <td>${this.getStatusBadge(footerCount > 0)}</td>
            </tr>
            <tr>
                <td>&lt;section&gt;</td>
                <td>${sectionCount}/${data.analyzed_pages}</td>
                <td>${this.getStatusBadge(sectionCount > 0)}</td>
            </tr>
            <tr>
                <td>&lt;article&gt;</td>
                <td>${articleCount}/${data.analyzed_pages}</td>
                <td>${this.getStatusBadge(articleCount > 0)}</td>
            </tr>
        `;
        
        html += `
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Initialize chart after rendering
        setTimeout(() => {
            this.initSemanticElementsChart(data);
        }, 100);
        
        return html;
    },
    
    /**
     * Render analyzed pages section
     * 
     * @param {Object} data Analysis data
     * @return {string} HTML content
     */
    renderAnalyzedPagesSection: function(data) {
        if (!data.pages_data || data.pages_data.length === 0) {
            return '';
        }
        
        let html = `
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Analyzed Pages</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>URL</th>
                                    <th>H1</th>
                                    <th>Semantic HTML</th>
                                    <th>Content/HTML</th>
                                    <th>Schema</th>
                                    <th>Word Count</th>
                                </tr>
                            </thead>
                            <tbody>
        `;
        
        // Add rows for each page
        data.pages_data.forEach(page => {
            const hasH1Badge = page.has_h1 ? 
                '<span class="badge bg-success"><i class="fas fa-check"></i></span>' : 
                '<span class="badge bg-danger"><i class="fas fa-times"></i></span>';
            
            const semanticRatio = page.semantic_elements.semantic_ratio || 0;
            const semanticBadge = this.getRatioBadge(semanticRatio);
            
            const contentRatio = page.content_html_ratio || 0;
            const contentBadge = this.getRatioBadge(contentRatio, 0.1, 0.3);
            
            const schemaMarkupBadge = page.has_schema_markup ? 
                '<span class="badge bg-success"><i class="fas fa-check"></i></span>' : 
                '<span class="badge bg-warning"><i class="fas fa-times"></i></span>';
            
            const wordCount = page.main_content_word_count || 0;
            const wordCountClass = wordCount < 300 ? 'text-danger' :
                                 (wordCount < 500 ? 'text-warning' : 'text-success');
            
            html += `
                <tr>
                    <td><a href="${page.url}" target="_blank" class="text-truncate d-inline-block" style="max-width: 250px;" title="${page.url}">${this.truncateUrl(page.url)}</a></td>
                    <td>${hasH1Badge}</td>
                    <td>${semanticBadge}</td>
                    <td>${contentBadge}</td>
                    <td>${schemaMarkupBadge}</td>
                    <td class="${wordCountClass}">${wordCount}</td>
                </tr>
            `;
        });
        
        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
        
        return html;
    },
    
    /**
     * Render issues list
     * 
     * @param {Array} issues List of issues
     * @return {string} HTML content
     */
    renderIssuesList: function(issues) {
        if (!issues || issues.length === 0) {
            return `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i> No significant page structure issues detected
                </div>
            `;
        }
        
        let html = '<div class="list-group">';
        
        issues.forEach(issue => {
            const severityClass = this.getSeverityClass(issue.severity);
            const severityIcon = this.getSeverityIcon(issue.severity);
            
            html += `
                <div class="list-group-item list-group-item-${severityClass}">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">
                            <i class="${severityIcon} me-2"></i> ${issue.description}
                        </h6>
                        <small>${this.capitalizeFirstLetter(issue.severity)}</small>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        return html;
    },
    
    /**
     * Render recommendations list
     * 
     * @param {Array} recommendations List of recommendations
     * @return {string} HTML content
     */
    renderRecommendationsList: function(recommendations) {
        if (!recommendations || recommendations.length === 0) {
            return `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> No specific recommendations available
                </div>
            `;
        }
        
        let html = '<div class="page-recommendations-container">';
        
        recommendations.forEach((recommendation, index) => {
            const priorityClass = this.getPriorityClass(recommendation.priority);
            
            html += `
                <div class="card mb-3 border-${priorityClass}">
                    <div class="card-header bg-white d-flex align-items-center">
                        <span class="badge bg-${priorityClass} me-2">${this.capitalizeFirstLetter(recommendation.priority)}</span>
                        <h5 class="mb-0 flex-grow-1">${recommendation.description}</h5>
                    </div>
                    <div class="card-body bg-light border-top">
                        <p class="mb-0">${recommendation.details}</p>
                        ${recommendation.code_example ? `
                            <div class="mt-3">
                                <p class="mb-1 fw-bold">Example:</p>
                                <pre class="bg-dark text-light p-3 rounded"><code>${this.escapeHtml(recommendation.code_example)}</code></pre>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        return html;
    },
    
    /**
     * Initialize heading distribution chart
     * 
     * @param {Object} data Analysis data
     */
    initHeadingDistributionChart: function(data) {
        const ctx = document.getElementById('headingDistributionChart');
        if (!ctx) return;
		
		// Check if there's an existing chart and destroy it
		if (ctx.chart) {
			ctx.chart.destroy();
		}		
			
        // Prepare data for chart
        const headingCounts = [0, 0, 0, 0, 0, 0];
        
        // Sum up heading counts across all pages
        if (data.pages_data) {
            data.pages_data.forEach(page => {
                headingCounts[0] += page.heading_structure.h1_count || 0;
                headingCounts[1] += page.heading_structure.h2_count || 0;
                headingCounts[2] += page.heading_structure.h3_count || 0;
                headingCounts[3] += page.heading_structure.h4_count || 0;
                headingCounts[4] += page.heading_structure.h5_count || 0;
                headingCounts[5] += page.heading_structure.h6_count || 0;
            });
        }
        
        // Create chart
			// Create chart and store reference
			ctx.chart = new Chart(ctx, {
				type: 'bar',
				data: {
					labels: ['H1', 'H2', 'H3', 'H4', 'H5', 'H6'],
					datasets: [{
						label: 'Heading Count',
						data: headingCounts,
						backgroundColor: [
							'rgba(255, 99, 132, 0.6)',
							'rgba(54, 162, 235, 0.6)',
							'rgba(255, 206, 86, 0.6)',
							'rgba(75, 192, 192, 0.6)',
							'rgba(153, 102, 255, 0.6)',
							'rgba(255, 159, 64, 0.6)'
						],
						borderColor: [
							'rgba(255, 99, 132, 1)',
							'rgba(54, 162, 235, 1)',
							'rgba(255, 206, 86, 1)',
							'rgba(75, 192, 192, 1)',
							'rgba(153, 102, 255, 1)',
							'rgba(255, 159, 64, 1)'
						],
						borderWidth: 1
					}]
				},
				options: {
					responsive: true,
					scales: {
						y: {
							beginAtZero: true,
							title: {
								display: true,
								text: 'Count'
							}
						}
					},
					plugins: {
						title: {
							display: true,
							text: 'Heading Distribution'
						}
					}
				}
			});
		},
    
    /**
     * Initialize semantic elements chart
     * 
     * @param {Object} data Analysis data
     */
    initSemanticElementsChart: function(data) {
        const ctx = document.getElementById('semanticElementsChart');
        if (!ctx) return;
			
		// Check if there's an existing chart and destroy it
		if (ctx.chart) {
			ctx.chart.destroy();
		}		
        
        // Prepare data for chart
        let semanticCount = 0;
        let divSpanCount = 0;
        
    // Sum up semantic and div/span counts across all pages
    if (data.pages_data) {
        data.pages_data.forEach(page => {
            semanticCount += page.semantic_elements.semantic_element_count || 0;
            divSpanCount += page.semantic_elements.div_span_count || 0;
        });
    }
    
    // Create chart and store reference
    ctx.chart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Semantic Elements', 'Generic Elements (div/span)'],
            datasets: [{
                data: [semanticCount, divSpanCount],
                backgroundColor: [
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(255, 99, 132, 0.6)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 99, 132, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
},
 
cleanupCharts: function() {
    // Get chart canvases
    const headingChart = document.getElementById('headingDistributionChart');
    const semanticChart = document.getElementById('semanticElementsChart');
    
    // Destroy charts if they exist
    if (headingChart && headingChart.chart) {
        headingChart.chart.destroy();
        headingChart.chart = null;
    }
    
    if (semanticChart && semanticChart.chart) {
        semanticChart.chart.destroy();
        semanticChart.chart = null;
    }
}, 
 
 
    /**
     * Count pages with H1 tags
     * 
     * @param {Object} data Analysis data
     * @return {number} Count
     */
    countPagesWithH1: function(data) {
        if (!data.pages_data) return 0;
        
        return data.pages_data.filter(page => page.has_h1).length;
    },
    
    /**
     * Count pages with schema markup
     * 
     * @param {Object} data Analysis data
     * @return {number} Count
     */
    countPagesWithSchema: function(data) {
        if (!data.pages_data) return 0;
        
        return data.pages_data.filter(page => page.has_schema_markup).length;
    },
    
    /**
     * Get average word count
     * 
     * @param {Object} data Analysis data
     * @return {number} Average word count
     */
    getAvgWordCount: function(data) {
        if (!data.pages_data || data.pages_data.length === 0) return 0;
        
        const totalWordCount = data.pages_data.reduce((sum, page) => sum + (page.main_content_word_count || 0), 0);
        return Math.round(totalWordCount / data.pages_data.length);
    },
    
    /**
     * Get badge class for H1 status
     * 
     * @param {Object} data Analysis data
     * @return {string} Badge class
     */
    getH1Badge: function(data) {
        const pagesWithH1 = this.countPagesWithH1(data);
        const totalPages = data.analyzed_pages || 0;
        
        if (pagesWithH1 === totalPages) return 'success';
        if (pagesWithH1 === 0) return 'danger';
        return 'warning';
    },
    
    /**
     * Get badge class for schema markup status
     * 
     * @param {Object} data Analysis data
     * @return {string} Badge class
     */
    getSchemaMarkupBadge: function(data) {
        const pagesWithSchema = this.countPagesWithSchema(data);
        const totalPages = data.analyzed_pages || 0;
        
        if (pagesWithSchema === totalPages) return 'success';
        if (pagesWithSchema === 0) return 'danger';
        return 'warning';
    },
    
    /**
     * Get badge class for word count
     * 
     * @param {Object} data Analysis data
     * @return {string} Badge class
     */
    getWordCountBadge: function(data) {
        const avgWordCount = this.getAvgWordCount(data);
        
        if (avgWordCount >= 500) return 'success';
        if (avgWordCount >= 300) return 'warning';
        return 'danger';
    },
    
    /**
     * Get status badge HTML
     * 
     * @param {boolean} isGood True if status is good
     * @return {string} Badge HTML
     */
    getStatusBadge: function(isGood) {
        return isGood ? 
            '<span class="badge bg-success">Good</span>' : 
            '<span class="badge bg-warning">Needs Improvement</span>';
    },
    
    /**
     * Get badge for ratio value
     * 
     * @param {number} ratio Ratio value
     * @param {number} lowThreshold Low threshold
     * @param {number} highThreshold High threshold
     * @return {string} Badge HTML
     */
    getRatioBadge: function(ratio, lowThreshold = 0.2, highThreshold = 0.4) {
        const badgeClass = ratio < lowThreshold ? 'bg-danger' :
                          ratio < highThreshold ? 'bg-warning' : 'bg-success';
        
        return `<span class="badge ${badgeClass}">${(ratio * 100).toFixed(1)}%</span>`;
    },
    
    /**
     * Get CSS class for score
     * 
     * @param {number} score The score value
     * @return {string} CSS class
     */
    getScoreClass: function(score) {
        if (score >= 90) return 'score-excellent';
        if (score >= 70) return 'score-good';
        if (score >= 50) return 'score-average';
        return 'score-poor';
    },
    
    /**
     * Get CSS class for progress bar
     * 
     * @param {number} score The score value
     * @return {string} CSS class
     */
    getProgressBarClass: function(score) {
        if (score >= 90) return 'bg-success';
        if (score >= 70) return 'bg-primary';
        if (score >= 50) return 'bg-warning';
        return 'bg-danger';
    },
    
    /**
     * Get CSS class for severity
     * 
     * @param {string} severity The severity level
     * @return {string} CSS class
     */
    getSeverityClass: function(severity) {
        switch (severity.toLowerCase()) {
            case 'high': return 'danger';
            case 'medium': return 'warning';
            case 'low': return 'info';
            default: return 'secondary';
        }
    },
    
    /**
     * Get icon for severity
     * 
     * @param {string} severity The severity level
     * @return {string} Icon class
     */
    getSeverityIcon: function(severity) {
        switch (severity.toLowerCase()) {
            case 'high': return 'fas fa-exclamation-circle';
            case 'medium': return 'fas fa-exclamation-triangle';
            case 'low': return 'fas fa-info-circle';
            default: return 'fas fa-info-circle';
        }
    },
    
    /**
     * Get CSS class for priority
     * 
     * @param {string} priority The priority level
     * @return {string} CSS class
     */
    getPriorityClass: function(priority) {
        switch (priority.toLowerCase()) {
            case 'high': return 'danger';
            case 'medium': return 'warning';
            case 'low': return 'info';
            default: return 'secondary';
        }
    },
    
    /**
     * Truncate URL for display
     * 
     * @param {string} url The URL to truncate
     * @param {number} maxLength Maximum length
     * @return {string} Truncated URL
     */
    truncateUrl: function(url, maxLength = 50) {
        if (!url) return '';
        
        // Remove protocol
        let displayUrl = url.replace(/^https?:\/\//, '');
        
        // Truncate if too long
        if (displayUrl.length > maxLength) {
            displayUrl = displayUrl.substring(0, maxLength - 3) + '...';
        }
        
        return displayUrl;
    },
    
    /**
     * Capitalize first letter of a string
     * 
     * @param {string} string The string to capitalize
     * @return {string} Capitalized string
     */
    capitalizeFirstLetter: function(string) {
        if (!string) return '';
        return string.charAt(0).toUpperCase() + string.slice(1);
    },
    
    /**
     * Escape HTML special characters
     * 
     * @param {string} html HTML string to escape
     * @return {string} Escaped HTML
     */
    escapeHtml: function(html) {
        const div = document.createElement('div');
        div.textContent = html;
        return div.innerHTML;
    }
};

// Initialize when document is ready
$(document).ready(function() {
    PageStructure.initialize();
});
// Add an event handler for this
$(document).on('beforeAnalysisStart', function() {
    PageStructure.cleanupCharts();
});

$(window).on('unload', function() {
    PageStructure.cleanupCharts();
});

// Event listener for analysis manager integration
$(document).on('requestPageStructureAnalysis', function(event, url) {
	
	PageStructure.cleanupCharts();
    // Check if we have data already loaded
    const existingData = $('#pageStructureContent').data('results');
    
    if (existingData) {
        // Display the data directly
        PageStructure.displayResults(existingData);
    } else {
        // Request new data
        ApiService.runPageStructureAnalysis(url)
            .done(function(response) {
                if (response.success) {
                    // Display the results
                    PageStructure.displayResults(response.data);
                    
                    // Store for later reference
                    $('#pageStructureContent').data('results', response.data);
                } else {
                    // Display error
                    $('#pageStructureContent').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> Page structure analysis failed: ${response.message}
                        </div>
                    `);
                }
            })
            .fail(function() {
                // Display API error
                $('#pageStructureContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Page structure analysis failed due to a server error
                    </div>
                `);
            });
    }
});